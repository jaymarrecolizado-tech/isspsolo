<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class ReportController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { header('Location: /?r=admin_login'); return false; }
        return true;
    }

    public function form(): void
    {
        if (!$this->requireAdmin()) return;
        $pdfAvailable = class_exists('TCPDF');
        $pdo = Database::pdo();
        $tpl = $pdo->query('SELECT id,name FROM report_templates WHERE admin_id='.(int)$_SESSION['admin_id'].' ORDER BY id DESC')->fetchAll();
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_report.php';
    }

    public function generate(): void
    {
        if (!$this->requireAdmin()) return;
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $date = trim((string)($_POST['date'] ?? ''));
        $title = trim((string)($_POST['title'] ?? 'Attendance Report'));
        $subtitle = trim((string)($_POST['subtitle'] ?? ''));
        $fields = (array)($_POST['fields'] ?? []);
        $format = trim((string)($_POST['format'] ?? 'auto'));
        $download = ((string)($_POST['download'] ?? '0')) === '1';
        $start = trim((string)($_POST['start_date'] ?? ''));
        $end = trim((string)($_POST['end_date'] ?? ''));
        $leftLogoPath = null; $rightLogoPath = null;
        foreach (['left_logo'=>'leftLogoPath','right_logo'=>'rightLogoPath'] as $key=>$var) {
            if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
                $type = mime_content_type($_FILES[$key]['tmp_name']);
                if (!in_array($type, ['image/png','image/jpeg'])) continue;
                $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'logos';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $ext = $type === 'image/png' ? 'png' : 'jpg';
                $dest = $dir . DIRECTORY_SEPARATOR . (time().'_'.bin2hex(random_bytes(4))).'.'.$ext;
                move_uploaded_file($_FILES[$key]['tmp_name'], $dest);
                $$var = $dest;
            }
        }
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $dateOk = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
        $startOk = $start !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start);
        $endOk = $end !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end);
        if ($startOk || $endOk) {
            if ($startOk && $endOk) { $where[]='a.attendance_date BETWEEN ? AND ?'; $bind[]=$start; $bind[]=$end; }
            elseif ($startOk) { $where[]='a.attendance_date >= ?'; $bind[]=$start; }
            else { $where[]='a.attendance_date <= ?'; $bind[]=$end; }
        } elseif ($dateOk) {
            $where[]='a.attendance_date = ?'; $bind[]=$date;
        }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.id,a.signature_path,a.attendance_date,a.time_in,p.uuid,p.first_name,p.last_name,p.agency,p.designation,p.email,p.sex,p.contact_no FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id ASC");
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();

        $pdfAvailable = class_exists('TCPDF');
        $usePdf = ($format === 'pdf' && $pdfAvailable) || ($format === 'auto' && $pdfAvailable);
        if ($usePdf) {
            $pdf = new class('L') extends \TCPDF {
                public function Footer(): void { $this->SetY(-15); $this->SetFont('helvetica','I',8); $this->Cell(0,10,'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),0,0,'R'); }
            };
            $pdf->setPrintFooter(true);
            $pdf->AddPage('L');
            $pdf->SetFont('helvetica','',9);
            $map = $this->fieldMap();
            $labels = [];
            foreach ($fields as $f) { if (isset($map[$f])) { $labels[] = strtoupper($f==='id' ? 'No.' : $map[$f]); } }
            $hasNo = in_array('id', $fields, true);
            $sigW = 10;
            $noW = $hasNo ? 6 : 0;
            $rem = 100 - $sigW - $noW;
            $otherCount = count($fields) - ($hasNo ? 1 : 0);
            $per = $otherCount > 0 ? intdiv($rem, $otherCount) : 0;
            $lastAdjust = $otherCount > 0 ? ($rem - ($per * ($otherCount))) : 0;
            $widths = [];
            foreach ($fields as $i=>$f) {
                if ($f === 'id') { $widths[] = $noW; }
                else { $widths[] = $per + (($lastAdjust>0 && $i === (count($fields)-1)) ? $lastAdjust : 0); }
            }
            $headerRow = '<tr>';
            foreach ($labels as $i=>$l) { $headerRow .= '<th width="'.$widths[$i].'%">'.$l.'</th>'; }
            $headerRow .= '<th width="'.$sigW.'%">'.strtoupper('Signature').'</th></tr>';
            $perPage = 15;
            $total = count($rows);
            $idx = 0;
            for ($offset = 0; $offset < $total; $offset += $perPage) {
                $html = '';
                $html .= '<style>.title{font-size:16px;font-weight:bold;text-transform:uppercase;margin:0} .subtitle{font-size:11px;color:#333;margin:2px 0 6px} .section-title{font-size:11px;font-weight:bold;margin:4px 0} .tbl td{font-size:9px} .tbl th{font-size:9px} .tbl thead{display:table-header-group}</style>';
                $html .= '<table width="100%"><tr>';
                $html .= '<td width="20%">'.($leftLogoPath?('<img src="data:image/'.($this->extOf($leftLogoPath)).';base64,'.base64_encode(file_get_contents($leftLogoPath)).'" height="50">'):'').'</td>';
                $html .= '<td width="60%" align="center"><div class="title">'.nl2br(htmlspecialchars($title)).'</div>'.($subtitle!==''?('<div class="subtitle">'.nl2br(htmlspecialchars($subtitle)).'</div>'):'').'</td>';
                $html .= '<td width="20%" align="right">'.($rightLogoPath?('<img src="data:image/'.($this->extOf($rightLogoPath)).';base64,'.base64_encode(file_get_contents($rightLogoPath)).'" height="50">'):'').'</td>';
                $html .= '</tr></table>';
                $html .= '<hr style="height:1px;border:0;border-top:1px solid #000;margin:2px 0 6px 0">';
                $html .= '<div class="section-title">Registered Guest List</div><table class="tbl" border="1" cellpadding="3"><thead>'.$headerRow.'</thead><tbody>';
                $count = min($perPage, $total - $offset);
                for ($i = 0; $i < $count; $i++) {
                    $r = $rows[$offset + $i];
                    $html .= '<tr>';
                    foreach ($fields as $ci=>$f) {
                        $html .= '<td width="'.$widths[$ci].'%">'.($f==='id' ? (string)($idx+1) : $this->val($f, $r)).'</td>';
                    }
                    $b64 = '';
                    if (!empty($r['signature_path']) && is_file($r['signature_path'])) { $b64 = base64_encode(file_get_contents($r['signature_path'])); }
                    $imgTag = $b64 !== '' ? ('<img src="data:image/png;base64,'.$b64.'" height="40">') : '';
                    $html .= '<td width="'.$sigW.'%">'.$imgTag.'</td>';
                    $html .= '</tr>';
                    $idx++;
                }
                $html .= '</tbody></table>';
                $pdf->writeHTML($html);
                if ($offset + $perPage < $total) { $pdf->AddPage('L'); }
            }
            $pdf->Output('attendance_report.pdf', $download ? 'D' : 'I');
            return;
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-3">';
        echo '<table width="100%"><tr>';
        echo '<td width="20%">'.($leftLogoPath?('<img src="data:image/'.($this->extOf($leftLogoPath)).';base64,'.base64_encode(file_get_contents($leftLogoPath)).'" style="height:60px">'):'').'</td>';
        echo '<td width="60%" class="text-center"><h2>'.htmlspecialchars($title).'</h2>'.($subtitle!==''?('<div>'.htmlspecialchars($subtitle).'</div>'):'').'</td>';
        echo '<td width="20%" class="text-end">'.($rightLogoPath?('<img src="data:image/'.($this->extOf($rightLogoPath)).';base64,'.base64_encode(file_get_contents($rightLogoPath)).'" style="height:60px">'):'').'</td>';
        echo '</tr></table>';
        echo '<h4>Registered Guest List</h4>';
        echo '<table class="table table-sm table-bordered"><thead><tr>';
        $map = $this->fieldMap(); foreach ($fields as $f) { if (isset($map[$f])) echo '<th>'.$map[$f].'</th>'; } echo '<th>Signature</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>'; foreach ($fields as $f) { echo '<td>'.$this->val($f,$r).'</td>'; }
            $b64 = '';
            if (!empty($r['signature_path']) && is_file($r['signature_path'])) { $b64 = base64_encode(file_get_contents($r['signature_path'])); }
            $imgTag = $b64 !== '' ? ('<img src="data:image/png;base64,'.$b64.'" style="height:40px">') : '';
            echo '<td>'.$imgTag.'</td></tr>';
        }
        echo '</tbody></table></body></html>';
    }

    private function fieldMap(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'agency' => 'Agency/Org.',
            'designation' => 'Designation',
            'email' => 'Email',
            'contact_no' => 'Mobile',
            'sex' => 'Gender',
            'registered_at' => 'Registered At',
        ];
    }

    private function val(string $f, array $r): string
    {
        switch ($f) {
            case 'id': return (string)$r['id'];
            case 'name': return htmlspecialchars(($r['first_name'].' '.$r['last_name']), ENT_QUOTES);
            case 'agency': return htmlspecialchars((string)$r['agency'], ENT_QUOTES);
            case 'designation': return htmlspecialchars((string)$r['designation'], ENT_QUOTES);
            case 'email': return htmlspecialchars((string)$r['email'], ENT_QUOTES);
            case 'contact_no': return htmlspecialchars((string)$r['contact_no'], ENT_QUOTES);
            case 'sex': return htmlspecialchars((string)$r['sex'], ENT_QUOTES);
            case 'registered_at': return htmlspecialchars(($r['attendance_date'].' '.$r['time_in']), ENT_QUOTES);
        }
        return '';
    }
    private function extOf(string $path): string { return str_ends_with(strtolower($path), '.png') ? 'png' : 'jpeg'; }

    public function saveTemplate(): void
    {
        if (!$this->requireAdmin()) return;
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $name = trim((string)($_POST['tpl_name'] ?? 'Untitled'));
        $config = [
            'title' => (string)($_POST['title'] ?? ''),
            'subtitle' => (string)($_POST['subtitle'] ?? ''),
            'date' => (string)($_POST['date'] ?? ''),
            'start_date' => (string)($_POST['start_date'] ?? ''),
            'end_date' => (string)($_POST['end_date'] ?? ''),
            'fields' => (array)($_POST['fields'] ?? []),
            'format' => (string)($_POST['format'] ?? 'auto'),
        ];
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO report_templates (admin_id,name,config) VALUES (?,?,?)');
        $stmt->execute([(int)$_SESSION['admin_id'],$name,json_encode($config)]);
        header('Location: /?r=admin_report');
    }

    public function loadTemplate(): void
    {
        if (!$this->requireAdmin()) return;
        $id = (int)($_GET['tpl_id'] ?? 0);
        $pdo = Database::pdo();
        $tpl = $pdo->prepare('SELECT config FROM report_templates WHERE id=? AND admin_id=?');
        $tpl->execute([$id,(int)$_SESSION['admin_id']]);
        $row = $tpl->fetch();
        header('Content-Type: application/json');
        echo $row ? $row['config'] : json_encode(['error'=>'not_found']);
    }

    public function rowsForTest(?string $date, ?string $start, ?string $end): array
    {
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $date = trim((string)$date);
        $start = trim((string)$start);
        $end = trim((string)$end);
        $dateOk = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
        $startOk = $start !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start);
        $endOk = $end !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end);
        if ($startOk || $endOk) {
            if ($startOk && $endOk) { $where[]='a.attendance_date BETWEEN ? AND ?'; $bind[]=$start; $bind[]=$end; }
            elseif ($startOk) { $where[]='a.attendance_date >= ?'; $bind[]=$start; }
            else { $where[]='a.attendance_date <= ?'; $bind[]=$end; }
        } elseif ($dateOk) { $where[]='a.attendance_date = ?'; $bind[]=$date; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.id,a.attendance_date,a.time_in FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id ASC");
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    public function buildPdfPagesForTest(?string $date, ?string $start, ?string $end, array $fields, string $title, string $subtitle, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $where=[];$bind=[];
        $date = trim((string)$date);
        $start = trim((string)$start);
        $end = trim((string)$end);
        $dateOk = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
        $startOk = $start !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start);
        $endOk = $end !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end);
        if ($startOk || $endOk) {
            if ($startOk && $endOk) { $where[]='a.attendance_date BETWEEN ? AND ?'; $bind[]=$start; $bind[]=$end; }
            elseif ($startOk) { $where[]='a.attendance_date >= ?'; $bind[]=$start; }
            else { $where[]='a.attendance_date <= ?'; $bind[]=$end; }
        } elseif ($dateOk) { $where[]='a.attendance_date = ?'; $bind[]=$date; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.id,a.signature_path,a.attendance_date,a.time_in,p.uuid,p.first_name,p.last_name,p.agency,p.designation,p.email,p.sex,p.contact_no FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id ASC");
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();
        $map = $this->fieldMap();
        $labels = [];
        foreach ($fields as $f) { if (isset($map[$f])) { $labels[] = strtoupper($f==='id' ? 'No.' : $map[$f]); } }
        $hasNo = in_array('id', $fields, true);
        $sigW = 10;
        $noW = $hasNo ? 6 : 0;
        $rem = 100 - $sigW - $noW;
        $otherCount = count($fields) - ($hasNo ? 1 : 0);
        $per = $otherCount > 0 ? intdiv($rem, $otherCount) : 0;
        $lastAdjust = $otherCount > 0 ? ($rem - ($per * ($otherCount))) : 0;
        $widths = [];
        foreach ($fields as $i=>$f) { $widths[] = ($f==='id') ? $noW : ($per + (($lastAdjust>0 && $i === (count($fields)-1)) ? $lastAdjust : 0)); }
        $headerRow = '<tr>';
        foreach ($labels as $i=>$l) { $headerRow .= '<th width="'.$widths[$i].'%">'.$l.'</th>'; }
        $headerRow .= '<th width="'.$sigW.'%">'.strtoupper('Signature').'</th></tr>';
        $pages = [];
        $idx = 0;
        for ($offset = 0; $offset < count($rows); $offset += $perPage) {
            $html = '';
            $html .= '<style>.title{font-size:16px;font-weight:bold;text-transform:uppercase;margin:0} .subtitle{font-size:11px;color:#333;margin:2px 0 6px} .section-title{font-size:11px;font-weight:bold;margin:4px 0} .tbl td{font-size:9px} .tbl th{font-size:9px} .tbl thead{display:table-header-group}</style>';
            $html .= '<div class="title">'.nl2br(htmlspecialchars($title)).'</div>'.($subtitle!==''?('<div class="subtitle">'.nl2br(htmlspecialchars($subtitle)).'</div>'):'');
            $html .= '<div class="section-title">Registered Guest List</div><table class="tbl" border="1" cellpadding="3"><thead>'.$headerRow.'</thead><tbody>';
            $count = min($perPage, count($rows) - $offset);
            for ($i = 0; $i < $count; $i++) {
                $r = $rows[$offset + $i];
                $html .= '<tr>';
                foreach ($fields as $ci=>$f) { $html .= '<td width="'.$widths[$ci].'%">'.($f==='id' ? (string)($idx+1) : $this->val($f, $r)).'</td>'; }
                $b64 = '';
                if (!empty($r['signature_path']) && is_file($r['signature_path'])) { $b64 = base64_encode(file_get_contents($r['signature_path'])); }
                $imgTag = $b64 !== '' ? ('<img src="data:image/png;base64,'.$b64.'" height="40">') : '';
                $html .= '<td width="'.$sigW.'%">'.$imgTag.'</td>';
                $html .= '</tr>';
                $idx++;
            }
            $html .= '</tbody></table>';
            $pages[] = $html;
        }
        return $pages;
    }
}