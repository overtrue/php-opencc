<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Contracts\ConverterInterface;
use Overtrue\PHPOpenCC\Cache\DictionaryCache;

class OptimizedConverter implements ConverterInterface
{
    private DictionaryCache $cache;
    private bool $useCache;
    
    public function __construct(bool $useCache = true)
    {
        $this->useCache = $useCache;
        if ($useCache) {
            $this->cache = DictionaryCache::getInstance();
        }
    }
    
    public function convert(string|array $input, array $dictionaries): string|array
    {
        $isArray = is_array($input);
        
        foreach ($dictionaries as $dictionaryKey => $dictionary) {
            // 处理嵌套字典数组
            if (is_array(reset($dictionary))) {
                $dictionary = $this->mergeDictionaries($dictionary);
            }
            
            // 使用缓存的排序字典
            if ($this->useCache) {
                $cacheKey = is_string($dictionaryKey) ? $dictionaryKey : md5(serialize($dictionary));
                $dictionary = $this->cache->getSorted($cacheKey, $dictionary);
            } else {
                uksort($dictionary, function ($a, $b) {
                    return mb_strlen($b) <=> mb_strlen($a);
                });
            }
            
            // 批量转换优化
            if ($isArray) {
                $input = $this->batchConvert($input, $dictionary);
            } else {
                $input = $this->convertString($input, $dictionary);
            }
        }
        
        return $input;
    }
    
    private function mergeDictionaries(array $dictionaries): array
    {
        $merged = [];
        foreach ($dictionaries as $dict) {
            $merged = array_merge($merged, $dict);
        }
        return $merged;
    }
    
    private function batchConvert(array $inputs, array $dictionary): array
    {
        // 使用更高效的批量处理
        $result = [];
        foreach ($inputs as $key => $str) {
            $result[$key] = $this->convertString($str, $dictionary);
        }
        return $result;
    }
    
    private function convertString(string $str, array $dictionary): string
    {
        // 对于短字符串直接使用strtr
        if (mb_strlen($str) < 100) {
            return strtr($str, $dictionary);
        }
        
        // 对于长字符串使用分块处理
        $chunks = mb_str_split($str, 1000);
        $result = '';
        foreach ($chunks as $chunk) {
            $result .= strtr($chunk, $dictionary);
        }
        return $result;
    }
}