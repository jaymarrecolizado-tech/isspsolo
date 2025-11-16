<?php
declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    public static function allow(string $key, int $max, int $windowSec): bool
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $file = $dir . DIRECTORY_SEPARATOR . 'ratelimit.json';
        $now = time();
        $data = [];
        if (is_file($file)) {
            $json = file_get_contents($file);
            $data = $json ? (json_decode($json, true) ?: []) : [];
        }
        $entry = $data[$key] ?? ['count'=>0,'start'=>$now];
        if ($now - (int)$entry['start'] >= $windowSec) { $entry = ['count'=>0,'start'=>$now]; }
        $entry['count'] = (int)$entry['count'] + 1;
        $data[$key] = $entry;
        file_put_contents($file, json_encode($data));
        return $entry['count'] <= $max;
    }
}