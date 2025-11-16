<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../src/Services/Database.php';

$pdo = \App\Services\Database::pdo();
echo __DIR__ . '/../migrations/001_init.sql', "\n";
$sql = file_get_contents(__DIR__ . '/../migrations/001_init.sql');
echo (strpos($sql, 'VARCHAR(191)') !== false ? 'has191' : 'no191'), "\n";
foreach (array_filter(array_map('trim', preg_split('/;\s*\n/',$sql))) as $stmt) {
    if ($stmt !== '') {
        echo $stmt, "\n";
        $pdo->exec($stmt);
    }
}
echo 'migrated';