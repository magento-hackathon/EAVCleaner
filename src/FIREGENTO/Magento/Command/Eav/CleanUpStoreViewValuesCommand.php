<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpStoreViewValuesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean-store-view-values')
            ->setDescription('Remove store view values that are the same as the default value');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->detectMagento($output);

        if ($this->initMagento()) {
            $resource = \Mage::getModel('core/resource');
            $db = $resource->getConnection('core_write');
            $counts = array();
            $i = 0;
            $tables = array('varchar', 'int', 'decimal', 'text', 'datetime');

            foreach ($tables as $table) {
                // Select all non-global values
                $rows = $db->fetchAll('SELECT * FROM catalog_product_entity_' . $table . ' WHERE store_id != 0');

                foreach ($rows as $row) {
                    // Select the global value if it's the same as the non-global value
                    $results = $db->fetchAll('SELECT * FROM catalog_product_entity_' . $table
                        . 'WHERE entity_type_id = ? AND attribute_id = ? AND store_id = ? AND entity_id = ? AND value = ?',
                        array($row['entity_type_id'], $row['attribute_id'], 0, $row['entity_id'], $row['value'])
                    );

                    if (count($results) > 0) {
                        foreach ($results as $result) {
                            // Remove the non-global value
                            $db->query('DELETE FROM catalog_product_entity_' . $table
                                . ' WHERE value_id = ?', $row['value_id']
                            );
                            $output->writeln('Deleting ' . $row['value_id'] . ' in favor of ' . $result['value_id']
                                . ' for attribute ' . $row['attribute_id'] . ' in table ' . $table
                            );
                            $counts[$row['attribute_id']]++;
                            $i++;
                        }
                    }

                    // Remove all non-global null values
                    $db->query('DELETE FROM catalog_product_entity_' . $table
                        . ' WHERE store_id = ? AND value IS NULL', array($row['store_id'])
                    );
                }
            }

            if (count($counts)) {
                $output->writeln('Done');
            }
            else {
                $output->writeln('There were no attribute values to clean up');
            }

        }
    }
}