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
            ->setDescription('List attributes with wrong backend/frontend/source models')
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

        $sourceModelsAllowed = array('select', 'multiselect', 'hidden');

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
                        $error .= '<error>backend-model doesn\'t exist: ' . $attribute->getBackendModel() . '</error>';
                    }
                }

                if ($frontendModel != '') {
                    $testFrontendModel = \Mage::getModel($frontendModel);
                    if (!$testFrontendModel) {
                        $error .= '<error>frontend-model doesn\'t exist: ' . $attribute->getFrontendModel() . '</error>';
                    }}

                if ($sourceModel != '' && ! in_array($attribute->getFrontendInput(), $sourceModelsAllowed)) {
                    $additionalMessage = null;
                    if($attribute->getSourceModel() == 'eav/entity_attribute_source_boolean' && $attribute->getFrontendInput() != 'select') {
                        $additionalMessage = ' - the frontend input type should be select';
                    }
                    $error .= '<error>sourcemodel ' . $attribute->getSourceModel() . ' not allowed for frontend input type: ' . $attribute->getFrontendInput() . $additionalMessage . '</error>';
                }
                else if ($sourceModel != '') {
                    $testSourceModel = \Mage::getModel($sourceModel);
                    if (!$testSourceModel) {
                        $error .= '<error>source-model doesn\'t exist: ' . $attribute->getSourceModel() . '</error>';
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
            $headers[] = 'attribute_code';
            $headers[] = 'attribute_id';
            $headers[] = 'entity_type';
            $headers[] = 'label';
            $headers[] = 'error';

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
