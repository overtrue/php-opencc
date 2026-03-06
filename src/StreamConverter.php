<?php

namespace Overtrue\PHPOpenCC;

class StreamConverter
{
    /**
     * Convert text from input stream to output stream line by line.
     * Note: Line-based conversion may not handle phrase replacements that span newline boundaries.
     *
     * @param  resource  $inputStream  Readable stream resource
     * @param  resource  $outputStream  Writable stream resource
     */
    public static function convertStream($inputStream, $outputStream, string $strategy = Strategy::SIMPLIFIED_TO_TRADITIONAL): void
    {
        if (! is_resource($inputStream) || ! is_resource($outputStream)) {
            throw new \InvalidArgumentException('Input and output must be valid stream resources.');
        }

        $converter = new Converter;
        $dictionaries = Dictionary::get($strategy);

        while (true) {
            $line = fgets($inputStream);
            if ($line === false) {
                if (feof($inputStream)) {
                    break;
                }

                throw new \RuntimeException('Unable to read from input stream.');
            }

            $converted = $converter->convert($line, $dictionaries);
            if (! is_string($converted)) {
                throw new \RuntimeException('Converted stream content must be a string.');
            }

            self::writeAll($outputStream, $converted);
        }
    }

    /**
     * Convert a text file to another file using line-based streaming.
     */
    public static function convertFile(string $inputPath, string $outputPath, string $strategy = Strategy::SIMPLIFIED_TO_TRADITIONAL): void
    {
        $in = fopen($inputPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Unable to open input file: '.$inputPath);
        }
        $out = fopen($outputPath, 'wb');
        if ($out === false) {
            fclose($in);
            throw new \RuntimeException('Unable to open output file: '.$outputPath);
        }

        try {
            self::convertStream($in, $out, $strategy);
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /**
     * @param  resource  $stream
     */
    protected static function writeAll($stream, string $content): void
    {
        $writtenTotal = 0;
        $length = strlen($content);

        while ($writtenTotal < $length) {
            $written = fwrite($stream, substr($content, $writtenTotal));
            if ($written === false || $written === 0) {
                throw new \RuntimeException('Unable to write to output stream.');
            }

            $writtenTotal += $written;
        }
    }
}
