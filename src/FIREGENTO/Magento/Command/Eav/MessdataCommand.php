<?php

    namespace FIREGENTO\Magento\Command\Eav;

    use N98\Magento\Command\AbstractMagentoCommand;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class MessdataCommand extends AbstractCommand
    {
        protected function configure()
        {
            parent::configure();
            $this
                ->setName('eav:messdata')
                ->setDescription('mess up eav data - for testing purposes');
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
                $this->_addAttribute('messed_up_attribute_code');
                $this->_addAttributeValues();
                $this->_addWrongDefaultValues();
            }

        }

        protected function _addAttribute($attributeCode)
        {

            $data = array(
                'entity_type_id' =>  4,
                'attribute_code' => $attributeCode,
                'backend_type' => 'text',
                'frontend_input' => 'textarea',
                'frontend_label' => 'messed up description ',
                'is_required' => 0,
                'is_user_defined' => 1,
                'is_global' => 0,
                'note' => 'delete me',
            );

            $setup = new \Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->removeAttribute('4', $attributeCode);

            $eav = $this->_getEavAttributeResourceModel()->setData($data)->save();


        }


        protected function _addAttributeValues()
        {
            $storeId = 0;
            $productCollection = $this->getProductCollection();

            $i = 1;
            foreach ($productCollection as $product)
            {
                $product->addAttributeUpdate('messed_up_attribute_code', 'value ' . $i, $storeId);
                $i++;
            }
        }

        protected function _addWrongDefaultValues()
        {
            $attributeCodeToMessUp = 'messed_up_attribute_code_2';
            $this->_addAttribute($attributeCodeToMessUp);

            $productCollection = $this->getProductCollection();
            $product = $productCollection->getFirstItem();

            $this->_addAttributeToAttributeSet($product->getAttributeSetId(),$attributeCodeToMessUp);

            $product->addAttributeUpdate($attributeCodeToMessUp, 'value ', 0);
            foreach (\Mage::app()->getStores() as $store) {
                $product->addAttributeUpdate($attributeCodeToMessUp, 'value ', $store->getId());
            }
        }

        protected function _addAttributeToAttributeSet($attributeSetId, $attributeCode)
        {
            $setup = new \Mage_Eav_Model_Entity_Setup('core_setup');
            $attributeId = $setup->getAttributeId('catalog_product', $attributeCode);
            $attributeGroupId = $setup->getDefaultAttributeGroupId('catalog_product', $attributeSetId);
            $setup->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupId, $attributeId);
        }

        /**
         * @return \Mage_Core_Model_Abstract
         */
        protected function _getEavAttributeResourceModel()
        {
            return \Mage::getModel('catalog/resource_eav_attribute');
        }

        /**
         * @return \Mage_Core_Model_Abstract
         */
        protected function _getCatalogProductEntity()
        {
            return \Mage::getModel('catalog/product_entity_text');
        }

        /**
         * @param string $entityType
         * @param string $attributeCode
         *
         * @return \Mage_Eav_Model_Entity_Attribute_Abstract|false
         */
        protected function getAttribute($entityType, $attributeCode)
        {
            return \Mage::getModel('eav/config')->getAttribute($entityType, $attributeCode);
        }

        /**
         * @return \Mage_Customer_Model_Resource_Customer_Collection
         */
        protected function getProductCollection()
        {
            return $this->_getResourceModel('catalog/product_collection', 'Mage_Catalog_Model_Resource_Product_Collection');
        }


    }

