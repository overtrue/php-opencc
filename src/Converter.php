<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Contracts\ConverterInterface;

class Converter implements ConverterInterface
{
    public function convert(string|array $input, array $dictionaries): string|array
    {
        $isArray = is_array($input);

        foreach ($dictionaries as $dictionary) {
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
