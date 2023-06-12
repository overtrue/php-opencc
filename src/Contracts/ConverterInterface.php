<?php

namespace Overtrue\PHPOpenCC\Contracts;

interface ConverterInterface
{
    /**
     * @param  array<array<string, string>>  $dictionaries
     */
    public function convert(string $string, array $dictionaries): string;
}
