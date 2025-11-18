<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdminParticipantsApiController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { header('Content-Type: application/json'); http_response_code(403); echo json_encode(['error'=>'forbidden']); return false; }
        return true;
    }

    public function search(): void
    {
        header('Content-Type: application/json');
        if (!$this->requireAdmin()) return;
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '' || mb_strlen($q) < 2) { echo json_encode(['results'=>[]]); return; }
        $pdo = Database::pdo();
        $like = "%{$q}%";
        $stmt = $pdo->prepare('SELECT uuid, first_name, last_name, agency FROM participants WHERE first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, " ", last_name) LIKE ? ORDER BY id DESC LIMIT 10');
        $stmt->execute([$like,$like,$like]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) { $out[] = ['uuid'=>$r['uuid'],'name'=>($r['first_name'].' '.$r['last_name']),'agency'=>$r['agency']]; }
        echo json_encode(['results'=>$out]);
    }
}