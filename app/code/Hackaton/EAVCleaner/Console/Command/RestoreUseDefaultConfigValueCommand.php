<?php
namespace Hackaton\EAVCleaner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;



class RestoreUseDefaultConfigValueCommand extends Command
{

    var $questionHelper;

    /**
     * Init command
     */
    protected function configure()
    {
        $this
            ->setName('eav:config:restore-use-default-value')
            ->setDescription("
                Restore config's 'Use Default Value' if the non-global value is the same as the global value
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
      
        $removedConfigValues = 0;

        /** @var \Mage_Core_Model_Resource $resource */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\App\ResourceConnection $db */
        $resConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $db = $resConnection->getConnection();
        $configData = $db->fetchAll('SELECT DISTINCT path, value FROM ' . $db->getTableName('core_config_data') . ' WHERE scope_id = 0');
        foreach($configData as $config) {
            $count = $db->fetchOne('SELECT COUNT(*) FROM ' . $db->getTableName('core_config_data') .' WHERE path = ? AND value = ?', array($config['path'], $config['value']));
            if($count > 1) {
                $output->writeln('Config path ' . $config['path'] . ' with value ' . $config['value']. ' has ' . $count . ' values; deleting non-default values');
                if(!$isDryRun) {
                    $db->query('DELETE FROM ' . $db->getTableName('core_config_data') . ' WHERE path = ? AND value = ? AND scope_id != ?', array($config['path'], $config['value'], 0));
                }
                $removedConfigValues += ($count-1);
            }
        }
        $output->writeln('Removed ' . $removedConfigValues . ' values from core_config_data table.');

        
    }

}