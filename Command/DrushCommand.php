<?php

namespace Bangpound\Bundle\DrupalBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DrushCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('drush')
            ->setDescription('Drush')
            ->ignoreValidationErrors()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = array_splice($_SERVER['argv'], 2);
        $input = new StringInput(implode(' ', $args));
        $commandline = 'bin/drush -r $PWD/web '. (string) $input;
        $cwd = $this->getContainer()->getParameter('kernel.root_dir') .'/../';

        $process = new Process($commandline, $cwd);

        $callback = function ($type, $data) use ($output) {
            $output->write($data, false, OutputInterface::OUTPUT_RAW);
        };

        $process->run($callback);
    }
}
