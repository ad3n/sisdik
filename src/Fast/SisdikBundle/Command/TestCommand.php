<?php

namespace Fast\SisdikBundle\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 
 * @author Ihsan Faisal
 */
class TestCommand extends Command
{
    protected function configure() {
        $this->setName('demo:greet')->setDescription('Greet someone')
                ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
                ->addOption('yell', null, InputOption::VALUE_NONE,
                        'If set, the task will yell in uppercase letters')
                ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Username', '')
                ->addOption('passwd', 'p', InputOption::VALUE_OPTIONAL, 'Password', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument('name');
        if ($input->getOption('user') == '') {
            $text = "username can't be empty";
        }
        if ($name) {
            $text = 'Hello ' . $name;
        } else {
            $text = 'Hello';
        }

        if ($input->getOption('yell')) {
            $text = strtoupper($text);
        }

        $output->writeln($text);
    }
}
