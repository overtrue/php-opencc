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

    protected ?string $tempDir = null;

    protected function configure(): void
    {
        $this
            ->setDefinition(
                new InputDefinition([
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force rebuild even if local dictionaries are fresh.'),
                ])
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureDirectory(self::DICTIONARY_DIR);
        $this->ensureDirectory(self::PARSED_DIR);

        $file = self::DICTIONARY_DIR.'/STCharacters.txt';

        if (file_exists($file) && filemtime($file) > time() - 3600 * 24 && ! $input->getOption('force')) {
            $output->writeln('Data files are up to date.');

            return Command::SUCCESS;
        }

        try {
            $this->download($output);
            $this->extract($output);
            $this->copy($output);
            $this->parse($output);
        } finally {
            $this->cleanupTempDir();
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    public function download(OutputInterface $output): void
    {
        $output->writeln('Downloading data files...');

        $zipUrl = 'https://github.com/BYVoid/OpenCC/archive/refs/heads/master.zip';
        $target = $this->getZipPath();

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

        $readStream = fopen($zipUrl, 'rb', false, $context);
        if ($readStream === false) {
            $output->writeln('Download failed.');
            throw new \RuntimeException('Unable to download dictionary zip.');
        }

        $writeStream = fopen($target, 'wb');
        if ($writeStream === false) {
            fclose($readStream);
            throw new \RuntimeException("Unable to write zip file to [{$target}].");
        }

        try {
            $writtenBytes = stream_copy_to_stream($readStream, $writeStream);
            if ($writtenBytes === false || $writtenBytes === 0) {
                throw new \RuntimeException('Unable to download dictionary zip.');
            }
        } finally {
            fclose($readStream);
            fclose($writeStream);
        }

        $output->writeln('Done.');
    }

    public function copy(OutputInterface $output): void
    {
        $output->write('Copying data files...');
        $srcDir = $this->getExtractedDictionaryPath();
        $dstDir = self::DICTIONARY_DIR;

        if (! is_dir($srcDir)) {
            throw new \RuntimeException("Dictionary source directory does not exist: {$srcDir}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $dstDir.'/'.substr($item->getPathname(), strlen($srcDir) + 1);
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    $this->ensureDirectory($targetPath);
                }
            } else {
                $dir = dirname($targetPath);
                if (! is_dir($dir)) {
                    $this->ensureDirectory($dir);
                }
                if (! copy($item->getPathname(), $targetPath)) {
                    throw new \RuntimeException("Unable to copy [{$item->getPathname()}] to [{$targetPath}].");
                }
            }
        }
        $output->writeln('Done.');
    }

    public function extract(OutputInterface $output): void
    {
        $output->write('Extracting data files...');
        $zipPath = $this->getZipPath();
        $dest = $this->getExtractRootPath();
        $this->ensureDirectory($dest);

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
                if ($lines === false) {
                    throw new \RuntimeException("Unable to read dictionary file [{$txt}].");
                }
            } else {
                // merge files
                $content = '';
                foreach (self::MERGE_OUTPUT_MAP[$file] as $f) {
                    $source = sprintf('%s/%s.txt', self::DICTIONARY_DIR, $f);
                    $part = file_get_contents($source);
                    if ($part === false) {
                        throw new \RuntimeException("Unable to read dictionary file [{$source}].");
                    }

                    $content .= $part;
                }
                $lines = array_filter(explode("\n", $content));
            }

            $needReverse = in_array($file, self::REVERSED_FILES, true);

            $words = [];
            foreach ($lines as $line) {
                $parts = explode("\t", $line, 2);
                if (count($parts) !== 2) {
                    $output->writeln('Skip malformed line: '.$line);

                    continue;
                }

                [$from, $to] = $parts;
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

            if (file_put_contents($target, $content) === false) {
                throw new \RuntimeException("Unable to write parsed dictionary [{$target}].");
            }
        }

        $output->writeln('Done.');
    }

    protected function getZipPath(): string
    {
        return $this->getTempDir().'/opencc.zip';
    }

    protected function getExtractRootPath(): string
    {
        return $this->getTempDir().'/extracted';
    }

    protected function getExtractedDictionaryPath(): string
    {
        $candidates = glob($this->getExtractRootPath().'/OpenCC-*', GLOB_ONLYDIR);
        if ($candidates === false || empty($candidates)) {
            throw new \RuntimeException('Unable to locate extracted OpenCC directory.');
        }

        return $candidates[0].'/data/dictionary';
    }

    protected function getTempDir(): string
    {
        if ($this->tempDir === null) {
            $base = rtrim(sys_get_temp_dir(), '/');
            $this->tempDir = $base.'/'.uniqid('opencc-', true);
            $this->ensureDirectory($this->tempDir);
        }

        return $this->tempDir;
    }

    protected function cleanupTempDir(): void
    {
        if ($this->tempDir === null || ! is_dir($this->tempDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($this->tempDir);
        $this->tempDir = null;
    }

    protected function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new \RuntimeException("Unable to create directory [{$path}].");
        }
    }
}
