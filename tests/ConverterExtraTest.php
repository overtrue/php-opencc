<?php

use Overtrue\PHPOpenCC\Converter;
use Overtrue\PHPOpenCC\OpenCC;
use Overtrue\PHPOpenCC\Strategy;
use PHPUnit\Framework\TestCase;

class ConverterExtraTest extends TestCase
{
    public function test_invalid_magic_call_should_throw_without_warning(): void
    {
        $warnings = [];
        set_error_handler(function (int $errno, string $errstr) use (&$warnings) {
            // Collect warnings/notices
            $warnings[] = [$errno, $errstr];

            return true; // prevent output
        });

        try {
            $this->expectException(\BadMethodCallException::class);
            // call a non-existing alias
            OpenCC::foo('bar');
        } finally {
            restore_error_handler();
        }

        // ensure no warnings were emitted during magic call resolution
        $this->assertSame([], $warnings);
    }

    public function test_converter_with_grouped_dictionaries_merges_and_replaces_longest_first(): void
    {
        $converter = new Converter;

        // Two groups of dictionaries; later keys should override earlier ones within a group
        $dictionaries = [
            [
                '服务' => '服務',
                '服务器' => '伺服器', // longer phrase should win on input "服务器"
                '器' => '器',
            ],
            [
                '程序' => '程式',
                '程序员' => '程式設計師', // longer phrase should win on input "程序员"
            ],
        ];

        $input = '服务器程序员';
        $output = $converter->convert($input, $dictionaries);

        $this->assertSame('伺服器程式設計師', $output);
    }

    public function test_convert_with_alias_strings_lower_and_uppercase(): void
    {
        $this->assertSame('伺服器', OpenCC::convert('服务器', 's2twp'));
        $this->assertSame('伺服器', OpenCC::convert('服务器', 'S2TWP'));
        $this->assertSame('伺服器', OpenCC::convert('服务器', Strategy::SIMPLIFIED_TO_TAIWAN_WITH_PHRASE));
    }

    public function test_iterable_input_is_supported_and_returns_array(): void
    {
        $converter = new Converter;
        $iterable = (function () {
            yield 'a' => '汉字';
            yield 'b' => '服务器';
        })();

        $result = $converter->convert($iterable, Overtrue\PHPOpenCC\Dictionary::get(Strategy::SIMPLIFIED_TO_TAIWAN_WITH_PHRASE));

        $this->assertSame(['a' => '漢字', 'b' => '伺服器'], $result);
    }
}
