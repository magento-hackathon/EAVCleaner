<?php

namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CleanUpProductAttributeSetValuesCommand extends AbstractCommand
{
    const BATCH_SIZE = 1000;

    protected $_isDryRun;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean:product-attribute-set-values')
            ->setDescription('Remove extra attributes values if they are not linked to product attribute set')
            ->addOption('dry-run');
    }

    /**
     * Testcase:
     * 1. add an attribute and assign the attribute to any attribute set
     * 2. create/edit a product with this attribute set
     * 3. remove the link between attribute set and attribute (via backend)
     * 4. the product values are still in the database
     * 5. solution run this script ;-)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->_isDryRun = $input->getOption('dry-run');

        if (!$this->_isDryRun) {
            $output->writeln('WARNING: this is not a dry run. If you want to do a dry-run, add --dry-run.');
            $question = new ConfirmationQuestion('Are you sure you want to continue? [No] ', false);

            $this->questionHelper = $this->getHelper('question');
            if (!$this->questionHelper->ask($input, $output, $question)) {
                return;
            }
        }

        $this->_info('Start Cleaning Eav Values');
        $this->detectMagento($output);

        if ($this->initMagento()) {
            $this->cleanEAV();
        }
    }

    protected function cleanEAV()
    {
        $fixedRowsQty = 0;

        $entityTable = $this->_getTableName('catalog_product_entity');

        $allowedAttributeTypes = ['varchar', 'text', 'decimal', 'datetime', 'int'];
        $userDefinedAttributeIds = $this->_getUserDefinedAttributeIds();

        $attributeSetList = $this->_getAttributeSetList();

        $connection = $this->_isDryRun ? $this->_getWriteConnection() : $this->_getReadConnection();

        foreach ($attributeSetList as $attributeSet){

            $attributeSetId = $attributeSet['id'];
            $attributeSetName = $attributeSet['name'];

            $attributesIdsForAttributeSet = $this->_getAttributeIdsForAttributeSet($attributeSetId);
            $attributeIdsToClean = array_diff($userDefinedAttributeIds, $attributesIdsForAttributeSet);
            if (!count($attributeIdsToClean)){
                continue;
            }
            $attributeIdsToCleanFilter = implode(',', $attributeIdsToClean);

            $productIds = $this->_getProductIdsForAttributeSet($attributeSetId);
            $productQty = count($productIds);
            if (!$productQty){
                continue;
            }

            $this->_info('');
            $this->_info(
                sprintf('%s products found in "%s" attribute set.',
                    $productQty,
                    $attributeSetName
                )
            );
            $this->_info('Looking for messy data...');

            while (count($productIds)){

                $batchSize = self::BATCH_SIZE;
                $queueProductIds = array_splice($productIds, 0, $batchSize);
                $queueProductsFilter = implode(',', $queueProductIds);

                if ($productQty > $batchSize){
                    $this->_info(
                        sprintf(
                            "\tBatch of %s products (%s in queue)...",
                            count($queueProductIds),
                            count($productIds) ? count($productIds) : "nothing"
                        )
                    );
                }

                foreach ($allowedAttributeTypes as $attributeType) {

                    if ($this->_isDryRun) {
                        $sql = 'SELECT COUNT(*) FROM `';
                    } else {
                        $sql = 'DELETE FROM `';
                    }

                    $sql = $sql . $entityTable . '_' . $attributeType . '`
                                WHERE `entity_id` IN (' . $queueProductsFilter . ')
                                    AND attribute_id IN (' . $attributeIdsToCleanFilter . ')';

                    if ($this->_isDryRun) {
                        $rowsCount = $connection->fetchOne($sql);
                    } else {
                        $connection->beginTransaction();
                        $result = $connection->query($sql);
                        $rowsCount = $result->rowCount();
                        $connection->commit();
                    }

                    if ($rowsCount > 0) {
                        $fixedRowsQty += $rowsCount;
                    }
                }
            }
        }

        if ($fixedRowsQty > 0) {
            $this->_info(sprintf('We fix your Database %s Rows :-) Done!', $fixedRowsQty));
        } else {
            $this->_info('Done without any change!');
        }
    }

    protected function _getUserDefinedAttributeIds()
    {
        $attributeIds = [];
        $entityType = $this->_getEntityType();
        $attributeCollection = $entityType->getAttributeCollection()->addFilter('is_user_defined', '1')->getItems();

        foreach ($attributeCollection as $attribute) {
            $attributeIds[] = $attribute->getId();
        }
        return $attributeIds;
    }

    protected function _getAttributeSetList()
    {
        $entityType = $this->_getEntityType();
        $entityTypeId = $entityType->getEntityTypeId();

        $attributeSetTable = $this->_getTableName('eav_attribute_set');
        $connection = $this->_getReadConnection();
        $select = $connection->select();

        $select
            ->from(
                $attributeSetTable,
                [
                    'id' => 'attribute_set_id',
                    'name' => 'attribute_set_name',
                ]
            )
            ->where("entity_type_id = ?", $entityTypeId)
        ;

        return $connection->fetchAll($select);
    }

    protected function _getProductIdsForAttributeSet($attributeSetId)
    {
        $catalogProductEntityTable = $this->_getTableName('catalog_product_entity');

        $connection = $this->_getReadConnection();
        $select = $connection->select();

        $select
            ->from($catalogProductEntityTable, ['entity_id'])
            ->where('attribute_set_id = ?', $attributeSetId)
        ;

        return $connection->fetchCol($select);
    }

    protected function _getAttributeIdsForAttributeSet($attributeSetId)
    {
        $entityTypeId = $this->_getEntityTypeId();
        $eavEntityAttributeTable = $this->_getTableName('eav_entity_attribute');

        $connection = $this->_getReadConnection();
        $select = $connection->select();

        $select
            ->from($eavEntityAttributeTable, ['attribute_id'])
            ->where('entity_type_id = ?', $entityTypeId)
            ->where('attribute_set_id = ?', $attributeSetId)
        ;

        $attributeIds = $connection->fetchCol($select);
        return $attributeIds;
    }

    protected function _getEntityTypeId()
    {
        return $this->_getEntityType()->getEntityTypeId();
    }

    protected function _getEntityType()
    {
        $entityType = \Mage::getModel('eav/entity_type')->loadByCode('catalog_product');
        return $entityType;
    }

    protected function _getTableName($table)
    {
        return \Mage::getSingleton('core/resource')->getTableName($table);
    }

    protected function _getReadConnection()
    {
        $resource = \Mage::getSingleton('core/resource');
        return $resource->getConnection('core_read');
    }

    protected function _getWriteConnection()
    {
        $resource = \Mage::getSingleton('core/resource');
        return $resource->getConnection('core_write');
    }
}
