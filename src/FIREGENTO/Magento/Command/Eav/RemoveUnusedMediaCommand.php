<?php

namespace FIREGENTO\Magento\Command\Eav;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveUnusedMediaCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:media:remove-unused')
            ->setDescription('Remove unused product images')
            ->addOption('dry-run')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;

    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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

        $this->detectMagento($output);
        if ($this->initMagento()) {
            $table = array();

            $imageDir = \Mage::getBaseDir('media') . DS  . 'catalog' . DS . 'product';
            $resource = \Mage::getSingleton('core/resource');
            $mediaGallery = $resource->getTableName('catalog_product_entity_media_gallery');
            $coreRead = $resource->getConnection('core_read');

            $i=0;

            $directoryIterator = new \RecursiveDirectoryIterator($imageDir);
            foreach( new \RecursiveIteratorIterator($directoryIterator) as $file) {

                if(strpos($file, "/cache") !== false || is_dir($file) ) {
                    continue;
                }

                $filePath      = str_replace($imageDir, "", $file);
                if(empty($filePath)) continue;

                $value         = $coreRead->fetchOne('SELECT value FROM ' . $mediaGallery . ' WHERE value = ?', array($filePath));

                if($value == false) {
                    $row = array();
                    $row[] = $filePath;
                    $table[] = $row;
                    $filesize += filesize($file);
                    $countFiles++;

                    echo '## REMOVING: ' . $filePath . ' ##';
                    if(!$isDryRun) {
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
                ->renderByFormat($output, $table, $input->getOption('format'));

            $output->writeln("Found " . number_format($filesize/1024/1024, '2') . " MB unused images in $countFiles files");
        }
    }
}
