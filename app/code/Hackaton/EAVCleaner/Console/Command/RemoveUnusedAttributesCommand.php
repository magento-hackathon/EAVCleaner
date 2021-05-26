<?php
namespace Hackaton\EAVCleaner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;

class RemoveUnusedAttributesCommand extends Command
{
    /**
     * Init command
     */
    protected function configure()
    {
        $this
            ->setName('eav:attributes:remove-unused')
            ->setDescription('Remove unused attributes')
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
        if (!$isDryRun) {
            $output->writeln('WARNING: this is not a dry run. If you want to do a dry-run, add --dry-run.');
            $question = new ConfirmationQuestion('Are you sure you want to continue? [No] ', false);
            $this->questionHelper = $this->getHelper('question');
            if (!$this->questionHelper->ask($input, $output, $question)) {
                return;
            }
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $db = $resource->getConnection('core_write');
        $deleted = 0;
        $attributes = $objectManager->get('Magento\Eav\Model\Entity\Attribute')
            ->getCollection()
            ->addFieldToFilter('is_user_defined', 1);
        $eavAttributeTable = $resource->getConnection()->getTableName('eav_attribute');
        $eavEntityAttributeTable = $resource->getConnection()->getTableName('eav_entity_attribute');
        foreach ($attributes as $attribute) {
            $table = $resource->getConnection()->getTableName('catalog_product_entity_' . $attribute['backend_type']);
            /* Look for attributes that have no values set in products */
            $attributeValues = $db->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE attribute_id = ?', array($attribute['attribute_id']));
            if ($attributeValues == 0) {
                $output->writeln($attribute['attribute_code'] . ' has ' . $attributeValues . ' values; deleting attribute');
                if (!$isDryRun) {
                    $db->query('DELETE FROM ' . $eavAttributeTable . ' WHERE attribute_code = ?', $attribute['attribute_code']);
                }
                $deleted++;
            } else {
                /* Look for attributes that are not assigned to attribute sets */
                $attributeGroups = $db->fetchOne('SELECT COUNT(*) FROM ' . $eavEntityAttributeTable . ' WHERE attribute_id = ?', array($attribute['attribute_id']));
                if ($attributeGroups == 0) {
                    $output->writeln($attribute['attribute_code'] . ' is not assigned to any attribute set; deleting attribute and its ' . $attributeValues . ' orphaned value(s)');
                    if (!$isDryRun) {
                        $db->query('DELETE FROM ' . $eavAttributeTable . ' WHERE attribute_code = ?', $attribute['attribute_code']);
                    }
                    $deleted++;
                }
            }
        }
        $output->writeln('Deleted ' . $deleted . ' attributes.');
    }
}
