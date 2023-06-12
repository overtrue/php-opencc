<?php

namespace Overtrue\PHPOpenCC\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';

    protected static $defaultDescription = 'Build OpenCC data files.';

    const DICTIONARY_DIR = __DIR__.'/../../data/dictionary';

    const PARSED_DIR = __DIR__.'/../../data/parsed';

    const FILES = [
        'HKVariants.txt',
        'HKVariantsRevPhrases.txt',
        'JPShinjitaiCharacters.txt',
        'JPShinjitaiPhrases.txt',
        'JPVariants.txt',
        'STCharacters.txt',
        'STPhrases.txt',
        'TSCharacters.txt',
        'TSPhrases.txt',
        'TWPhrasesIT.txt',
        'TWPhrasesName.txt',
        'TWPhrasesOther.txt',
        'TWVariants.txt',
        'TWVariantsRevPhrases.txt',
    ];

    const MERGE_OUTPUT_MAP = [
        'TWPhrases.txt' => ['TWPhrasesIT.txt', 'TWPhrasesName.txt', 'TWPhrasesOther.txt'],
        'TWVariantsRev.txt' => ['TWVariants.txt'],
        'TWPhrasesRev.txt' => ['TWPhrases.txt'],
        'HKVariantsRev.txt' => ['HKVariants.txt'],
        'JPVariantsRev.txt' => ['JPVariants.txt'],
    ];

    protected function configure(): void
    {
        $this
            ->setDefinition(
                new InputDefinition([
                    new InputOption('force', 'f', InputOption::VALUE_OPTIONAL),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! file_exists(self::DICTIONARY_DIR)) {
            mkdir(self::DICTIONARY_DIR, 0755, true);
        }

        if (! file_exists(self::PARSED_DIR)) {
            mkdir(self::PARSED_DIR, 0755, true);
        }

        $file = self::DICTIONARY_DIR.'/STCharacters.txt';

        if (file_exists($file) && filemtime($file) > time() - 3600 * 24 && ! $input->hasOption('force')) {
            $output->writeln('Data files are up to date.');

            return Command::SUCCESS;
        }

        $this->download($output);
        $this->extract($output);
        $this->copy($output);
        $this->parse($output);

        return Command::SUCCESS;
    }

    public function download(OutputInterface $output): void
    {
        $output->writeln('Downloading data files...');

        $zip = 'https://github.com/BYVoid/OpenCC/archive/refs/heads/master.zip';
        try {
            $process = Process::fromShellCommandline('curl -L -o /tmp/opencc.zip '.$zip);
            $process->setTty(Process::isTtySupported());
            $process->run();
        } catch (\Exception $e) {
            $output->writeln('Download failed.');
            throw $e;
        }
    }

    public function copy(OutputInterface $output): void
    {
        $output->writeln('Copying data files...');
        $process = Process::fromShellCommandline('cp -rf /tmp/opencc/OpenCC-master/data/dictionary/* '.self::DICTIONARY_DIR);
        $process->setTty(Process::isTtySupported());
        $process->run();
    }

    public function extract(OutputInterface $output): void
    {
        $output->writeln('Extracting data files...');
        $process = Process::fromShellCommandline('unzip -o /tmp/opencc.zip -d /tmp/opencc');
        $process->setTty(Process::isTtySupported());
        $process->run();
    }

    public function parse(OutputInterface $output): void
    {
        $output->writeln('Parsing dictionary files...');

        foreach (self::MERGE_OUTPUT_MAP as $file => $files) {
            $output->writeln('Merge '.$file.'...');
            $content = '';

            foreach ($files as $f) {
                $content .= file_get_contents(sprintf('%s/%s', self::DICTIONARY_DIR, $f));
            }

            file_put_contents(self::DICTIONARY_DIR.'/'.$file, $content);
        }

        $files = array_merge(self::FILES, array_keys(self::MERGE_OUTPUT_MAP));

        foreach ($files as $file) {
            $output->writeln('Parsing '.$file.'...');

            $lines = file(self::DICTIONARY_DIR.'/'.$file);

            $words = [];

            foreach ($lines as $line) {
                [$from, $to] = explode("\t", $line);
                $to = preg_split('/\s+/', $to, -1, PREG_SPLIT_NO_EMPTY)[0] ?? null;

                if (! $to) {
                    $output->writeln('Skip '.$line);

                    continue;
                }

                $words[$from] = $to;
            }

            $content = sprintf('<?php return %s;', var_export($words, true));

            $target = sprintf('%s/%s.php', self::PARSED_DIR, str_replace('.txt', '', $file));

            file_put_contents($target, $content);
        }
    }
}
