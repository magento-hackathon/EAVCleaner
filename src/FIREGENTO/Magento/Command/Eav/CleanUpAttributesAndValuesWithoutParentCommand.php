<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CleanUpAttributesAndValuesWithoutParentCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:clean:attributes-and-values-without-parent')
            ->setDescription('Remove catalog_eav_attribute and attribute values which are missing parent entry in eav_attribute')
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
            $resource = \Mage::getModel('core/resource');
            $db = $resource->getConnection('core_write');
            $types = array('varchar', 'int', 'decimal', 'text', 'datetime');
            $entityTypeCodes = array($this->_prefixTable('catalog_product'), $this->_prefixTable('catalog_category'), $this->_prefixTable('customer'), $this->_prefixTable('customer_address'));
            foreach($entityTypeCodes as $code) {
                $entityType = \Mage::getModel('eav/entity_type')->loadByCode($code);
                $output->writeln("<info>Cleaning values for $code</info>");
                //removing attribute values
                foreach ($types as $type) {
                    $eavTable = $this->_prefixTable('eav_attribute');
                    $entityValueTable = $this->_prefixTable($code . '_entity_' . $type);
                    $query = "SELECT * FROM $entityValueTable WHERE `attribute_id` not in(SELECT attribute_id"
                        . " FROM `$eavTable` where entity_type_id = " . $entityType->getEntityTypeId() . ")";
                    $results = $db->fetchAll($query);
                    $output->writeln("Clean up " . count($results) . " rows in $entityValueTable");
                    $this->verboseWriteLine($output, $query);
                    $this->printVerboseQueryResult($input, $output, $results);

                    if (!$isDryRun && count($results) > 0) {
                        $db->query("DELETE FROM $entityValueTable WHERE `attribute_id` not in(SELECT attribute_id"
                        . " FROM `$eavTable` where entity_type_id = " . $entityType->getEntityTypeId() . ")");
                    }
                }


            }
            //cleaning catalog_eav_attribute
            $output->writeln("<info>Cleaning orphaned attributes from catalog_eav_attribute</info>");
            $query = "SELECT * FROM " . $this->_prefixTable('catalog_eav_attribute') . " WHERE `attribute_id` not in(SELECT attribute_id FROM `" . $this->_prefixTable('eav_attribute') . "`)";
            $results = $db->fetchAll($query);

            $output->writeln("Clean up " . count($results) . " rows in catalog_eav_attribute");
            $this->verboseWriteLine($output, $query);
            $this->printVerboseQueryResult($input, $output, $results);
            if (!$isDryRun && count($results) > 0) {
                $db->query("DELETE * FROM " . $this->_prefixTable('catalog_eav_attribute') . " WHERE `attribute_id` not in(SELECT attribute_id FROM `" . $this->_prefixTable('eav_attribute') . "`)");
            }

        }
    }
}
