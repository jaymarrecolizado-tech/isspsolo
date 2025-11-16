# Installation

## Requirements
- PHP 8.x
- MySQL 8
- Extensions: PDO, GD

## Setup
1. Copy `.env.example` to `.env` and set DB/SMTP.
2. Start server: `php -S localhost:8000 -t public`.
3. Seed admin: `php scripts/seed_admin.php admin admin123`.
4. Optional: run `scripts/run_migrations.php`.

## SMTP
Update via `/?r=admin_settings` or edit `.env`.