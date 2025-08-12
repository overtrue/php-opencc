<?php

namespace Overtrue\PHPOpenCC\Config;

class Config
{
    private static array $config = [
        'cache' => [
            'enabled' => true,
            'max_size' => 10,
            'ttl' => 3600,
        ],
        'dictionary' => [
            'lazy_load' => true,
            'preload' => [],
            'format' => 'php', // php or json
        ],
        'converter' => [
            'chunk_size' => 1000,
            'batch_optimization' => true,
        ],
        'performance' => [
            'opcache' => true,
            'memory_limit' => '256M',
        ],
    ];
    
    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }
    
    public static function load(array $config): void
    {
        self::$config = array_merge_recursive(self::$config, $config);
    }
    
    public static function reset(): void
    {
        self::$config = [
            'cache' => [
                'enabled' => true,
                'max_size' => 10,
                'ttl' => 3600,
            ],
            'dictionary' => [
                'lazy_load' => true,
                'preload' => [],
                'format' => 'php',
            ],
            'converter' => [
                'chunk_size' => 1000,
                'batch_optimization' => true,
            ],
            'performance' => [
                'opcache' => true,
                'memory_limit' => '256M',
            ],
        ];
    }
}