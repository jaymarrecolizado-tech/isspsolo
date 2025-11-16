<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$uuid = isset($_GET['uuid']) ? (string)$_GET['uuid'] : '';
if ($uuid === '' || !isset($_SESSION['qr_allowed'][$uuid])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . substr($uuid,0,2) . DIRECTORY_SEPARATOR . $uuid . '.png';
if (!is_file($path)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="qr.png"');
readfile($path);