<?php

namespace FIREGENTO\Magento\Command\Eav;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;


class CheckUnusedMediaCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:check:media')
            ->setDescription('Checks unused media ')
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
                $query         = 'SELECT value FROM ' . mysql_real_escape_string($mediaGallery) . ' WHERE value="' . mysql_real_escape_string($filePath) . '"';
                $value         = $coreRead->fetchOne($query);

                if($value == false){
                    $row = array();
                    $row[] = $filePath;
                    $table[] = $row;
                    $filesize += filesize($file);
                    $countFiles++;

                    #echo "## REMOVEING: " . $filePath . " ## \n";
                    #unlink($file);

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
