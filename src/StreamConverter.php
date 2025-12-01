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

        while (($line = fgets($inputStream)) !== false) {
            $converted = $converter->convert($line, $dictionaries);
            fwrite($outputStream, $converted);
        }
    }

    /**
     * Convert a text file to another file using line-based streaming.
     */
    public static function convertFile(string $inputPath, string $outputPath, string $strategy = Strategy::SIMPLIFIED_TO_TRADITIONAL): void
    {
        $in = @fopen($inputPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Unable to open input file: '.$inputPath);
        }
        $out = @fopen($outputPath, 'wb');
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
}
