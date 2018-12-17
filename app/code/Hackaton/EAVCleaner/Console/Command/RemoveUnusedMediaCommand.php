<?php
namespace Hackaton\EAVCleaner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\Filesystem\DirectoryList;

class RemoveUnusedMediaCommand extends Command
{
    /**
     * Init command
     */
    protected function configure()
    {
        $this
            ->setName('eav:media:remove-unused')
            ->setDescription('Remove unused product images')
            ->addOption('dry-run');
    }

    /**
     * Execute Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void;
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $filesize = 0;
        $countFiles = 0;
        $isDryRun = $input->getOption('dry-run');

        if(!$isDryRun) {
            $output->writeln('WARNING: this is not a dry run. If you want to do a dry-run, add --dry-run.');
            $question = new ConfirmationQuestion('Are you sure you want to continue? [No] ', false);
            $this->questionHelper = $this->getHelper('question');
            if (!$this->questionHelper->ask($input, $output, $question)) {
                return;
            }
        }

        $table = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $filesystem = $objectManager->get('Magento\Framework\Filesystem');
        $directory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $imageDir = $directory->getAbsolutePath() . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $mediaGallery = $resource->getConnection()->getTableName('catalog_product_entity_media_gallery');
        $coreRead = $resource->getConnection('core_read');
        $i = 0;
        $directoryIterator = new \RecursiveDirectoryIterator($imageDir);

        foreach (new \RecursiveIteratorIterator($directoryIterator) as $file) {

            if (strpos($file, "/cache") !== false || is_dir($file)) {
                continue;
            }

            $filePath = str_replace($imageDir, "", $file);
            if (empty($filePath)) continue;
            $value = $coreRead->fetchOne('SELECT value FROM ' . $mediaGallery . ' WHERE value = ?', array($filePath));
            if ($value == false) {
                $row = array();
                $row[] = $filePath;
                $table[] = $row;
                $filesize += filesize($file);
                $countFiles++;
                echo '## REMOVING: ' . $filePath . ' ##';
                if (!$isDryRun) {
                    unlink($file);
                } else {
                    echo ' -- DRY RUN';
                }
                echo PHP_EOL;
                $i++;
            }
        }

        $headers = array();
        $headers[] = 'filepath';
        $this->getHelper('table')
            ->setHeaders($headers)
            ->setRows($table)->render($output);
        $output->writeln("Found " . number_format($filesize / 1024 / 1024, '2') . " MB unused images in $countFiles files");
    }
}
