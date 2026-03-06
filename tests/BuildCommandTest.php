<?php

use Overtrue\PHPOpenCC\Console\BuildCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class BuildCommandTest extends TestCase
{
    public function test_build_command_skips_when_data_is_fresh_and_force_is_not_set(): void
    {
        $file = BuildCommand::DICTIONARY_DIR.'/STCharacters.txt';
        $this->assertFileExists($file);
        touch($file, time());

        $command = new class extends BuildCommand
        {
            public bool $downloadCalled = false;

            public function download(OutputInterface $output): void
            {
                $this->downloadCalled = true;
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertFalse($command->downloadCalled);
        $this->assertStringContainsString('Data files are up to date.', $tester->getDisplay());
    }

    public function test_build_command_runs_when_force_is_set(): void
    {
        $file = BuildCommand::DICTIONARY_DIR.'/STCharacters.txt';
        $this->assertFileExists($file);
        touch($file, time());

        $command = new class extends BuildCommand
        {
            public bool $downloadCalled = false;

            public bool $extractCalled = false;

            public bool $copyCalled = false;

            public bool $parseCalled = false;

            public function download(OutputInterface $output): void
            {
                $this->downloadCalled = true;
            }

            public function extract(OutputInterface $output): void
            {
                $this->extractCalled = true;
            }

            public function copy(OutputInterface $output): void
            {
                $this->copyCalled = true;
            }

            public function parse(OutputInterface $output): void
            {
                $this->parseCalled = true;
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->downloadCalled);
        $this->assertTrue($command->extractCalled);
        $this->assertTrue($command->copyCalled);
        $this->assertTrue($command->parseCalled);
    }
}
