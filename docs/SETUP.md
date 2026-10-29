# Local Setup — Step by Step

## Prerequisites

Install:

- PHP 8.3+
- Composer
- MySQL
- Node.js + npm

Laravel 13 requires PHP 8.3+. Laravel release notes: https://laravel.com/docs/13.x/releases

## 1. Create the database

In MySQL:

```sql
CREATE DATABASE sb_secretary_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## 2. Environment

Copy:

```text
.env.example → .env
```

Set at least:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sb_secretary_portal
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=ChangeMe123!
```

## 3. Install PHP packages

```bash
composer install
```

## 4. Generate application key

```bash
php artisan key:generate
```

## 5. Create SQL tables and seed sample data

```bash
php artisan migrate --seed
```

This creates the database schema, the admin account, document types, and imports the PDFs stored in `database/seed-data/documents`.

## 6. Install/build frontend assets

```bash
npm install
npm run build
```

For active development:

```bash
npm run dev
```

## 7. Start Laravel

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

## 8. Staff login

Open:

```text
/admin/login
```

Use the values from `.env`.

## 9. If you change migrations during development

For a disposable local database only:

```bash
php artisan migrate:fresh --seed
```

**Do not run `migrate:fresh` against a database containing real records.**

## 10. Clear stale configuration

If `.env` changes do not appear to take effect:

```bash
php artisan optimize:clear
```

## 11. File storage

This project uses Laravel's private `local` disk for PDFs. You do not need `storage:link` for the current secure document viewer because PDFs are streamed through controller routes.

If a future feature needs genuinely public files, Laravel supports a separate `public` disk and `storage:link`. Laravel filesystem documentation: https://laravel.com/docs/13.x/filesystem
