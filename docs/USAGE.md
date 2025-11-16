# Usage

- Registration: `http://localhost:8000/`
- Scan & Sign: `http://localhost:8000/?r=scan`
- Admin Login: `http://localhost:8000/?r=admin_login`
- Registrants: `/?r=admin_registrants`
- Attendance: `/?r=admin_attendance`
- Gallery: `/?r=admin_attendance_gallery`
- Import: `/?r=admin_import`
- Export: `/?r=admin_export`
- Settings: `/?r=admin_settings`

## Exports
- CSV registrants: filters `q`, `agency`, `sector`
- CSV attendance: filters `date`, `agency`
- XLSX/PDF require libraries; endpoints return notice if missing.

## Tests
- `php scripts/run_tests.php`