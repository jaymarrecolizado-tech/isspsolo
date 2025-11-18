<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AdvancedExportController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { http_response_code(403); echo 'Forbidden'; return false; }
        return true;
    }

    public function registrantsXlsx(): void
    {
        if (!$this->requireAdmin()) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!\App\Services\RateLimiter::allow('export_reg_xlsx:'.$ip, 5, 60)) { http_response_code(429); echo 'Too Many Requests'; return; }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) { header('Content-Type: text/plain'); echo 'XLSX library not installed'; return; }
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $q = trim((string)($_GET['q'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        $sector = trim((string)($_GET['sector'] ?? ''));
        if ($q !== '') { $where[]='(first_name LIKE ? OR last_name LIKE ?)'; $bind[]="%{$q}%"; $bind[]="%{$q}%"; }
        if ($agency !== '') { $where[]='agency LIKE ?'; $bind[]="%{$agency}%"; }
        if ($sector !== '') { $where[]='sector LIKE ?'; $bind[]="%{$sector}%"; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT timestamp,email,first_name,middle_name,last_name,nickname,sex,sector,agency,designation,office_email,contact_no FROM participants $sqlWhere ORDER BY id DESC");
        $stmt->execute($bind);

        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->fromArray(['Timestamp','Email Address','First Name','Middle Name','Last Name','Nickname','Sex','Sector','Agency','Designation','Office Email','Contact No'], NULL, 'A1');
        $row = 2;
        while ($r = $stmt->fetch()) {
            $ws->fromArray([ $r['timestamp'],$r['email'],$r['first_name'],$r['middle_name'],$r['last_name'],$r['nickname'],$r['sex'],$r['sector'],$r['agency'],$r['designation'],$r['office_email'],$r['contact_no']], NULL, 'A'.$row);
            $row++;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="registrants.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
        $writer->save('php://output');
    }

    public function attendanceXlsx(): void
    {
        if (!$this->requireAdmin()) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!\App\Services\RateLimiter::allow('export_att_xlsx:'.$ip, 5, 60)) { http_response_code(429); echo 'Too Many Requests'; return; }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) { header('Content-Type: text/plain'); echo 'XLSX library not installed'; return; }
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $date = trim((string)($_GET['date'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        $purpose = trim((string)($_GET['purpose'] ?? ''));
        if ($date !== '') { $where[]='a.attendance_date = ?'; $bind[]=$date; }
        if ($purpose !== '') { $where[]='a.purpose = ?'; $bind[]=$purpose; }
        if ($agency !== '') { $where[]='p.agency LIKE ?'; $bind[]="%{$agency}%"; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.attendance_date,a.time_in,a.purpose,p.uuid,(CONCAT(p.first_name,' ',p.last_name)) AS name,p.agency,a.signature_path FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id DESC");
        $stmt->execute($bind);

        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->fromArray(['Attendance Date','Time In','UUID','Name','Agency','Signature Path'], NULL, 'A1');
        $row = 2;
        while ($r = $stmt->fetch()) {
            $ws->fromArray([ $r['attendance_date'],$r['time_in'],$r['uuid'],$r['name'],$r['agency'],$r['signature_path'] ], NULL, 'A'.$row);
            $row++;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="attendance.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
        $writer->save('php://output');
    }

    public function attendancePdf(): void
    {
        if (!$this->requireAdmin()) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!\App\Services\RateLimiter::allow('export_att_pdf:'.$ip, 5, 60)) { http_response_code(429); echo 'Too Many Requests'; return; }
        if (!class_exists('TCPDF')) { header('Content-Type: text/plain'); echo 'PDF library not installed'; return; }
        $download = ((string)($_GET['download'] ?? '0')) === '1';
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $date = trim((string)($_GET['date'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        if ($date !== '') { $where[]='a.attendance_date = ?'; $bind[]=$date; }
        if ($agency !== '') { $where[]='p.agency LIKE ?'; $bind[]="%{$agency}%"; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.id,a.attendance_date,a.time_in,p.uuid,(CONCAT(p.first_name,' ',p.last_name)) AS name,p.agency FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id DESC");
        $stmt->execute($bind);

        $rows = $stmt->fetchAll();
        $pdf = new class('L') extends \TCPDF { public function Footer(): void { $this->SetY(-15); $this->SetFont('helvetica','I',8); $this->Cell(0,10,'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),0,0,'R'); } };
        $pdf->setPrintFooter(true);
        $perPage = 15;
        $total = count($rows);
        for ($offset = 0; $offset < $total; $offset += $perPage) {
            $pdf->AddPage('L');
            $html = '<style>.tbl td{font-size:9px} .tbl th{font-size:9px} .tbl thead{display:table-header-group}</style>';
            $html .= '<div style="text-align:center;font-size:16px;font-weight:bold;">Attendance</div>';
            $html .= '<hr style="height:1px;border:0;border-top:1px solid #000;margin:2px 0 6px 0">';
            $html .= '<table class="tbl" border="1" cellpadding="3"><thead><tr><th width="15%">Date</th><th width="10%">Time</th><th width="15%">Purpose</th><th width="25%">Name</th><th width="25%">Agency</th><th width="10%">Signature</th></tr></thead><tbody>';
            $count = min($perPage, $total - $offset);
            for ($i = 0; $i < $count; $i++) {
                $r = $rows[$offset + $i];
                $img = '/signature.php?aid='.$r['id'];
                $html .= '<tr><td width="15%">'.$r['attendance_date'].'</td><td width="10%">'.$r['time_in'].'</td><td width="15%">'.htmlspecialchars($r['purpose']).'</td><td width="25%">'.$r['name'].'</td><td width="25%">'.$r['agency'].'</td><td width="10%"><img src="'.$img.'" height="40"></td></tr>';
            }
            $html .= '</tbody></table>';
            $pdf->writeHTML($html);
        }
        $pdf->Output('attendance.pdf', $download ? 'D' : 'I');
    }
}