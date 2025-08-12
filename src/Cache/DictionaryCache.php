<?php

namespace Overtrue\PHPOpenCC\Cache;

class DictionaryCache
{
    private static ?self $instance = null;
    private array $cache = [];
    private array $sortedCache = [];
    private int $maxCacheSize;
    private array $accessCount = [];
    
    private function __construct(int $maxCacheSize = 10)
    {
        $this->maxCacheSize = $maxCacheSize;
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get(string $key, callable $loader): array
    {
        if (!isset($this->cache[$key])) {
            // LRU淘汰策略
            if (count($this->cache) >= $this->maxCacheSize) {
                $this->evictLeastUsed();
            }
            
            $this->cache[$key] = $loader();
            $this->accessCount[$key] = 0;
        }
        
        $this->accessCount[$key]++;
        return $this->cache[$key];
    }
    
    public function getSorted(string $key, array $dictionary): array
    {
        if (!isset($this->sortedCache[$key])) {
            // 预排序并缓存
            uksort($dictionary, function ($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });
            $this->sortedCache[$key] = $dictionary;
        }
        
        return $this->sortedCache[$key];
    }
    
    private function evictLeastUsed(): void
    {
        asort($this->accessCount);
        $leastUsed = array_key_first($this->accessCount);
        
        unset($this->cache[$leastUsed]);
        unset($this->accessCount[$leastUsed]);
        unset($this->sortedCache[$leastUsed]);
    }
    
    public function clear(): void
    {
        $this->cache = [];
        $this->sortedCache = [];
        $this->accessCount = [];
    }
}