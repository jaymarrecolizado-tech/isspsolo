<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';
spl_autoload_register(function($class){
    $prefix = 'App\\';
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . $rel . '.php';
    if (is_file($file)) require $file;
});

$pdo = \App\Services\Database::pdo();
$row = $pdo->query('SELECT uuid FROM participants ORDER BY id DESC LIMIT 1')->fetch();
if (!$row) { echo "no_participant\n"; exit; }
$uuid = (string)$row['uuid'];
$_SESSION['staff'] = 1;
$csrf = csrf_token();
$ctrl = new \App\Controllers\AttendanceController();
$sig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
$res1 = $ctrl->submitJsonForTest(['uuid'=>$uuid,'signature'=>$sig,'purpose'=>'standard'],$csrf);
$res2 = $ctrl->submitJsonForTest(['uuid'=>$uuid,'signature'=>$sig,'purpose'=>'collateral'],$csrf);
echo json_encode(['standard'=>$res1,'collateral'=>$res2]);