<?php

use Overtrue\PHPOpenCC\Strategy;
use Overtrue\PHPOpenCC\StreamConverter;
use PHPUnit\Framework\TestCase;

class StreamConverterTest extends TestCase
{
    public function test_convert_stream_line_by_line(): void
    {
        $in = fopen('php://memory', 'rb+');
        fwrite($in, "汉字\n服务器\n");
        rewind($in);

        $out = fopen('php://memory', 'rb+');

        StreamConverter::convertStream($in, $out, Strategy::SIMPLIFIED_TO_TAIWAN_WITH_PHRASE);

        rewind($out);
        $this->assertSame("漢字\n伺服器\n", stream_get_contents($out));

        fclose($in);
        fclose($out);
    }

    public function test_convert_stream_should_throw_when_output_stream_is_not_writable(): void
    {
        $in = fopen('php://memory', 'rb+');
        fwrite($in, "汉字\n");
        rewind($in);

        $out = fopen(__FILE__, 'rb');

        set_error_handler(static function (): bool {
            // Suppress fwrite warning; StreamConverter should turn this into RuntimeException.
            return true;
        });

        try {
            $this->expectException(\RuntimeException::class);
            StreamConverter::convertStream($in, $out, Strategy::SIMPLIFIED_TO_TRADITIONAL);
        } finally {
            restore_error_handler();
            fclose($in);
            fclose($out);
        }
    }
}
