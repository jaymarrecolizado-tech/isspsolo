<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class ExportController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { http_response_code(403); echo 'Forbidden'; return false; }
        return true;
    }

    public function registrantsCsv(): void
    {
        if (!$this->requireAdmin()) return;
        \App\Services\Logger::log((int)$_SESSION['admin_id'], 'export_registrants_csv');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="registrants.csv"');
        $out = fopen('php://output', 'w');
        $hdr = ['Timestamp','Email Address','First Name','Middle Name','Last Name','Nickname','Sex','Sector','Agency','Designation','Office Email','Contact No'];
        fputcsv($out, $hdr);
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT timestamp,email,first_name,middle_name,last_name,nickname,sex,sector,agency,designation,office_email,contact_no FROM participants ORDER BY id DESC');
        while ($r = $stmt->fetch()) {
            fputcsv($out, [
                $r['timestamp'], $r['email'], $r['first_name'], $r['middle_name'], $r['last_name'], $r['nickname'], $r['sex'], $r['sector'], $r['agency'], $r['designation'], $r['office_email'], $r['contact_no']
            ]);
        }
        fclose($out);
    }

    public function attendanceCsv(): void
    {
        if (!$this->requireAdmin()) return;
        \App\Services\Logger::log((int)$_SESSION['admin_id'], 'export_attendance_csv');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="attendance.csv"');
        $out = fopen('php://output', 'w');
        $hdr = ['Attendance Date','Time In','UUID','Name','Agency','Signature Path'];
        fputcsv($out, $hdr);
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT a.attendance_date,a.time_in,p.uuid,(CONCAT(p.first_name," ",p.last_name)) AS name,p.agency,a.signature_path FROM attendance a JOIN participants p ON p.id=a.participant_id ORDER BY a.id DESC');
        while ($r = $stmt->fetch()) {
            fputcsv($out, [$r['attendance_date'],$r['time_in'],$r['uuid'],$r['name'],$r['agency'],$r['signature_path']]);
        }
        fclose($out);
    }
}
