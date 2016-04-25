<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RestoreUseDefaultValueAttributesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:attributes:restore-use-default-value')
            ->setDescription("Restore product's 'Use Default Value' if the non-global value is the same as the global value")
            ->addOption('dry-run');
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
            /** @var \Mage_Core_Model_Resource $resource */
            $resource = \Mage::getModel('core/resource');
            $db = $resource->getConnection('core_write');
            $counts = array();
            $i = 0;
            $tables = array('varchar', 'int', 'decimal', 'text', 'datetime');

            foreach ($tables as $table) {
                // Select all non-global values
                $fullTableName = $this->_prefixTable('catalog_product_entity_' . $table);
                $rows = $db->fetchAll('SELECT * FROM ' . $fullTableName . ' WHERE store_id != 0');

                foreach ($rows as $row) {
                    // Select the global value if it's the same as the non-global value
                    $results = $db->fetchAll('SELECT * FROM ' . $fullTableName
                        . ' WHERE entity_type_id = ? AND attribute_id = ? AND store_id = ? AND entity_id = ? AND value = ?',
                        array($row['entity_type_id'], $row['attribute_id'], 0, $row['entity_id'], $row['value'])
                    );

                    if (count($results) > 0) {
                        foreach ($results as $result) {
                            if (!$isDryRun) {
                                // Remove the non-global value
                                $db->query('DELETE FROM ' . $fullTableName . ' WHERE value_id = ?', $row['value_id']
                                );
                            }

                            $output->writeln('Deleting value ' . $row['value_id'] . ' "' . $row['value'] .'" in favor of ' . $result['value_id']
                                . ' for attribute ' . $row['attribute_id'] . ' in table ' . $fullTableName
                            );
                            $counts[$row['attribute_id']]++;
                            $i++;
                        }
                    }

                    $nullValues = $db->fetchOne('SELECT COUNT(*) FROM ' . $fullTableName
                        . ' WHERE store_id = ? AND value IS NULL', array($row['store_id'])
                    );

                    if (!$isDryRun && $nullValues > 0) {
                        $output->writeln("Deleting " . $nullValues ." NULL value(s) from " . $fullTableName);
                        // Remove all non-global null values
                        $db->query('DELETE FROM ' . $fullTableName
                            . ' WHERE store_id = ? AND value IS NULL', array($row['store_id'])
                        );
                    }
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