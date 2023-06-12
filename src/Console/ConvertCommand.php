<?php

namespace Overtrue\PHPOpenCC\Console;

use Overtrue\PHPOpenCC\OpenCC;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertCommand extends Command
{
    protected static $defaultName = 'convert';

    protected static $defaultDescription = 'Convert string between Simplified Chinese and Traditional Chinese';

    protected function configure(): void
    {
        $this
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('string', InputArgument::REQUIRED),
                    new InputArgument('strategy', InputArgument::REQUIRED),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(OpenCC::convert($input->getArgument('string'), $input->getArgument('strategy')));

        return Command::SUCCESS;
    }
}
