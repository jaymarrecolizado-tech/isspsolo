<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Controllers\RegisterController;
use App\Controllers\ScanController;
use App\Controllers\ParticipantController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\AdminRegistrantsController;
use App\Controllers\AdminAttendanceController;
use App\Controllers\AdminImportController;
use App\Controllers\ExportController;
use App\Controllers\SampleCsvController;
use App\Controllers\AdminEventsController;
use App\Controllers\AdminAttendanceGalleryController;
use App\Controllers\SettingsController;
use App\Controllers\AdminLogsController;
use App\Controllers\AdvancedExportController;
use App\Controllers\ReportController;

spl_autoload_register(function($class){
    $prefix = 'App\\';
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . $rel . '.php';
    if (is_file($file)) require $file;
});
if (is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

$route = $_GET['r'] ?? 'register';

if ($route === 'register') {
    (new RegisterController())->show();
    exit;
}
if ($route === 'register_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new RegisterController())->submit();
    exit;
}
if ($route === 'register_submit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?r=register');
    exit;
}
if ($route === 'register_success') {
    (new RegisterController())->success();
    exit;
}
if ($route === 'scan') {
    (new ScanController())->show();
    exit;
}
if ($route === 'api_participant') {
    (new ParticipantController())->getByUuidJson();
    exit;
}
if ($route === 'attendance_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AttendanceController())->submit();
    exit;
}
if ($route === 'admin_login') {
    (new AuthController())->loginForm();
    exit;
}
if ($route === 'admin_login_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AuthController())->login();
    exit;
}
if ($route === 'admin_logout') {
    (new AuthController())->logout();
    exit;
}
if ($route === 'admin_registrants') {
    (new AdminRegistrantsController())->list();
    exit;
}
if ($route === 'admin_attendance') {
    (new AdminAttendanceController())->list();
    exit;
}
if ($route === 'admin_attendance_gallery') {
    (new AdminAttendanceGalleryController())->list();
    exit;
}
if ($route === 'admin_settings') {
    (new SettingsController())->form();
    exit;
}
if ($route === 'admin_settings_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new SettingsController())->save();
    exit;
}
if ($route === 'admin_import') {
    (new AdminImportController())->form();
    exit;
}
if ($route === 'admin_import_preview' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AdminImportController())->preview();
    exit;
}
if ($route === 'admin_import_execute' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AdminImportController())->execute();
    exit;
}
if ($route === 'admin_import_history') {
    (new AdminImportController())->history();
    exit;
}
if ($route === 'admin_export') {
    if (empty($_SESSION['admin_id'])) { header('Location: /?r=admin_login'); exit; }
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_export.php';
    exit;
}
if ($route === 'admin_report') {
    (new ReportController())->form();
    exit;
}
if ($route === 'admin_report_generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new ReportController())->generate();
    exit;
}
if ($route === 'admin_report_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new ReportController())->saveTemplate();
    exit;
}
if ($route === 'admin_report_load') {
    (new ReportController())->loadTemplate();
    exit;
}
if ($route === 'admin_logs') {
    (new AdminLogsController())->list();
    exit;
}
if ($route === 'admin_events') {
    (new AdminEventsController())->list();
    exit;
}
if ($route === 'admin_events_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AdminEventsController())->create();
    exit;
}
if ($route === 'admin_events_set_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AdminEventsController())->setActive();
    exit;
}
if ($route === 'export_registrants_csv') {
    (new ExportController())->registrantsCsv();
    exit;
}
if ($route === 'export_attendance_csv') {
    (new ExportController())->attendanceCsv();
    exit;
}
if ($route === 'export_registrants_xlsx') {
    (new AdvancedExportController())->registrantsXlsx();
    exit;
}
if ($route === 'export_attendance_xlsx') {
    (new AdvancedExportController())->attendanceXlsx();
    exit;
}
if ($route === 'export_attendance_pdf') {
    (new AdvancedExportController())->attendancePdf();
    exit;
}
if ($route === 'sample_csv') {
    (new SampleCsvController())->download();
    exit;
}

http_response_code(404);
echo 'Not Found';