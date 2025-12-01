<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Contracts\ConverterInterface;

class Converter implements ConverterInterface
{
    public function convert(string|array|\Traversable $input, array $dictionaries): string|array
    {
        $isArray = is_array($input);
        $isIterable = $input instanceof \Traversable;

        foreach ($dictionaries as $dictionary) {
            if ($isArray) {
                $input = array_map(function ($str) use ($dictionary) {
                    return strtr($str, $dictionary);
                }, $input);

                continue;
            }

            if ($isIterable) {
                $result = [];
                foreach ($input as $key => $value) {
                    $result[$key] = strtr($value, $dictionary);
                }
                $input = $result;
                $isArray = true; // downstream rounds continue as array

                continue;
            }

            $input = strtr($input, $dictionary);
        }

        return $input;
    }
}
