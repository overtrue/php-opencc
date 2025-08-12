<?php

namespace Overtrue\PHPOpenCC;

use Overtrue\PHPOpenCC\Cache\DictionaryCache;

class OptimizedDictionary
{
    const SETS_MAP = Dictionary::SETS_MAP;
    const PARSED_DIR = Dictionary::PARSED_DIR;
    
    private static DictionaryCache $cache;
    private static bool $lazyLoad = true;
    
    public static function setLazyLoad(bool $lazy): void
    {
        self::$lazyLoad = $lazy;
    }
    
    /**
     * 获取字典数据，使用懒加载和缓存优化
     */
    public static function get(string $set): array
    {
        $set = constant(Strategy::class.'::'.strtoupper($set));
        
        if (!array_key_exists($set, self::SETS_MAP)) {
            throw new \InvalidArgumentException("Dictionary set [{$set}] does not exists.");
        }
        
        if (!isset(self::$cache)) {
            self::$cache = DictionaryCache::getInstance();
        }
        
        return self::$cache->get($set, function() use ($set) {
            return self::loadDictionaries($set);
        });
    }
    
    private static function loadDictionaries(string $set): array
    {
        $dictionaries = [];
        
        foreach (self::SETS_MAP[$set] as $dictionary) {
            if (is_array($dictionary)) {
                if (self::$lazyLoad) {
                    // 懒加载：返回加载器而不是立即加载
                    $dictionaries[] = new LazyDictionaryLoader($dictionary);
                } else {
                    $group = [];
                    foreach ($dictionary as $dict) {
                        $group[$dict] = self::loadDictionaryFile($dict);
                    }
                    $dictionaries[] = $group;
                }
                continue;
            }
            
            if (self::$lazyLoad) {
                $dictionaries[$dictionary] = new LazyDictionaryLoader([$dictionary]);
            } else {
                $dictionaries[$dictionary] = self::loadDictionaryFile($dictionary);
            }
        }
        
        return $dictionaries;
    }
    
    private static function loadDictionaryFile(string $dictionary): array
    {
        $filePath = sprintf('%s/%s.php', self::PARSED_DIR, $dictionary);
        
        if (!file_exists($filePath)) {
            // 尝试使用JSON格式（更高效）
            $jsonPath = sprintf('%s/%s.json', self::PARSED_DIR, $dictionary);
            if (file_exists($jsonPath)) {
                return json_decode(file_get_contents($jsonPath), true);
            }
            
            throw new \InvalidArgumentException("Dictionary [{$dictionary}] does not exists.");
        }
        
        // 使用opcache优化
        if (function_exists('opcache_is_script_cached') && !opcache_is_script_cached($filePath)) {
            opcache_compile_file($filePath);
        }
        
        return require $filePath;
    }
    
    /**
     * 预加载常用字典到内存
     */
    public static function preload(array $strategies = []): void
    {
        $strategies = $strategies ?: [
            Strategy::SIMPLIFIED_TO_TRADITIONAL,
            Strategy::TRADITIONAL_TO_SIMPLIFIED,
        ];
        
        foreach ($strategies as $strategy) {
            self::get($strategy);
        }
    }
    
    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        if (isset(self::$cache)) {
            self::$cache->clear();
        }
    }
}

/**
 * 懒加载字典加载器
 */
class LazyDictionaryLoader implements \ArrayAccess
{
    private array $dictNames;
    private ?array $data = null;
    
    public function __construct(array $dictNames)
    {
        $this->dictNames = $dictNames;
    }
    
    private function load(): void
    {
        if ($this->data === null) {
            $this->data = [];
            foreach ($this->dictNames as $dict) {
                $filePath = sprintf('%s/%s.php', OptimizedDictionary::PARSED_DIR, $dict);
                $this->data[$dict] = require $filePath;
            }
        }
    }
    
    public function offsetExists($offset): bool
    {
        $this->load();
        return isset($this->data[$offset]);
    }
    
    public function offsetGet($offset): mixed
    {
        $this->load();
        return $this->data[$offset];
    }
    
    public function offsetSet($offset, $value): void
    {
        $this->load();
        $this->data[$offset] = $value;
    }
    
    public function offsetUnset($offset): void
    {
        $this->load();
        unset($this->data[$offset]);
    }
}