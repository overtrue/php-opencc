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

foreach ($dictionaries as $strategy) {
  echo "Testing with strategy: {$strategy}...";

  $start = microtime(true);

  foreach (array_chunk($chunks, $batchSize) as $chunk) {
    OpenCC::convert($chunk, $strategy);
  }

  $usage = round(microtime(true) - $start, 5) * 1000;
  $avgUsagePerChunk = $usage / count($chunks);
  $avgUsagePerChar = $usage / mb_strlen($input);
  $html[] = "<tr>
                <td><span class=\"text-teal-500\">{$strategy}</span></td>
                <td><span class=\"text-green-500\">{$usage} ms</span></td>
                <td><span class=\"text-green-500\">{$avgUsagePerChunk} ms</span></td>
                <td><span class=\"text-green-500\">{$avgUsagePerChar} ms</span></td>
             </tr>
        ";
  echo "Done.\n";
}

$html = implode("\n", $html);
$chunksCount = count($chunks);
$textLength = mb_strlen($input);

render(<<<"HTML"
    <div class="m-2">
        <div class="px-1 bg-green-600 text-white">PHP OpenCC</div>

        <div class="py-1">
            Converted <span class="text-teal-500">{$textLength}</span> chars(<span class="text-teal-500">{$chunksCount}</span> chunks/{$chunkSize} chars per chunk) with following strategies:
        </div>

        <table>
            <thead>
                <tr>
                    <th>Strategy</th>
                    <th>Time Usage</th>
                    <th>Avg Usage Per Chunk ({$chunkSize} chars)</th>
                    <th>Avg Usage Per Char</th>
                </tr>
            </thead>
            {$html}
        </table>
    </div>
HTML);
