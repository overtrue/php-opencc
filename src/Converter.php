<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Contracts\ConverterInterface;

class Converter implements ConverterInterface
{
    public function convert(string|array $input, array $dictionaries): string|array
    {
        $isArray = is_array($input);

        foreach ($dictionaries as $dictionary) {
            // [['f1' => 't1'], ['f2' => 't2'], ...]
            if (is_array(reset($dictionary))) {
                $tmp = [];
                foreach ($dictionary as $dict) {
                    $tmp = array_merge($tmp, $dict);
                }
                $dictionary = $tmp;
            }

            uksort($dictionary, function ($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });

            if ($isArray) {
                $input = array_map(function ($str) use ($dictionary) {
                    return strtr($str, $dictionary);
                }, $input);
            } else {
                $input = strtr($input, $dictionary);
            }
        }

        return $input;
    }
}
