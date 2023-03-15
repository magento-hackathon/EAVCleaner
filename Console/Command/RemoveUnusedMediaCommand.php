<?php

namespace Hackathon\EAVCleaner\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveUnusedMediaCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_INCLUDING_CACHE = 'including-cache';
    private const OPTION_FORCE = 'force';
    private const COMMAND_NAME_EAV_MEDIA_REMOVE_UNUSED = 'eav:media:remove-unused';

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem, ResourceConnection $resourceConnection, string $name = null)
    {
        parent::__construct($name);
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileSize = 0;
        $countFiles = 0;
        $isForce = $input->getOption(self::OPTION_FORCE);
        $isDryRun = $input->getOption(self::OPTION_DRY_RUN);
        $deleteCacheAsWell = $input->getOption(self::OPTION_INCLUDING_CACHE);

        if (!$isDryRun && !$isForce) {
            if (!$input->isInteractive()) {
                $output->writeln(
                    sprintf(
                        'ERROR: neither --%s nor --%s options were supplied, and we are not running interactively.', self::OPTION_DRY_RUN,
                        self::OPTION_FORCE
                    )
                );

                return Cli::RETURN_FAILURE;
            }

            $output->writeln(
                sprintf('<comment>WARNING: this is not a dry run. If you want to do a dry-run, add --%s.</comment>', self::OPTION_DRY_RUN)
            );

            $question = new ConfirmationQuestion('<comment>Are you sure you want to continue? [No]</comment>', false);

            if (!$this->getHelper('question')->ask($input, $output, $question)) {
                return Cli::RETURN_FAILURE;
            }
        }

        $imageDir = $this->getImageDir();
        $connection = $this->resourceConnection->getConnection('core_read');
        $mediaGalleryTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery');

        $directoryIterator = new RecursiveDirectoryIterator($imageDir);

        $imagesToKeep = $connection->fetchCol('SELECT value FROM ' . $mediaGalleryTable);

        foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
            // Directory guard
            if (is_dir($file)) {
                continue;
            }

            // Cached guard
            if ($this->isInCachePath($file) && !$deleteCacheAsWell) {
                continue;
            }

            $filePath = str_replace($imageDir, "", $file);
            // Filepath guard
            if (empty($filePath)) {
                continue;
            }

            $filePathWithoutCacheDir = preg_replace('#/cache_*/[a-z0-9]+(/[a-z0-9]/[a-z0-9]/.+?)#i', '$1', $filePath);
            if (in_array($filePathWithoutCacheDir, $imagesToKeep, true)) {
                continue;
            }

            // Placeholder guard
            if ($this->isInPlaceholderPath($file)) {
                continue;
            }

            if (in_array($filePath, $imagesToKeep, true)) {
                continue;
            }

            $fileSize += filesize($file);
            $countFiles++;

            if (!$isDryRun) {
                unlink($file);
                $output->writeln('## REMOVING: ' . $filePath . ' ##');
            } else {
                $output->writeln('## WOULD REMOVE: ' . $filePath . ' ##');
            }
        }

        $this->printResult($output, $isDryRun, $countFiles, $fileSize);

        return Cli::RETURN_SUCCESS;
    }

    private function getImageDir(): string
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $directory->getAbsolutePath() . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
    }

    private function isInCachePath(?string $file): bool
    {
        return strpos($file, '/cache') !== false;
    }

    private function isInPlaceholderPath(?string $file): bool
    {
        return strpos($file, '/placeholder') !== false;
    }

    private function printResult(OutputInterface $output, $isDryRun, int $countFiles, int $filesize): void
    {
        $actionName = 'Deleted';

        if ($isDryRun) {
            $actionName = 'Would delete';
        }

        $fileSizeInMB = number_format($filesize / 1024 / 1024, '2');

        $output->writeln("<info>{$actionName} {$countFiles} unused images. {$fileSizeInMB} MB</info>");
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME_EAV_MEDIA_REMOVE_UNUSED);
        $this->setDescription('Remove unused product images');

        $this->addOption(
            self::OPTION_INCLUDING_CACHE,
            'c',
            null,
            'Also clear the ./cache/* entries for the corresponding images'
        );
        $this->addOption(
            self::OPTION_DRY_RUN,
            'd',
            null,
            'Only process files and output what would be deleted, but don\'t delete anything'
        );
        $this->addOption(
            self::OPTION_FORCE,
            'f',
            null,
            'Prevent confirmation question and force execution. Option is required for non-interactive execution.'
        );
    }
}
