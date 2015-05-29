<?php

namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpAttributesCommand extends AbstractCommand
{
    const PAGE_SIZE = 100;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean-001')
            ->setDescription('test 001');
    }

    /**
     * Testcase:
     * 1. add an attribute and assign the attribute to any attribute set
     * 2. create/edit a product with this attribute set
     * 3. remove the link between attribute set and attribute (via backend)
     * 4. the product values are still in the database
     * 5. solution run this script ;-)
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input  = $input;
        $this->_output = $output;
        $this->_info('Start Clean Eav Values');
        $this->detectMagento($output);
        if ($this->initMagento()) {

            //allowed attribute types
            $types = array('varchar', 'text', 'decimal', 'datetime', 'int');

            //attribute sets array
            $attributeSets = array();

            //user defined attribute ids
            $entityType = \Mage::getModel('eav/entity_type')->loadByCode('catalog_product');

            //connection for raw queries
            $connection = \Mage::getSingleton('core/resource')->getConnection('core_write');

            $attributeCollection = $entityType->getAttributeCollection()->addFilter('is_user_defined', '1')->getItems();
            $attrIds             = array();
            foreach ($attributeCollection as $attribute) {
                $attrIds[] = $attribute->getId();
            }
            $userDefined = implode(',', $attrIds);

            //product collection
            $collection  = \Mage::getModel('catalog/product')->getCollection();
            $entityTable = $collection->getTable(\Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getEntityTable());
            /**  if ($this->getArg('products') != 'all') {
                if ($ids = $this->_parseString($this->getArg('products'))) {
                    $collection->addAttributeToFilter('entity_id', array('in' => $ids));
                }
            }*/
            $collection->setPageSize(self::PAGE_SIZE);

            $pages       = $collection->getLastPageNumber();
            $currentPage = 1;

            //light product collection iterating
            while ($currentPage <= $pages) {
                $collection->setCurPage($currentPage);
                $collection->load();

                foreach ($collection->getItems() as $item) {
                    $product = \Mage::getModel('catalog/product')->load($item->getId());

                    //updating attribute ids for current products attribute set if necessary
                    if (!isset($attributeSets[$product->getAttributeSetId()])) {
                        $attributes = \Mage::getModel('catalog/product_attribute_api')->items($product->getAttributeSetId());
                        $attrIds    = array();
                        foreach ($attributes as $attribute) {
                            $attrIds[] = $attribute['attribute_id'];
                        }
                        $attributeSets[$product->getAttributeSetId()] = implode(',', $attrIds);
                    }

                    //deleting extra product attributes values for each backend type if the are not link to any
                    //attribute set and user defined
                    
                    foreach ($types as $type) {
                        $sql    = 'DELETE FROM `' . $entityTable . '_' . $type . '`
                                WHERE `entity_id` = ' . $product->getId() . '
                                    AND attribute_id NOT IN (' . $attributeSets[$product->getAttributeSetId()] . ')
                                    AND attribute_id IN (' . $userDefined . ')';
                        $result = $connection->query($sql);
                        if($result->rowCount() > 0) {
                            $output->write($result->rowCount() . ' rows were deleted.');
                        }
                    }
                }

                $currentPage++;
                $collection->clear();
            }

            $this->_info('Done!');
        }
    }

    /**
     * @param $diffValues
     * @param $output
     *
     * @return void
     */
    private function output($diffValues, $output)
    {
        foreach ($diffValues as $key => $value) {
            $output->write($key . '/');
            if (is_array($value)) $this->output($value, $output);
            else $output->writeln(' = ' . $value);
        }

    }



    /**
     * Parse string with id's and return array
     *
     * @param string $string
     * @return array
     */
    protected function _parseString($string)
    {
        $ids = array();
        if (!empty($string)) {
            $ids = explode(',', $string);
            $ids = array_map('trim', $ids);
        }
        return $ids;
    }

}
