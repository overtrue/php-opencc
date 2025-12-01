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
}
