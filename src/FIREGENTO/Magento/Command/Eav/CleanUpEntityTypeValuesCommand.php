<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class CleanUpEntityTypeValuesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean:entity-type-values')
            ->setDescription('Remove attribute values with wrong entity_type_id')
            ->addOption('dry-run')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );
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
            $entityTypeCodes = array('catalog_product', 'catalog_category', 'customer', 'customer_address');
            foreach($entityTypeCodes as $code) {
                $entityType = \Mage::getModel('eav/entity_type')->loadByCode($code);

                foreach ($types as $type) {
                    $entityValueTable = $code . '_entity_' . $type;
                    $query = "SELECT * FROM $entityValueTable WHERE `entity_type_id` <> " . $entityType->getEntityTypeId();
                    $results = $db->fetchAll($query);
                    $output->writeln("Clean up " . count($results) . " rows in $entityValueTable");
                    $this->verboseWriteLine($output, $query);
                    $this->printVerboseValueData($input, $output, $results);

                    if (!$isDryRun) {
                        $db->query("DELETE FROM $entityValueTable WHERE `entity_type_id` <> " . $entityType->getEntityTypeId());
                    }
                }
            }
        }
    }

    /**
     * Print verbose information about attribute values
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $results
     */
    protected function printVerboseValueData(InputInterface $input, OutputInterface $output, $results) {
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL && count($results) > 0) {
            $headers = array();
            $headers[] = 'value_id';
            $headers[] = 'entity_type_id';
            $headers[] = 'attribute_id';
            $headers[] = 'store_id';
            $headers[] = 'entity_id';
            $headers[] = 'value';

            $this->getHelper('table')
                ->setHeaders($headers)
                ->renderByFormat($output, $results, $input->getOption('format'));
        }
    }

    /**
     * @param OutputInterface $output
     * @param string|array $messages
     * @param int $type
     */
    protected function verboseWriteLine(OutputInterface $output, $messages, $type = OutputInterface::OUTPUT_NORMAL) {
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln($messages, $type);
        }
    }
}
