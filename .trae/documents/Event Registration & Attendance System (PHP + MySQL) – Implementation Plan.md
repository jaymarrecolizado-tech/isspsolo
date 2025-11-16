## Architecture Overview
- PHP 8.x + MySQL 8 with PDO (prepared statements), Composer-managed dependencies.
- MVC-style, PSR-4 autoloading; modular services (QR, email, CSV import, export, security).
- Stateless API endpoints for client pages; server-side rendering for admin views using Bootstrap 5.
- Protected storage outside webroot for images (`storage/qrcodes`, `storage/signatures`), streamed via access-controlled PHP endpoints.

## Tech Stack & Dependencies
- Core: `phpdotenv` (env), `PHPMailer` (SMTP email), `endroid/qr-code` (QR), `ramsey/uuid` (UUID v4), `league/csv` (CSV parsing), `phpoffice/phpspreadsheet` (XLSX optional), `tecnickcom/tcpdf` (PDF optional), `robmorgan/phinx` or simple custom migrations.
- Frontend: Bootstrap 5, `html5-qrcode` (QR scanning) or `jsQR`, `signature_pad` for signature canvas.
- Testing: PHPUnit for unit tests (duplicate detection, validators).

## Directory Structure
- `public/` entry points (`index.php`, assets), no direct access to `storage/`.
- `src/` with PSR-4 namespaces: `Controllers/`, `Models/`, `Services/`, `Middleware/`, `Utils/`.
- `views/` (Bootstrap 5 templates), `config/` (env reading), `migrations/` (SQL), `storage/` (qrcodes, signatures), `logs/`.

## Database Schema & Migrations
- Implement tables exactly per spec: `participants`, `attendance`, `admins`, `import_logs` with indexes (`uq_email`, `idx_name_agency`).
- Seed: one admin (bcrypt), sample participants.
- Migrations provide up/down; ensure FK `attendance.participant_id → participants.id ON DELETE CASCADE`.

## Security & Compliance
- Input validation/sanitization: central `Validator` with rules; server-side validation for all forms and JSON.
- CSRF protection: session token middleware; hidden tokens on forms; header token for AJAX (`X-CSRF-Token`).
- Auth: Admin login via sessions; bcrypt (`password_hash`/`password_verify`); RBAC gating for admin-only routes.
- Rate limiting: per-IP + per-endpoint table with sliding window; stricter on `/auth/login`, `/admin/import-*`.
- Headers: CSP, HSTS (deployment), secure cookies (`httponly`, `secure`, `samesite=strict`).
- File access: store images outside webroot; serve via PHP after auth; deny directory listing; random non-guessable filenames.
- Secrets via `.env` (DB, SMTP); never hardcode.

## Core Features
### Registration & QR
- Mobile-first form with required fields; validate and insert into `participants`.
- Generate `uuid` v4; create QR payload `PART|<uuid>`; save PNG to `storage/qrcodes/<prefix>/<uuid>.png` and persist `qr_path`.
- Display QR to user and email confirmation with inline/attached PNG.

### Scan & Signature
- Staff page: camera-based QR scan; decode payload; fetch `GET /participant/<uuid>`.
- Show participant, draw signature via `signature_pad`; `POST /attendance` saves Base64 PNG to `storage/signatures/<year>/<uuid>_<timestamp>.png`, writes attendance with `attendance_date`, `time_in`.
- Configurable per-day rules (single/multiple time-ins).

### Admin Dashboard
- Auth pages; registrants list with search/filter (name, agency, sector) and pagination.
- Attendance list with filters, signature thumbnails, download links (access-controlled).
- Signatures gallery view; Export buttons.

### Import Workflow
- Upload CSV (MIME/type and content validation, size caps, UTF-8);
- Header validation against template; errors shown on preview abort.
- Preview first 200 rows with status badges: New, Duplicate (email), Duplicate (name+agency), Error (missing required).
- Duplicate strategies: Skip (default), Override duplicates only, Override all.
- Confirmation screen with counts; execution in transaction batches (e.g., 500 rows); generate QR for new participants; optional email.
- Detailed `import_logs` with JSON summary (changed fields old→new). Optionally persist original CSV in protected storage and link.

### Export Module
- CSV (Google Sheets-compatible) with exact headers; filters for registrants/attendance/daily/combined.
- Optional XLSX via PHPSpreadsheet; optional PDF via TCPDF with signature thumbnails.

## API Endpoints
- `POST /auth/login`, `GET /auth/logout` (session + CSRF).
- `POST /register` (public, CSRF for form); `GET /participant/<uuid>` (JSON).
- `POST /attendance` (CSRF + staff session).
- `GET /attendance?date=YYYY-MM-DD` (admin).
- `POST /admin/import-preview`, `POST /admin/import-execute`, `GET /admin/import-history`.
- `GET /export/registrants.csv`, `GET /export/attendance.csv`.
- `GET /qrcode/<uuid>.png` and `GET /signature/<path>`: both stream from protected storage after auth/session checks.

## File Storage & Access Control
- Sharded QR folders by first 2 chars of UUID; signatures by year.
- Access scripts verify admin or authorized session; stream via `readfile` with correct headers; deny direct web access via server config.

## Logging & Audit
- Log admin actions (login, imports, exports) with timestamps and admin ID.
- Store import decisions and updates in `import_logs.summary` JSON.

## UI/UX
- Bootstrap 5, mobile-first; responsive grids, touch-friendly controls.
- Status badges (New green, Duplicate orange, Error red, Updated blue).
- Loading/error/success states; ARIA roles; keyboard accessibility.

## Testing & QA
- PHPUnit tests for duplicate detection (email primary, name+agency fallback) and validators.
- Manual QA script: register → email → scan → sign → attendance → admin views.
- Sample CSVs: valid new rows, email duplicates, missing columns (fail gracefully).
- Export validation: open in Google Sheets.
- Security checks: direct signature/QR URLs blocked; CSRF tokens enforced.

## Deployment & Configuration
- `.env` for DB/SMTP; environment-based config; production-only HSTS/CSP.
- Optional Docker Compose (Nginx + PHP-FPM + MySQL) if requested.
- Installation instructions: PHP extensions, DB setup, SMTP, admin seed.

## Milestones
1) DB + Registration + QR + Email
2) Scan page + signature capture + attendance
3) Admin dashboard core (registrants, attendance, signatures)
4) CSV Import workflow + logging
5) Export module (CSV, optional XLSX/PDF)
6) Security hardening + tests + docs

## Deliverables
- Full source (Composer, PSR-4), SQL schema/migrations, seed data.
- Admin credentials (dev), sample CSV template and test CSVs.
- Installation and import/export usage guides; short security checklist.

## Next Step
- Confirm to proceed with Phase 1 (DB migrations + Registration flow with QR generation and email).