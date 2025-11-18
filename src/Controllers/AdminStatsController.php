<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdminStatsController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); return false; }
        return true;
    }

    public function today(): void
    {
        header('Content-Type: application/json');
        if (!$this->requireAdmin()) return;
        $date = trim((string)($_GET['date'] ?? date('Y-m-d')));
        $pdo = Database::pdo();
        $std = $pdo->prepare('SELECT COUNT(*) AS c FROM attendance WHERE attendance_date=? AND purpose=?');
        $std->execute([$date,'standard']);
        $coll = $pdo->prepare('SELECT COUNT(*) AS c FROM attendance WHERE attendance_date=? AND purpose=?');
        $coll->execute([$date,'collateral']);
        $uniq = $pdo->prepare('SELECT COUNT(DISTINCT participant_id) AS c FROM attendance WHERE attendance_date=?');
        $uniq->execute([$date]);
        echo json_encode([
            'date'=>$date,
            'standard'=>(int)$std->fetch()['c'],
            'collateral'=>(int)$coll->fetch()['c'],
            'unique'=>(int)$uniq->fetch()['c'],
        ]);
    }
}