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
if (is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

$_SESSION['admin_id'] = 1;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', time() - 86400);
$ctrl = new \App\Controllers\ReportController();
$fields = ['id','name','agency','designation','email','contact_no','sex','registered_at'];
$pages = $ctrl->buildPdfPagesForTest('', $yesterday, $today, $fields, 'Attendance Report', 'Test', 5);
$i = 1;
foreach ($pages as $html) {
    $hasHeader = (strpos($html, '<thead>') !== false) ? 'YES' : 'NO';
    echo 'PAGE '.$i.' HEADER='.$hasHeader."\n";
    $i++;
}