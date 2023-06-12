<?php

namespace Overtrue\PHPOpenCC;

class DictionarySets
{
    const SIMPLIFIED_TO_TRADITIONAL = ['STPhrases', 'STCharacters']; // S2T

    const SIMPLIFIED_TO_HONGKONG = ['STPhrases', 'STCharacters', 'HKVariants']; // S2HK

    const SIMPLIFIED_TO_JAPANESE = ['STPhrases', 'STCharacters', 'JPVariants']; // S2JP

    const SIMPLIFIED_TO_TAIWAN = ['STPhrases', 'STCharacters', 'TWVariants']; // S2TW

    const SIMPLIFIED_TO_TAIWAN_WITH_PHRASE = ['STPhrases', 'STCharacters', 'TWPhrases', 'TWVariants']; // S2TWP

    const HONGKONG_TO_TRADITIONAL = ['HKVariantsRevPhrases', 'HKVariantsRev']; // HK2T

    const HONGKONG_TO_SIMPLIFIED = ['HKVariantsRevPhrases', 'HKVariantsRev', 'TSPhrases', 'TSCharacters']; // HK2S

    const TAIWAN_TO_SIMPLIFIED = ['TWVariantsRevPhrases', 'TWVariantsRev', 'TSPhrases', 'TSCharacters']; // TW2S

    const TAIWAN_TO_TRADITIONAL = ['TWVariantsRevPhrases', 'TWVariantsRev']; // TW2T

    const TAIWAN_TO_SIMPLIFIED_WITH_PHRASE = ['TWPhrasesRev', 'TWVariantsRevPhrases', 'TWVariantsRev', 'TSPhrases', 'TSCharacters']; // TW2SP

    const TRADITIONAL_TO_HONGKONG = ['HKVariants']; // T2HK

    const TRADITIONAL_TO_SIMPLIFIED = ['TSPhrases', 'TSCharacters']; // T2S

    const TRADITIONAL_TO_TAIWAN = ['TWVariants']; // T2TW

    const TRADITIONAL_TO_JAPANESE = ['JPVariants']; // T2JP

    const JAPANESE_TO_TRADITIONAL = ['JPShinjitaiPhrases', 'JPShinjitaiCharacters', 'JPVariantsRev']; // JP2T

    const JAPANESE_TO_SIMPLIFIED = ['JPShinjitaiPhrases', 'JPShinjitaiCharacters', 'JPVariantsRev', 'TSPhrases', 'TSCharacters']; // JP2S

    const PARSED_DIR = __DIR__.'/../data/parsed';

    /**
     * @return array<string, array<string, string>>
     */
    public static function get(string $set): array
    {
        $set = strtoupper($set);

        if (! defined("self::{$set}")) {
            throw new \InvalidArgumentException("Dictionary set [{$set}] does not exists.");
        }

        $dictionaries = [];

        foreach (constant("self::{$set}") as $dictionary) {
            $dictionaries[$dictionary] = self::loadDictionary($dictionary);
        }

        return $dictionaries;
    }

    protected static function loadDictionary(string $dictionary)
    {
        $dictionary = sprintf('%s/%s.php', self::PARSED_DIR, $dictionary);

        if (! file_exists($dictionary)) {
            throw new \InvalidArgumentException("Dictionary [{$dictionary}] does not exists.");
        }

        return require $dictionary;
    }
}
