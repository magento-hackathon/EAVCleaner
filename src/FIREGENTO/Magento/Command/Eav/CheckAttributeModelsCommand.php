<?php

namespace FIREGENTO\Magento\Command\Eav;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class CheckAttributeModelsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:check:models')
            ->setDescription('Checks attributes with obsole models ')
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
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $table = array();
            $attributesCollection = \Mage::getResourceModel('eav/entity_attribute_collection');
            $attributesCollection->setOrder('attribute_code', 'asc');
            foreach ($attributesCollection as $attribute) {
                $entityType = $this->_getEntityType($attribute);


                $backendModel = $attribute->getBackendModel();
                $frontendModel = $attribute->getFrontendModel();
                $sourceModel = $attribute->getSourceModel();

                $testBackendModel = $testFrontendModel = $testSourceModel = true;

                $error = '';

                if ($backendModel != '') {
                    $testBackendModel = \Mage::getModel($backendModel);
                    if (!$testBackendModel) {
                        $error .= 'backend: ' . $attribute->getBackendModel();
                    }
                }

                if ($frontendModel != '') {
                    $testFrontendModel = \Mage::getModel($frontendModel);
                    if (!$testFrontendModel) {
                        $error .= 'frontend: ' . $attribute->getFrontendModel();
                    }}

                if ($sourceModel != '') {
                    $testSourceModel = \Mage::getModel($sourceModel);
                    if (!$testSourceModel) {
                        $error .= 'source: ' . $attribute->getSourceModel();
                    }}


                if ($error != '')
                {
                    $row = array();
                    $row[] = $attribute->getAttributeCode();
                    $row[] = $attribute->getId();
                    $row[] = $entityType;
                    $row[] = $attribute->getFrontendLabel();
                    $row[] = $error;
                    $table[] = $row;
                }
            }

            $headers = array();
            $headers[] = 'code';
            $headers[] = 'id';
            $headers[] = 'entity_type';
            $headers[] = 'label';
            $headers[] = 'model';

            $this->getHelper('table')
                ->setHeaders($headers)
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }

    /**
     * @param $attribute
     * @return null|string
     */
    protected function _getEntityType($attribute)
    {
        $entityTypeCode = '';
        try {
            $entityType = $attribute->getEntityType();
            if ($entityType instanceof \Mage_Eav_Model_Entity_Type) {
                $entityTypeCode = $entityType->getEntityTypeCode();
            }
        } catch (\Exception $e) {
        }

        return $entityTypeCode;
    }
}
