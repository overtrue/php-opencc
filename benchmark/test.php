<?php

include __DIR__ .'/../vendor/autoload.php';

use Overtrue\PHPOpenCC\OpenCC;
use Overtrue\PHPOpenCC\Dictionary;
use function Termwind\{render};

// 多少个字符为一组
$chunkSize = $argv[1] ?? 10;

// 一次转换多少组
$batchSize = $argv[2] ?? 1;

$input = file_get_contents(__DIR__ . '/input.txt');

$chunks = mb_str_split($input, $chunkSize);

$dictionaries = array_keys(Dictionary::SETS_MAP);

$output = [];
$totalMemoryStart = memory_get_usage(true);

foreach ($dictionaries as $strategy) {
  echo "Testing with strategy: {$strategy}...";

  $start = microtime(true);
  $memory = memory_get_usage(true);

  foreach (array_chunk($chunks, $batchSize) as $chunk) {
    OpenCC::convert($chunk, $strategy);
  }

  $memoryUsage = round((memory_get_usage(true) - $memory) / 1024 / 1024, 4); // mb
  $timeUsage = round(microtime(true) - $start, 5) * 1000;
  $avgUsagePerChunk = round($timeUsage / count($chunks), 5);
  $avgUsagePerChar = round($timeUsage / mb_strlen($input), 5);
  $html[] = "<tr>
                <td><span class=\"text-teal-500\">{$strategy}</span></td>
                <td><span class=\"text-green-500\">{$memoryUsage} mb</span></td>
                <td><span class=\"text-green-500\">{$timeUsage} ms</span></td>
                <td><span class=\"text-green-500\">{$avgUsagePerChunk} ms</span></td>
                <td><span class=\"text-green-500\">{$avgUsagePerChar} ms</span></td>
             </tr>
        ";
  echo "Done.\n";
}

$html = implode("\n", $html);
$chunksCount = count($chunks);
$textLength = mb_strlen($input);
$totalMemoryUsage = round((memory_get_usage(true) - $totalMemoryStart) / 1024 / 1024, 4); // mb
$peakMemoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 4); // mb

render(<<<"HTML"
    <div class="m-2">
        <div class="p-1">
          <span class="text-green-600 px-1">PHP OpenCC</span>
          <span class="px-2">benchmark test</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Strategy</th>
                    <th>Memory Usage</th>
                    <th>Time Usage</th>
                    <th>Avg Time Usage / chunk</th>
                    <th>Avg Time Usage / char</th>
                </tr>
            </thead>
            {$html}
        </table>

        <div class="py-1">
            <div>Total chars <span class="text-teal-500">{$textLength}</span></div>
            <div>Split into <span class="text-teal-500">{$chunksCount}</span> chunks</div>
            <div>Chunk size <span class="text-teal-500">{$chunkSize}</span> chars</div>
            <div>Batch size <span class="text-teal-500">{$batchSize}</span> chunks</div>
            <div>Memory usage <span class="text-teal-500">{$totalMemoryUsage} mb</span></div>
            <div>Peak memory usage <span class="text-teal-500">{$peakMemoryUsage} mb</span></div>
        </div>
    </div>
HTML);
