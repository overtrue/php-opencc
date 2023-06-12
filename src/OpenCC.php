<?php

namespace Overtrue\PHPOpenCC;

class OpenCC
{
    public static function convert(string $input, string $strategy): string
    {
        $converter = new Converter();

        return $converter->convert($input, DictionarySets::get($strategy));
    }
}
