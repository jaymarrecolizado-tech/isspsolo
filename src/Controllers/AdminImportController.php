<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdminImportController
{
    private array $headers = [
        'Timestamp','Email Address','First Name','Middle Name','Last Name','Nickname','Sex','Sector','Agency','Designation','Office Email','Contact No'
    ];

    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { header('Location: /?r=admin_login'); return false; }
        return true;
    }

    public function form(): void
    {
        if (!$this->requireAdmin()) return;
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_import.php';
    }

    public function preview(): void
    {
        if (!$this->requireAdmin()) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ok = \App\Services\RateLimiter::allow('import_preview:'.$ip, 10, 60);
        if (!$ok) { http_response_code(429); echo 'Too Many Attempts'; return; }
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) { http_response_code(400); echo 'No file'; return; }
        $name = $_FILES['csv']['name'];
        $size = (int)$_FILES['csv']['size'];
        if ($size <= 0 || $size > 5_000_000) { http_response_code(400); echo 'Invalid size'; return; }
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') { http_response_code(400); echo 'Invalid type'; return; }

        $tmp = $_FILES['csv']['tmp_name'];
        $fh = fopen($tmp, 'r');
        $header = fgetcsv($fh);
        if (!$this->validateHeader($header)) { fclose($fh); $this->renderPreview([], ['Header mismatch']); return; }

        $rows = [];
        $errors = [];
        $limit = 200;
        $pdo = Database::pdo();
        $map = $this->headerMap($header);
        $count = 0;
        while (($data = fgetcsv($fh)) !== false) {
            $count++;
            $row = $this->rowFromMap($map, $data);
            $status = $this->detectStatus($pdo, $row);
            $rows[] = ['rownum'=>$count, 'row'=>$row, 'status'=>$status];
            if (count($rows) >= $limit) break;
        }
        fclose($fh);

        $storeDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'imports';
        if (!is_dir($storeDir)) mkdir($storeDir, 0775, true);
        $stored = $storeDir . DIRECTORY_SEPARATOR . time() . '_' . $_SESSION['admin_id'] . '.csv';
        move_uploaded_file($tmp, $stored);
        $_SESSION['import_file'] = $stored;
        $_SESSION['import_map'] = $map;
        $this->renderPreview($rows, $errors);
    }

    public function execute(): void
    {
        if (!$this->requireAdmin()) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ok = \App\Services\RateLimiter::allow('import_execute:'.$ip, 5, 60);
        if (!$ok) { http_response_code(429); echo 'Too Many Attempts'; return; }
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $strategy = $_POST['strategy'] ?? 'skip';
        $file = $_SESSION['import_file'] ?? '';
        $map = $_SESSION['import_map'] ?? null;
        if (!$file || !is_file($file) || !is_array($map)) { http_response_code(400); echo 'Missing preview state'; return; }

        $pdo = Database::pdo();
        $fh = fopen($file, 'r');
        $header = fgetcsv($fh);
        $batch = [];
        $changes = [];
        $inserted = 0; $updated = 0; $skipped = 0; $errored = 0;
        $chunk = 500;
        while (($data = fgetcsv($fh)) !== false) {
            $row = $this->rowFromMap($map, $data);
            $status = $this->detectStatus($pdo, $row);
            if ($status === 'Error') { $errored++; continue; }
            $match = $this->findMatch($pdo, $row);
            if (!$match) {
                $batch[] = ['action'=>'insert','row'=>$row];
            } else {
                if ($strategy === 'override_all' || ($strategy === 'override_duplicates' && ($status === 'Duplicate (email)' || $status === 'Duplicate (name+agency)'))) {
                    $batch[] = ['action'=>'update','row'=>$row,'match'=>$match];
                } else {
                    $skipped++;
                }
            }
            if (count($batch) >= $chunk) { [$i,$u,$c] = $this->runBatch($pdo, $batch); $inserted+=$i; $updated+=$u; $changes = array_merge($changes, $c); $batch = []; }
        }
        fclose($fh);
        if ($batch) { [$i,$u,$c] = $this->runBatch($pdo, $batch); $inserted+=$i; $updated+=$u; $changes = array_merge($changes, $c); }

        $summary = json_encode(['inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped,'errored'=>$errored,'changes'=>$changes], JSON_UNESCAPED_UNICODE);
        $log = $pdo->prepare('INSERT INTO import_logs (admin_id, file_name, action, duplicate_strategy, summary) VALUES (?,?,?,?,?)');
        $log->execute([(int)$_SESSION['admin_id'], basename($file), 'execute', $strategy, $summary]);

        unset($_SESSION['import_file'], $_SESSION['import_map']);
        header('Location: /?r=admin_import_history');
    }

    public function history(): void
    {
        if (!$this->requireAdmin()) return;
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT id, admin_id, file_name, action, duplicate_strategy, created_at FROM import_logs ORDER BY id DESC LIMIT 100')->fetchAll();
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_import_history.php';
    }

    private function validateHeader(?array $header): bool
    {
        if (!$header || count($header) < count($this->headers)) return false;
        foreach ($this->headers as $h) {
            if (!in_array($h, $header, true)) return false;
        }
        return true;
    }

    private function headerMap(array $header): array
    {
        $map = [];
        foreach ($this->headers as $h) { $map[$h] = array_search($h, $header, true); }
        return $map;
    }

    private function rowFromMap(array $map, array $data): array
    {
        $get = function(string $k) use ($map, $data) { $i = $map[$k]; return $i !== false && isset($data[$i]) ? trim((string)$data[$i]) : ''; };
        return [
            'timestamp' => $get('Timestamp'),
            'email' => $get('Email Address'),
            'first_name' => $get('First Name'),
            'middle_name' => $get('Middle Name'),
            'last_name' => $get('Last Name'),
            'nickname' => $get('Nickname'),
            'sex' => $get('Sex'),
            'sector' => $get('Sector'),
            'agency' => $get('Agency'),
            'designation' => $get('Designation'),
            'office_email' => $get('Office Email'),
            'contact_no' => $get('Contact No'),
        ];
    }

    private function detectStatus(\PDO $pdo, array $row): string
    {
        if ($row['first_name'] === '' || $row['last_name'] === '') return 'Error';
        if ($row['email'] !== '') {
            $s = $pdo->prepare('SELECT id FROM participants WHERE email = ?');
            $s->execute([$row['email']]);
            if ($s->fetch()) return 'Duplicate (email)';
        } else {
            $s = $pdo->prepare('SELECT id FROM participants WHERE first_name = ? AND last_name = ? AND agency = ?');
            $s->execute([$row['first_name'], $row['last_name'], ($row['agency']!==''?$row['agency']:null)]);
            if ($s->fetch()) return 'Duplicate (name+agency)';
        }
        return 'New';
    }

    private function findMatch(\PDO $pdo, array $row): ?array
    {
        if ($row['email'] !== '') {
            $s = $pdo->prepare('SELECT * FROM participants WHERE email = ?');
            $s->execute([$row['email']]);
            $m = $s->fetch();
            if ($m) return $m;
        }
        $s = $pdo->prepare('SELECT * FROM participants WHERE first_name = ? AND last_name = ? AND agency = ?');
        $s->execute([$row['first_name'], $row['last_name'], ($row['agency']!==''?$row['agency']:null)]);
        $m = $s->fetch();
        return $m ?: null;
    }

    private function runBatch(\PDO $pdo, array $batch): array
    {
        $pdo->beginTransaction();
        $inserted = 0; $updated = 0; $changes = [];
        foreach ($batch as $item) {
            if ($item['action'] === 'insert') {
                $uuid = \App\Services\Uuid::v4();
                $qrPath = \App\Services\QrService::generate('PART|' . $uuid, $uuid);
                $stmt = $pdo->prepare('INSERT INTO participants (uuid,email,first_name,middle_name,last_name,nickname,sex,sector,agency,designation,office_email,contact_no,qr_path,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $uuid,
                    $item['row']['email'] !== '' ? $item['row']['email'] : null,
                    $item['row']['first_name'],
                    $item['row']['middle_name'] !== '' ? $item['row']['middle_name'] : null,
                    $item['row']['last_name'],
                    $item['row']['nickname'] !== '' ? $item['row']['nickname'] : null,
                    $item['row']['sex'] !== '' ? $item['row']['sex'] : null,
                    $item['row']['sector'] !== '' ? $item['row']['sector'] : null,
                    $item['row']['agency'] !== '' ? $item['row']['agency'] : null,
                    $item['row']['designation'] !== '' ? $item['row']['designation'] : null,
                    $item['row']['office_email'] !== '' ? $item['row']['office_email'] : null,
                    $item['row']['contact_no'] !== '' ? $item['row']['contact_no'] : null,
                    $qrPath,
                    (int)$_SESSION['admin_id'],
                ]);
                $inserted++;
            } else {
                $old = $item['match'];
                $fields = ['first_name','middle_name','last_name','nickname','sex','sector','agency','designation','office_email','contact_no'];
                $changed = [];
                foreach ($fields as $f) { if ((string)$old[$f] !== (string)($item['row'][$f] !== '' ? $item['row'][$f] : null)) { $changed[$f] = ['old'=>$old[$f],'new'=>$item['row'][$f]]; } }
                $stmt = $pdo->prepare('UPDATE participants SET first_name=?, middle_name=?, last_name=?, nickname=?, sex=?, sector=?, agency=?, designation=?, office_email=?, contact_no=? WHERE id=?');
                $stmt->execute([
                    $item['row']['first_name'],
                    $item['row']['middle_name'] !== '' ? $item['row']['middle_name'] : null,
                    $item['row']['last_name'],
                    $item['row']['nickname'] !== '' ? $item['row']['nickname'] : null,
                    $item['row']['sex'] !== '' ? $item['row']['sex'] : null,
                    $item['row']['sector'] !== '' ? $item['row']['sector'] : null,
                    $item['row']['agency'] !== '' ? $item['row']['agency'] : null,
                    $item['row']['designation'] !== '' ? $item['row']['designation'] : null,
                    $item['row']['office_email'] !== '' ? $item['row']['office_email'] : null,
                    $item['row']['contact_no'] !== '' ? $item['row']['contact_no'] : null,
                    (int)$old['id'],
                ]);
                if ($changed) $changes[] = ['id'=>$old['id'],'fields'=>$changed];
                $updated++;
            }
        }
        $pdo->commit();
        return [$inserted,$updated,$changes];
    }
}