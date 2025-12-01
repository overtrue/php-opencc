<?php

namespace Overtrue\PHPOpenCC;

class Dictionary
{
    public const SETS_MAP = [
        Strategy::SIMPLIFIED_TO_TRADITIONAL => [['STPhrases', 'STCharacters']], // S2T
        Strategy::SIMPLIFIED_TO_HONGKONG => [['STPhrases', 'STCharacters'], 'HKVariants'], // S2HK
        Strategy::SIMPLIFIED_TO_JAPANESE => [['STPhrases', 'STCharacters'], 'JPVariants'], // S2JP
        Strategy::SIMPLIFIED_TO_TAIWAN => [['STPhrases', 'STCharacters'], 'TWVariants'], // S2TW
        Strategy::SIMPLIFIED_TO_TAIWAN_WITH_PHRASE => [['STPhrases', 'STCharacters'], ['TWPhrases', 'TWVariants']], // S2TWP
        Strategy::HONGKONG_TO_TRADITIONAL => [['HKVariantsRevPhrases', 'HKVariantsRev']], // HK2T
        Strategy::HONGKONG_TO_SIMPLIFIED => [['HKVariantsRevPhrases', 'HKVariantsRev'], ['TSPhrases', 'TSCharacters']], // HK2S
        Strategy::TAIWAN_TO_SIMPLIFIED => [['TWVariantsRevPhrases', 'TWVariantsRev'], ['TSPhrases', 'TSCharacters']], // TW2S
        Strategy::TAIWAN_TO_TRADITIONAL => [['TWVariantsRevPhrases', 'TWVariantsRev']], // TW2T
        Strategy::TAIWAN_TO_SIMPLIFIED_WITH_PHRASE => [['TWPhrasesRev', 'TWVariantsRevPhrases', 'TWVariantsRev'], ['TSPhrases', 'TSCharacters']], // TW2SP
        Strategy::TRADITIONAL_TO_HONGKONG => ['HKVariants'], // T2HK
        Strategy::TRADITIONAL_TO_SIMPLIFIED => [['TSPhrases', 'TSCharacters']], // T2S
        Strategy::TRADITIONAL_TO_TAIWAN => ['TWVariants'], // T2TW
        Strategy::TRADITIONAL_TO_JAPANESE => ['JPVariants'], // T2JP
        Strategy::JAPANESE_TO_TRADITIONAL => [['JPShinjitaiPhrases', 'JPShinjitaiCharacters', 'JPVariantsRev']], // JP2T
        Strategy::JAPANESE_TO_SIMPLIFIED => [['JPShinjitaiPhrases', 'JPShinjitaiCharacters', 'JPVariantsRev'], ['TSPhrases', 'TSCharacters']], // JP2S
    ];

    public const PARSED_DIR = __DIR__.'/../data/parsed';

    protected static $dictionaries = [];

    /**
     * Cache raw dictionary files (filename => array map).
     *
     * @var array<string, array<string, string>>
     */
    protected static array $rawCache = [];

    /**
     * Return flattened, length-desc-sorted dictionaries per strategy.
     *
     * @return array<string, array<string, string>>|array<int, array<string, string>>
     */
    public static function get(string $set): array
    {
        $set = constant(Strategy::class.'::'.strtoupper($set));

        if (! array_key_exists($set, self::SETS_MAP)) {
            throw new \InvalidArgumentException("Dictionary set [{$set}] does not exists.");
        }

        if (array_key_exists($set, self::$dictionaries)) {
            return self::$dictionaries[$set];
        }

        $prepared = [];
        foreach (self::SETS_MAP[$set] as $dictionary) {
            if (is_array($dictionary)) {
                // load each member, merge with latter override earlier, then sort by key length desc once
                $loaded = [];
                foreach ($dictionary as $dict) {
                    $loaded[] = self::loadDictionary($dict);
                }
                $flattened = array_replace(...$loaded);
                $prepared[] = self::sortByKeyLengthDesc($flattened);

                continue;
            }
            $prepared[$dictionary] = self::sortByKeyLengthDesc(self::loadDictionary($dictionary));
        }

        self::$dictionaries[$set] = $prepared;

        return $prepared;
    }

    protected static function loadDictionary(string $dictionary)
    {
        $path = sprintf('%s/%s.php', self::PARSED_DIR, $dictionary);

        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Dictionary [{$path}] does not exists.");
        }

        // cache raw content
        if (! array_key_exists($path, self::$rawCache)) {
            self::$rawCache[$path] = require $path;
        }

        return self::$rawCache[$path];
    }

    /**
     * Sort mapping by key length (desc), so longer phrases take precedence with strtr.
     *
     * @param  array<string,string>  $map
     * @return array<string,string>
     */
    protected static function sortByKeyLengthDesc(array $map): array
    {
        // precompute lengths to avoid repeated mb_strlen in comparator
        $lengths = [];
        foreach (array_keys($map) as $key) {
            $lengths[$key] = mb_strlen($key);
        }

        uksort($map, function ($a, $b) use ($lengths) {
            return $lengths[$b] <=> $lengths[$a];
        });

        return $map;
    }
}
