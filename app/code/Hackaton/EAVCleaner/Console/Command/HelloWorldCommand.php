<?php
namespace Hackaton\EAVCleaner\Console\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;




class HelloWorldCommand extends Command
{

    protected function configure()
    {
        $this->setName('eavcleaner:hello_world')->setDescription('Prints hello world.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello World!');
    }

}