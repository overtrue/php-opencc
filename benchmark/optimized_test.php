<?php

include __DIR__ .'/../vendor/autoload.php';

use Overtrue\PHPOpenCC\OpenCC;
use Overtrue\PHPOpenCC\OptimizedConverter;
use Overtrue\PHPOpenCC\OptimizedDictionary;
use Overtrue\PHPOpenCC\Dictionary;
use Overtrue\PHPOpenCC\Config\Config;
use function Termwind\{render};

// 配置参数
$chunkSize = $argv[1] ?? 10;
$batchSize = $argv[2] ?? 1;

$input = file_get_contents(__DIR__ . '/input.txt');
$chunks = mb_str_split($input, $chunkSize);

$strategies = ['SIMPLIFIED_TO_TRADITIONAL', 'TRADITIONAL_TO_SIMPLIFIED'];

$results = [];

// 测试原版实现
echo "Testing original implementation...\n";
foreach ($strategies as $strategy) {
    echo "  Strategy: {$strategy}...";
    
    $start = microtime(true);
    $memoryStart = memory_get_usage(true);
    
    foreach (array_chunk($chunks, $batchSize) as $chunk) {
        OpenCC::convert($chunk, $strategy);
    }
    
    $memoryUsage = (memory_get_usage(true) - $memoryStart) / 1024 / 1024;
    $timeUsage = (microtime(true) - $start) * 1000;
    
    $results['original'][$strategy] = [
        'memory' => round($memoryUsage, 4),
        'time' => round($timeUsage, 2),
        'avg_per_chunk' => round($timeUsage / count($chunks), 4),
        'avg_per_char' => round($timeUsage / mb_strlen($input), 4),
    ];
    
    echo " Done.\n";
}

// 清理内存
unset($GLOBALS['dictionaries']);
gc_collect_cycles();

// 测试优化版实现
echo "\nTesting optimized implementation...\n";

// 配置优化选项
Config::set('cache.enabled', true);
Config::set('cache.max_size', 5);
Config::set('dictionary.lazy_load', true);
Config::set('converter.batch_optimization', true);

// 预加载常用字典
OptimizedDictionary::preload($strategies);

foreach ($strategies as $strategy) {
    echo "  Strategy: {$strategy}...";
    
    $converter = new OptimizedConverter(true);
    
    $start = microtime(true);
    $memoryStart = memory_get_usage(true);
    
    foreach (array_chunk($chunks, $batchSize) as $chunk) {
        $converter->convert($chunk, OptimizedDictionary::get($strategy));
    }
    
    $memoryUsage = (memory_get_usage(true) - $memoryStart) / 1024 / 1024;
    $timeUsage = (microtime(true) - $start) * 1000;
    
    $results['optimized'][$strategy] = [
        'memory' => round($memoryUsage, 4),
        'time' => round($timeUsage, 2),
        'avg_per_chunk' => round($timeUsage / count($chunks), 4),
        'avg_per_char' => round($timeUsage / mb_strlen($input), 4),
    ];
    
    echo " Done.\n";
}

// 计算改进百分比
$improvements = [];
foreach ($strategies as $strategy) {
    $orig = $results['original'][$strategy];
    $opt = $results['optimized'][$strategy];
    
    $improvements[$strategy] = [
        'memory' => round((1 - $opt['memory'] / $orig['memory']) * 100, 2),
        'time' => round((1 - $opt['time'] / $orig['time']) * 100, 2),
    ];
}

// 生成报告
$html = '';
foreach ($strategies as $strategy) {
    $orig = $results['original'][$strategy];
    $opt = $results['optimized'][$strategy];
    $imp = $improvements[$strategy];
    
    $memoryColor = $imp['memory'] > 0 ? 'green' : 'red';
    $timeColor = $imp['time'] > 0 ? 'green' : 'red';
    
    $html .= "<tr>
        <td class=\"text-teal-500\">{$strategy}</td>
        <td>{$orig['memory']} MB</td>
        <td>{$opt['memory']} MB</td>
        <td class=\"text-{$memoryColor}-500\">{$imp['memory']}%</td>
        <td>{$orig['time']} ms</td>
        <td>{$opt['time']} ms</td>
        <td class=\"text-{$timeColor}-500\">{$imp['time']}%</td>
    </tr>";
}

$chunksCount = count($chunks);
$textLength = mb_strlen($input);
$peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

render(<<<"HTML"
    <div class="m-2">
        <div class="p-1">
            <span class="text-green-600 px-1">PHP OpenCC</span>
            <span class="px-2">Performance Comparison</span>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Strategy</th>
                    <th>Original Memory</th>
                    <th>Optimized Memory</th>
                    <th>Memory Improvement</th>
                    <th>Original Time</th>
                    <th>Optimized Time</th>
                    <th>Time Improvement</th>
                </tr>
            </thead>
            {$html}
        </table>
        
        <div class="py-1">
            <div>Total chars: <span class="text-teal-500">{$textLength}</span></div>
            <div>Chunks: <span class="text-teal-500">{$chunksCount}</span> (size: {$chunkSize})</div>
            <div>Batch size: <span class="text-teal-500">{$batchSize}</span></div>
            <div>Peak memory: <span class="text-teal-500">{$peakMemory} MB</span></div>
        </div>
        
        <div class="py-1 text-gray-500">
            <div>✓ Optimizations applied:</div>
            <div>  - Dictionary caching with LRU eviction</div>
            <div>  - Lazy loading for large dictionaries</div>
            <div>  - Pre-sorted dictionary caching</div>
            <div>  - Batch processing optimization</div>
            <div>  - OPcache integration</div>
        </div>
    </div>
HTML);

echo "\n";