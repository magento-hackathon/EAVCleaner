<?php
namespace Hackaton\EAVCleaner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;



class CleanUpAttributesAndValuesWithoutParentCommand extends Command
{

    var $questionHelper;

    /**
     * Init command
     */
    protected function configure()
    {
        $this
            ->setName('eav:clean:attributes-and-values-without-parent')
            ->setDescription("
                Restore product's 'Use Default Value' if the non-global value is the same as the global value
            ")
            ->addOption('dry-run');
    }

    /**
     * Execute Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void;
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');

        if(!$isDryRun) {
            $output->writeln('WARNING: this is not a dry run. If you want to do a dry-run, add --dry-run.');
            $question = new ConfirmationQuestion('Are you sure you want to continue? [No] ', false);

            $this->questionHelper = $this->getHelper('question');
            if (!$this->questionHelper->ask($input, $output, $question)) {
                return;
            }
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\App\ResourceConnection $db */
        $resConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $db = $resConnection->getConnection();
        $types = array('varchar', 'int', 'decimal', 'text', 'datetime');
        $entityTypeCodes = array($db->getTableName('catalog_product'), $db->getTableName('catalog_category'), $db->getTableName('customer'), $db->getTableName('customer_address'));
        foreach($entityTypeCodes as $code) {
            $entityType = $objectManager->get('Magento\Eav\Model\Entity\Type')
                ->getCollection()
                ->addFieldToFilter('code', $code);
            $output->writeln("<info>Cleaning values for $code</info>");
            //removing attribute values
            foreach ($types as $type) {
                $eavTable = $db->getTableName('eav_attribute');
                $entityValueTable = $db->getTableName($code . '_entity_' . $type);
                $query = "SELECT * FROM $entityValueTable WHERE `attribute_id` not in(SELECT attribute_id"
                    . " FROM `$eavTable`)";
                $results = $db->fetchAll($query);
                $output->writeln("Clean up " . count($results) . " rows in $entityValueTable");

                if (!$isDryRun && count($results) > 0) {
                    $db->query("DELETE FROM $entityValueTable WHERE `attribute_id` not in(SELECT attribute_id"
                        . " FROM `$eavTable` where entity_type_id = " . $entityType->getEntityTypeId() . ")");
                }
            }

        }
    }

}