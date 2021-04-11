<?php
namespace FIREGENTO\Magento\Command\Eav;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RestoreUseDefaultValueConfigCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('eav:config:restore-use-default-value')
            ->setDescription("Restore config's 'Use Default Value' if the non-global value is the same as the global value")
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

        $removedConfigValues = 0;

        if ($this->initMagento()) {
            /** @var \Mage_Core_Model_Resource $resource */
            $resource = \Mage::getModel('core/resource');
            $db = $resource->getConnection('core_write');

            $configData = $db->fetchAll('SELECT DISTINCT path, value FROM ' . $this->_prefixTable('core_config_data') . ' WHERE scope_id = 0');
            foreach($configData as $config) {
                $count = $db->fetchOne('SELECT COUNT(*) FROM ' . $this->_prefixTable('core_config_data') .' WHERE path = ? AND BINARY value = ?', array($config['path'], $config['value']));
                if($count > 1) {
                    $output->writeln('Config path ' . $config['path'] . ' with value ' . $config['value']. ' has ' . $count . ' values; deleting non-default values');
                    if(!$isDryRun) {
                        $db->query('DELETE FROM ' . $this->_prefixTable('core_config_data') . ' WHERE path = ? AND BINARY value = ? AND scope_id != ?', array($config['path'], $config['value'], 0));
                    }
                    $removedConfigValues += ($count-1);
                }
            }

            $output->writeln('Removed ' . $removedConfigValues . ' values from core_config_data table.');
        }
    }
}