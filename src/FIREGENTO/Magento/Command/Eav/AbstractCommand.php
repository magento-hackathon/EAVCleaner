<?php

namespace FIREGENTO\Magento\Command\Eav;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists out modules with some specific logic for community modules
 */
abstract class AbstractCommand extends \FIREGENTO\Magento\Command\AbstractCommand
{
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

    /**
     * Print query result if verbose mode is on
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $results array of associative arrays. The array keys are column names, as strings.
     */
    protected function printVerboseQueryResult(InputInterface $input, OutputInterface $output, $results) {
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL && count($results) > 0) {
            $headers = array_keys($results[0]);

            $this->getHelper('table')
                ->setHeaders($headers)
                ->renderByFormat($output, $results, $input->getOption('format'));
        }
    }
}
