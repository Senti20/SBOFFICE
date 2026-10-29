# SB Secretary Portal — File Placement & Function Guide

## Application structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                 # staff-only controllers
│   │   ├── Auth/                  # login/logout
│   │   └── Portal/                # public-facing controllers
│   └── Middleware/                # request authorization
├── Models/                        # Eloquent database models
├── Providers/                     # Laravel service providers
└── Services/                      # reusable business logic

database/
├── migrations/                    # SQL schema managed by Laravel
├── seeders/                       # demo/default data
└── seed-data/documents/           # sample PDFs from the old prototype

resources/
├── views/                         # Blade HTML templates
├── css/app.css                    # application styles
└── js/                            # browser JavaScript + Axios bootstrap

routes/
└── web.php                        # browser routes

storage/app/documents/             # private uploaded PDFs
public/
└── index.php                      # Laravel public entry point
```

## Controllers

### `app/Http/Controllers/Auth/LoginController.php`
Handles staff login and logout. Credentials are checked server-side using Laravel authentication instead of the prototype's hard-coded `admin/admin123` check.

### `app/Http/Controllers/Admin/DashboardController.php`
Builds dashboard statistics and recent document/request lists.

### `app/Http/Controllers/Admin/DocumentController.php`
Handles staff document listing, upload, metadata editing, PDF viewing, and soft-archiving.

### `app/Http/Controllers/Admin/RequestController.php`
Lists requests and updates request status/notes.

### `app/Http/Controllers/Portal/DocumentController.php`
Provides the public archive search and only serves documents that are both published and public.

### `app/Http/Controllers/Portal/RequestController.php`
Creates certified-copy requests and provides privacy-aware request tracking using tracking code + contact number.

## Models

### `app/Models/User.php`
Staff account model. The `role` field currently supports `admin` for the staff portal.

### `app/Models/DocumentType.php`
Document categories such as Resolution, Minutes, Ordinance, and Other.

### `app/Models/Document.php`
Stores document metadata and the private file path. Uses soft deletes for safer archiving.

### `app/Models/Request.php`
Stores certified-copy requests, tracking codes, requested document, copies, pickup date, status, and processing information.

## Services

### `app/Services/DocumentService.php`
Owns the upload transaction. It stores the PDF, creates its database record, generates the document number, and removes the stored file if the transaction fails.

## Middleware

### `app/Http/Middleware/EnsureAdmin.php`
Prevents non-admin authenticated users from opening staff routes.

The alias is registered in `bootstrap/app.php` as `admin`.

## Database

### Migrations

- `000001_create_users_table.php` — staff accounts
- `000002_create_document_types_table.php` — document categories
- `000003_create_documents_table.php` — document metadata and storage paths
- `000004_create_requests_table.php` — certified-copy requests
- `000005_create_cache_and_jobs_tables.php` — Laravel cache/queue support

## Views

### `resources/views/layouts/app.blade.php`
Shared header, navigation, flash messages, error display, and footer.

### `resources/views/public/`
Citizen-facing archive, request, success, and tracking pages.

### `resources/views/admin/`
Staff dashboard, document management, and request management pages.

### `resources/views/auth/login.blade.php`
Secure staff login form.

## Frontend assets

### `resources/css/app.css`
Replaces the prototype's shared `admin/style.css` and keeps the same general visual language while making the interface responsive.

### `resources/js/app.js`
Small application-wide JavaScript layer, currently focused on shared behavior such as confirmation dialogs.

### `resources/js/bootstrap.js`
Configures Axios and CSRF headers for future AJAX features.

## Environment

`.env` contains machine-specific values such as database credentials and admin bootstrap credentials. Do not commit it.

## Sample data

The PDFs copied from the prototype are under:

```text
database/seed-data/documents/
```

`DatabaseSeeder` copies those files into Laravel storage and creates corresponding document records when `php artisan migrate --seed` is run.
