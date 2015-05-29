<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSourceAndBackendModelCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:check-frontend-backend-model')
            ->setDescription('Check if the assigned sourcemodel, backendmodel still exist.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_output = $output;
        $arrFrontendResult = [];
        $arrBackendResult = [];
        $arrSourceResult = [];

        $this->detectMagento($output);

        if ($this->initMagento()) {

            $entityType = \Mage::getModel('eav/entity_type')->loadByCode('catalog_product');

            $attributeCollection = $entityType->getAttributeCollection()->getItems();
            foreach ($attributeCollection as $attribute) {
                $sourceModel = $attribute->getSourceModel();
                $frontendModel = $attribute->getFrontendModel();
                $backendModel = $attribute->getBackendModel();


                if(!empty($sourceModel)) {
                    //$output->writeln('mhh '.$sourceModel);
                    $tmp1 = \Mage::getModel($sourceModel);
                    if(empty($tmp1)) $arrSourceResult[] = $sourceModel;
                }


                if(!empty($frontendModel)) {
                    //$output->writeln('mhh2 '.$frontendModel);
                    $tmp2 = \Mage::getModel($frontendModel);
                    if(empty($tmp2)) $arrFrontendResult[] = $frontendModel;
                }

                if(!empty($backendModel)) {
                    //$output->writeln('mhh3 '.$backendModel);
                    $tmp3 = \Mage::getModel($backendModel);
                    if(empty($tmp3)) $arrBackendResult[] = $backendModel;
                }
            }

            if (count($arrFrontendResult) > 0) {
                foreach ($arrFrontendResult as $model) {
                    $output->writeln("<error>The frontend model ".$model." doesn't exist</error>");
                }
            }

            if (count($arrSourceResult) > 0) {
                foreach ($arrSourceResult as $model2) {
                    $output->writeln("<error>The source model ".$model2." doesn't exist</error>");
                }
            }

            if (count($arrBackendResult) > 0) {
                foreach ($arrBackendResult as $model3) {
                    $output->writeln("<error>The backend model ".$model3." doesn't exist</error>");
                }
            }


            if (count($arrBackendResult) == 0 && count($arrFrontendResult) == 0 && count($arrSourceResult) == 0){
                $output->writeln('There were no attribute values to clean up');
            }

        }
    }
}
