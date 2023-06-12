<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Contracts\ConverterInterface;

class Converter implements ConverterInterface
{
    public function convert(string $string, array $dictionaries): string
    {
        foreach ($dictionaries as $dictionary) {
            uksort($dictionary, function ($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });

            $string = strtr($string, $dictionary);
        }

        return $string;
    }
}
