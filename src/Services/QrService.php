<?php
declare(strict_types=1);

namespace App\Services;

class QrService
{
    public static function generate(string $payload, string $uuid): string
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'qrcodes';
        $shard = substr($uuid, 0, 2);
        $dir = $base . DIRECTORY_SEPARATOR . $shard;
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $path = $dir . DIRECTORY_SEPARATOR . $uuid . '.png';
        $external = getenv('QR_EXTERNAL') === 'true';
        if (!$external && class_exists('Endroid\\QrCode\\QrCode')) {
            $qr = new \Endroid\QrCode\QrCode($payload);
            $qr->setSize(300);
            $qr->writeFile($path);
            return $path;
        }
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($payload);
        $img = file_get_contents($url);
        file_put_contents($path, $img);
        return $path;
    }
}
