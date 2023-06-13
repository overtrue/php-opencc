<?php

use Overtrue\PHPOpenCC\OpenCC;
use Overtrue\PHPOpenCC\Strategy;
use PHPUnit\Framework\TestCase;

class OpenCCTest extends TestCase
{
    public function test_convert()
    {
        // default strategy is `simplified to traditional`
        $this->assertSame('漢字', OpenCC::convert('汉字'));

        // convert to taiwan
        $this->assertSame('服務器', OpenCC::convert('服务器', Strategy::SIMPLIFIED_TO_TAIWAN));

        // convert to taiwan with phrase
        $this->assertSame('伺服器', OpenCC::convert('服务器', Strategy::SIMPLIFIED_TO_TAIWAN_WITH_PHRASE));
    }

    public function test_magic_call()
    {
        $this->assertSame('漢字', OpenCC::s2t('汉字'));
        $this->assertSame('漢字', OpenCC::simplifiedToTraditional('汉字'));
    }

    public function test_strategies()
    {
        $files = glob(__DIR__.'/testcases/*.txt');

        foreach ($files as $file) {
            $lines = file($file);
            $lines = array_filter($lines);
            $strategy = strtoupper(basename($file, '.txt'));

            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }

                [$from, $to] = preg_split('/\s*->\s*/', trim($line));

                $this->assertSame($to, OpenCC::convert($from, $strategy), "Failed asserting that [{$from}] converts to [{$to}] in {$file}.");
            }
        }
    }
}
