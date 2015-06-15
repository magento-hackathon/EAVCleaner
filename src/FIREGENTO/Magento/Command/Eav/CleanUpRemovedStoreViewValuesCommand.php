<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpRemovedStoreViewValuesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean:removed-store-view-values')
            ->setDescription('Clean up values from removed store views')
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
                $storeTable = 'core_store';

                $count = $db->fetchOne("SELECT COUNT(*)"
                    . " FROM $prodTable"
                    . " WHERE $prodTable.store_id NOT IN (SELECT $storeTable.store_id FROM $storeTable)"
                );

                $output->writeln("Clean up $count rows in $prodTable");
                
                if (!$isDryRun) {
                    $db->query("DELETE joker FROM $prodTable AS joker"
                        . " WHERE joker.store_id NOT IN (SELECT $storeTable.store_id FROM $storeTable)"
                    );
                }
            }
        }
    }
}
