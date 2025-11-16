<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdminRegistrantsController
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
        $q = trim((string)($_GET['q'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        $sector = trim((string)($_GET['sector'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 20;
        $offset = ($page - 1) * $per;

        $where = [];
        $bind = [];
        if ($q !== '') { $where[] = '(first_name LIKE ? OR last_name LIKE ?)'; $bind[] = "%{$q}%"; $bind[] = "%{$q}%"; }
        if ($agency !== '') { $where[] = 'agency LIKE ?'; $bind[] = "%{$agency}%"; }
        if ($sector !== '') { $where[] = 'sector LIKE ?'; $bind[] = "%{$sector}%"; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, uuid, first_name, last_name, agency, sector, email FROM participants $sqlWhere ORDER BY id DESC LIMIT $per OFFSET $offset");
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();
        $total = (int)$pdo->query('SELECT FOUND_ROWS() AS t')->fetch()['t'];
        $pages = max(1, (int)ceil($total / $per));

        $data = compact('rows','page','pages','q','agency','sector','total');
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_registrants.php';
    }
}