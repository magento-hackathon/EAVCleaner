<?php

namespace FIREGENTO\Magento\Command\Eav;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveUnusedAttributesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:attributes:remove-unused')
            ->setDescription('Remove unused attributes')
            ->addOption('dry-run')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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

            $deleted = 0;

            $attributes = \Mage::getResourceModel('catalog/product_attribute_collection')->addFieldToFilter('is_user_defined', 1);
            $eavAttributeTable = $this->_prefixTable('eav_attribute');
            $eavEntityAttributeTable = $this->_prefixTable('eav_entity_attribute');

            foreach($attributes as $attribute) {
                $table = $this->_prefixTable('catalog_product_entity_' . $attribute['backend_type']);

                /* Look for attributes that have no values set in products */
                $attributeValues = $db->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE attribute_id = ?', array($attribute['attribute_id']));
                if($attributeValues == 0) {
                    $output->writeln($attribute['attribute_code'] . ' has ' . $attributeValues . ' values; deleting attribute');
                    if(!$isDryRun) {
                        $db->query('DELETE FROM ' . $eavAttributeTable . ' WHERE attribute_code = ?', $attribute['attribute_code']);
                    }
                    $deleted++;
                } else {
                    /* Look for attributes that are not assigned to attribute sets */
                    $attributeGroups = $db->fetchOne('SELECT COUNT(*) FROM ' . $eavEntityAttributeTable . ' WHERE attribute_id = ?', array($attribute['attribute_id']));
                    if($attributeGroups == 0) {
                        $output->writeln($attribute['attribute_code'] . ' is not assigned to any attribute set; deleting attribute and its ' . $attributeValues . ' orphaned value(s)');
                        if(!$isDryRun) {
                            $db->query('DELETE FROM ' . $eavAttributeTable . ' WHERE attribute_code = ?', $attribute['attribute_code']);
                        }
                        $deleted++;
                    }
                }
            }

            $output->writeln('Deleted ' . $deleted . ' attributes.');
        }
    }
}
