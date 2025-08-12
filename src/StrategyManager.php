<?php

namespace Overtrue\PHPOpenCC;

class StrategyManager
{
    private static array $strategies = [];
    private static array $aliases = [];
    private static bool $initialized = false;
    
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // 注册内置策略
        self::registerBuiltinStrategies();
        self::$initialized = true;
    }
    
    private static function registerBuiltinStrategies(): void
    {
        // 使用反射获取所有策略常量
        $reflection = new \ReflectionClass(Strategy::class);
        $constants = $reflection->getConstants();
        
        foreach ($constants as $name => $value) {
            // 注册完整名称
            if (str_contains($value, '_')) {
                self::$strategies[strtoupper($value)] = $value;
            }
            
            // 注册别名
            if (strlen($name) <= 5) { // 短别名如 S2T
                self::$aliases[strtoupper($name)] = $value;
            }
        }
    }
    
    public static function resolve(string $strategy): string
    {
        self::initialize();
        
        $strategy = strtoupper($strategy);
        
        // 先检查别名
        if (isset(self::$aliases[$strategy])) {
            return self::$aliases[$strategy];
        }
        
        // 再检查完整策略名
        if (isset(self::$strategies[$strategy])) {
            return self::$strategies[$strategy];
        }
        
        // 尝试从方法名转换（如 simplifiedToTraditional）
        $converted = self::convertMethodToStrategy($strategy);
        if ($converted && isset(self::$strategies[$converted])) {
            return self::$strategies[$converted];
        }
        
        throw new \InvalidArgumentException("Strategy [{$strategy}] is not registered.");
    }
    
    private static function convertMethodToStrategy(string $method): ?string
    {
        // 转换驼峰命名为下划线命名
        $strategy = preg_replace('/([a-z])([A-Z])/', '$1_$2', $method);
        return strtoupper($strategy);
    }
    
    public static function register(string $name, string $strategy): void
    {
        self::initialize();
        self::$strategies[strtoupper($name)] = $strategy;
    }
    
    public static function registerAlias(string $alias, string $strategy): void
    {
        self::initialize();
        self::$aliases[strtoupper($alias)] = $strategy;
    }
    
    public static function getAll(): array
    {
        self::initialize();
        return array_merge(self::$strategies, self::$aliases);
    }
    
    public static function exists(string $strategy): bool
    {
        self::initialize();
        $strategy = strtoupper($strategy);
        return isset(self::$strategies[$strategy]) || isset(self::$aliases[$strategy]);
    }
}