<?php

namespace FIREGENTO\Magento\Command;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output  */
    protected $_output;

    protected function _prefixTable($tbl)
    {
        $resource = \Mage::getSingleton('core/resource');
        return $resource->getTableName($tbl);
    }



    protected function _info($message)
    {
        $this->_output->writeln("<info>$message</info>");
        return $this;
    }
}
