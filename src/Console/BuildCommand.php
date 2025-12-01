<?php

namespace Overtrue\PHPOpenCC\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'build',
    description: 'Build OpenCC data files.'
)]
class BuildCommand extends Command
{
    public const DICTIONARY_DIR = __DIR__.'/../../data/dictionary';

    public const PARSED_DIR = __DIR__.'/../../data/parsed';

    public const FILES = [
        'HKVariants',
        'HKVariantsRevPhrases',
        'JPShinjitaiCharacters',
        'JPShinjitaiPhrases',
        'JPVariants',
        'STCharacters',
        'STPhrases',
        'TSCharacters',
        'TSPhrases',
        'TWPhrasesIT',
        'TWPhrasesName',
        'TWPhrasesOther',
        'TWVariants',
        'TWVariantsRevPhrases',
    ];

    public const MERGE_OUTPUT_MAP = [
        'TWPhrases' => ['TWPhrasesIT', 'TWPhrasesName', 'TWPhrasesOther'],
        'TWVariantsRev' => ['TWVariants'],
        'TWPhrasesRev' => ['TWPhrasesIT', 'TWPhrasesName', 'TWPhrasesOther'],
        'HKVariantsRev' => ['HKVariants'],
        'JPVariantsRev' => ['JPVariants'],
    ];

    public const REVERSED_FILES = [
        'TWVariantsRev',
        'TWPhrasesRev',
        'HKVariantsRev',
        'JPVariantsRev',
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

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
    public function download(OutputInterface $output): void
    {
        $output->writeln('Downloading data files...');

        $zipUrl = 'https://github.com/BYVoid/OpenCC/archive/refs/heads/master.zip';
        $target = '/tmp/opencc.zip';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 60,
                'follow_location' => 1,
                'header' => [
                    'User-Agent: php-opencc-builder',
                ],
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents($zipUrl, false, $context);
        if ($data === false) {
            $output->writeln('Download failed.');
            throw new \RuntimeException('Unable to download dictionary zip.');
        }

        if (@file_put_contents($target, $data) === false) {
            throw new \RuntimeException('Unable to write zip file to /tmp.');
        }

        $output->writeln('Done.');
    }

    public function copy(OutputInterface $output): void
    {
        $output->write('Copying data files...');
        $srcDir = '/tmp/opencc/OpenCC-master/data/dictionary';
        $dstDir = self::DICTIONARY_DIR;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $dstDir.'/'.substr($item->getPathname(), strlen($srcDir) + 1);
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $dir = dirname($targetPath);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
        $output->writeln('Done.');
    }

    public function extract(OutputInterface $output): void
    {
        $output->write('Extracting data files...');
        $zipPath = '/tmp/opencc.zip';
        $dest = '/tmp/opencc';
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open downloaded zip.');
        }

        if ($zip->extractTo($dest) !== true) {
            $zip->close();
            throw new \RuntimeException('Unable to extract zip.');
        }
        $zip->close();
        $output->writeln('Done.');
    }

    public function parse(OutputInterface $output): void
    {
        $output->writeln('Parsing dictionary files...');

        $files = array_merge(self::FILES, array_keys(self::MERGE_OUTPUT_MAP));

        foreach ($files as $file) {
            $output->writeln('Parsing '.$file.'...');
            $txt = sprintf('%s/%s.txt', self::DICTIONARY_DIR, $file);
            if (file_exists($txt)) {
                $lines = file($txt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                // merge files
                $content = '';
                foreach (self::MERGE_OUTPUT_MAP[$file] as $f) {
                    $content .= file_get_contents(sprintf('%s/%s.txt', self::DICTIONARY_DIR, $f));
                }
                $lines = array_filter(explode("\n", $content));
            }

            $needReverse = in_array($file, self::REVERSED_FILES, true);

            $words = [];
            foreach ($lines as $line) {
                [$from, $to] = explode("\t", $line);
                $to = preg_split('/\s+/', $to, -1, PREG_SPLIT_NO_EMPTY)[0] ?? null;

                if (! $to) {
                    ! $to && $output->writeln('Skip '.$line);

                    continue;
                }

                if ($needReverse) {
                    [$from, $to] = [$to, $from];
                }

                // 会出现重复的词条，以最后一个为准
                $words[$from] = $to;
            }

            $content = sprintf('<?php return %s;', var_export($words, true));

            $target = sprintf('%s/%s.php', self::PARSED_DIR, $file);

            file_put_contents($target, $content);
        }

        $output->writeln('Done.');
    }
}
