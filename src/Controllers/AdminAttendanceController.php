<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdminAttendanceController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { header('Location: /?r=admin_login'); return false; }
        return true;
    }

    public function list(): void
    {
        if (!$this->requireAdmin()) return;
        $pdo = Database::pdo();
        $date = trim((string)($_GET['date'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        $name = trim((string)($_GET['name'] ?? ''));
        $purpose = trim((string)($_GET['purpose'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 20;
        $offset = ($page - 1) * $per;

        $where = [];
        $bind = [];
        if ($date !== '') { $where[] = 'a.attendance_date = ?'; $bind[] = $date; }
        if ($agency !== '') { $where[] = 'p.agency LIKE ?'; $bind[] = "%{$agency}%"; }
        if ($name !== '') { $where[] = '(p.first_name LIKE ? OR p.last_name LIKE ?)'; $bind[] = "%{$name}%"; $bind[] = "%{$name}%"; }
        if ($purpose !== '') { $where[] = 'a.purpose = ?'; $bind[] = $purpose; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS a.id, a.attendance_date, a.time_in, a.signature_path, a.purpose, p.first_name, p.last_name, p.agency, p.uuid FROM attendance a JOIN participants p ON p.id = a.participant_id $sqlWhere ORDER BY a.id DESC LIMIT $per OFFSET $offset");
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();
        $total = (int)$pdo->query('SELECT FOUND_ROWS() AS t')->fetch()['t'];
        $pages = max(1, (int)ceil($total / $per));

        $data = compact('rows','page','pages','date','agency','name','purpose','total');
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_attendance.php';
    }
}