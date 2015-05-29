<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpScopeValuesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean-scope-values')
            ->setDescription('Clean up values of attributes that changed scope')
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

        $this->detectMagento($output);

        if ($this->initMagento()) {
            $resource = \Mage::getModel('core/resource');
            $db = $resource->getConnection('core_write');
            $types = array('varchar', 'int', 'decimal', 'text', 'datetime');

            foreach ($types as $type) {
                $prodTable = 'catalog_product_entity_' . $type;
                $attrTable = 'catalog_eav_attribute';

                $count = $db->fetchOne("SELECT COUNT(*)"
                    . " FROM $prodTable"
                    . " INNER JOIN $attrTable ON $attrTable.attribute_id = $prodTable.attribute_id"
                    . " WHERE $prodTable.store_id != 0 AND $attrTable.is_global = 1"
                );

                $output->writeln("Clean up $count rows in $prodTable");

                if (!$isDryRun) {
                    $db->query("DELETE $prodTable.* FROM $prodTable"
                        . " INNER JOIN $attrTable ON $attrTable.attribute_id = $prodTable.attribute_id"
                        . " WHERE $prodTable.store_id != 0 AND $attrTable.is_global = 1"
                    );
                }
            }
        }
    }
}
