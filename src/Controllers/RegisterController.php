<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use App\Services\Uuid;
use App\Services\QrService;
use App\Services\Mailer;

class RegisterController
{
    public function show(): void
    {
        $pdo = \App\Services\Database::pdo();
        $agencies = $pdo->query("SELECT DISTINCT agency FROM participants WHERE agency IS NOT NULL AND agency <> '' ORDER BY agency ASC LIMIT 500")->fetchAll();
        $designations = $pdo->query("SELECT DISTINCT designation FROM participants WHERE designation IS NOT NULL AND designation <> '' ORDER BY designation ASC LIMIT 500")->fetchAll();
        $sexes = ['Female','Male','Other'];
        $sectors = ['National Government Agency','Local Government Unit','Provincial Government Unit','GOCCs','State Universities and Colleges','Water District'];
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register.php';
    }

    public function success(): void
    {
        $uuid = isset($_GET['uuid']) ? (string)$_GET['uuid'] : '';
        if ($uuid === '') { http_response_code(400); echo 'Missing UUID'; return; }
        $pdo = \App\Services\Database::pdo();
        $stmt = $pdo->prepare('SELECT uuid, first_name, last_name FROM participants WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo 'Not Found'; return; }
        $participant = [
            'uuid' => $row['uuid'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
        ];
        if (!isset($_SESSION['qr_allowed'])) $_SESSION['qr_allowed'] = [];
        $_SESSION['qr_allowed'][$uuid] = true;
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register_success.php';
    }

    public function submit(): void
    {
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) {
            http_response_code(400);
            echo 'Invalid CSRF';
            return;
        }

        $first = trim((string)($_POST['first_name'] ?? ''));
        $last = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $agencySel = trim((string)($_POST['agency_select'] ?? ''));
        $agencyOther = trim((string)($_POST['agency_other'] ?? ''));
        $agency = $agencySel === 'other' ? $agencyOther : $agencySel;
        $sector = trim((string)($_POST['sector'] ?? ''));
        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $sex = trim((string)($_POST['sex'] ?? ''));
        $designationSel = trim((string)($_POST['designation_select'] ?? ''));
        $designationOther = trim((string)($_POST['designation_other'] ?? ''));
        $designation = $designationSel === 'other' ? $designationOther : $designationSel;
        $office_email = trim((string)($_POST['office_email'] ?? ''));
        $contact_no = trim((string)($_POST['contact_no'] ?? ''));

        if ($first === '' || $last === '' || $sector === '') {
            http_response_code(422);
            echo 'Missing required fields';
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo 'Invalid email';
            return;
        }

        $pdo = Database::pdo();
        try {
            if ($email !== '') {
                $chk = $pdo->prepare('SELECT id FROM participants WHERE email = ?');
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    http_response_code(409);
                    $error = 'Email already registered';
                    require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register_error.php';
                    return;
                }
            }
            $attempts = 0;
            $max = 5;
            $uuid = '';
            while ($attempts < $max) {
                $uuid = Uuid::v4();
                try {
                    $stmt = $pdo->prepare('INSERT INTO participants (uuid,email,first_name,middle_name,last_name,nickname,sex,sector,agency,designation,office_email,contact_no,qr_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $uuid,
                        $email !== '' ? $email : null,
                        $first,
                        $_POST['middle_name'] ?? null,
                        $last,
                        $nickname !== '' ? $nickname : null,
                        $sex !== '' ? $sex : null,
                        $sector !== '' ? $sector : null,
                        $agency !== '' ? $agency : null,
                        $designation !== '' ? $designation : null,
                        $office_email !== '' ? $office_email : null,
                        $contact_no !== '' ? $contact_no : null,
                        null,
                    ]);
                    break;
                } catch (\PDOException $e) {
                    $attempts++;
                    if ($attempts >= $max) throw $e;
                }
            }
            $payload = 'PART|' . $uuid;
            $qrPath = QrService::generate($payload, $uuid);
            $up = $pdo->prepare('UPDATE participants SET qr_path=? WHERE uuid=?');
            $up->execute([$qrPath, $uuid]);
        } catch (\PDOException $e) {
            http_response_code(500);
            $error = 'Registration failed';
            require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register_error.php';
            return;
        }

        $to = $email !== '' ? $email : ($office_email !== '' ? $office_email : '');
        if ($to !== '') {
            $subject = 'Your registration QR code';
            $body = '<p>Thank you for registering.</p><p>Please find your QR attached or available on the confirmation page.</p>';
            $sent = Mailer::send($to, $subject, $body, $qrPath);
            \App\Services\Logger::log(null, $sent ? 'email_sent' : 'email_failed', ['to'=>$to]);
        }

        if (!isset($_SESSION['qr_allowed'])) $_SESSION['qr_allowed'] = [];
        $_SESSION['qr_allowed'][$uuid] = true;
        header('Location: /?r=register_success&uuid=' . urlencode($uuid));
        exit;
    }
}