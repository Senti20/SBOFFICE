# Prototype → Laravel Migration and Fix Log

## 1. Original prototype

The uploaded prototype was Node.js/Express, not PHP.

```text
HTML/CSS/JS
   ↓
server.js (Express)
   ↓
local docs/ folder
```

There was no SQL database implementation in the uploaded prototype.

## 2. File mapping

| Prototype | Laravel replacement |
|---|---|
| `server.js` | Controllers, service, models, routes |
| `public/index.html` | `resources/views/public/index.blade.php` |
| `public/script.js` | Blade/server-side search + small Laravel JS layer |
| `admin/index.html` | `resources/views/admin/*.blade.php` |
| `admin/script.js` | Server-side admin forms/controllers |
| `admin/style.css` | `resources/css/app.css` |
| `docs/*.pdf` | `database/seed-data/documents/*.pdf` → Laravel storage |
| in-memory `requests[]` | `requests` SQL table |
| filename-based document metadata | `documents` SQL table |
| hard-coded admin credentials | Laravel session authentication |

## 3. Prototype problems identified

### Hard-coded credentials

The prototype checked `admin` / `admin123` directly in browser JavaScript. Anyone with browser access could inspect the credentials.

**Fix:** Laravel server-side authentication + session + admin middleware.

### No SQL database

Documents were discovered by reading filenames from the local `docs` directory.

**Fix:** SQL `documents` table with structured metadata.

### Metadata was not persisted

The upload form collected document type, session number, date, author, and tags, but `server.js` did not save these values to a database record.

**Fix:** dedicated columns in `documents`.

### Race-prone document counter

The browser fetched `/api/counter`, added one, and sent the resulting ID. Two simultaneous uploads could produce the same document number.

**Fix:** database-generated primary key followed by `DOC-###` assignment.

### Requests were not actually stored

The public request form only showed a success message. It did not POST a request to the server.

**Fix:** real `requests` table + validated POST route + tracking code.

### Request GET endpoint was missing

The admin JavaScript called `GET /api/requests`, but the Express server did not implement it.

**Fix:** Laravel request index queries SQL directly.

### Request update endpoint was a stub

`PUT /api/requests/:id` always returned success without changing data.

**Fix:** validated database status update with processor and timestamp.

### Client-side search only

The prototype downloaded all documents and filtered them in JavaScript.

**Fix:** SQL-backed server-side search, filtering, and pagination.

### Public files were directly exposed

The Express server mounted the entire `docs/` directory as a static public path.

**Fix:** PDFs are stored on a private Laravel disk and returned through authorization-aware controller routes.

### Unsafe HTML rendering

The prototype built table rows with string interpolation from filenames and request values.

**Fix:** Blade escaping by default.

### CORS and absolute localhost URLs

The browser used `http://localhost:3000/api` and `http://localhost:3000/docs/...` directly.

**Fix:** same-origin Laravel routes and named route helpers.

### Delete physically removed the file

The prototype permanently deleted a PDF from the filesystem.

**Fix:** document deletion is a soft archive. This reduces accidental data loss and preserves the database record.

### Missing upload metadata/title

The prototype had no dedicated title field and relied heavily on filenames.

**Fix:** title is required and filename is retained only as file metadata.

## 4. Security improvements

- CSRF protection on browser forms.
- Server-side validation for all important inputs.
- PDF MIME/content validation with a 10 MB limit.
- Login throttling.
- Request submission and tracking throttling.
- Admin authorization middleware.
- Private file storage.
- Escaped Blade output.
- SQL through Eloquent/query builder instead of interpolated SQL strings.
- Soft deletion for documents.

Laravel provides built-in validation rules for uploaded files, including MIME-aware `mimes` validation and file size limits. Laravel validation documentation: https://laravel.com/docs/13.x/validation

## 5. Business workflow after migration

### Publish

```text
Admin login
  ↓
Publish Document
  ↓
Validate metadata + PDF
  ↓
Store PDF
  ↓
Create SQL record
  ↓
Assign DOC-###
  ↓
Published/public archive
```

### Public search

```text
Citizen search
  ↓
Laravel query
  ↓
Published + public documents only
  ↓
Paginated results
  ↓
Secure PDF viewer
```

### Certified-copy request

```text
Citizen selects document
  ↓
Validated request
  ↓
REQ-XXXXXXXX tracking code
  ↓
Pending
  ↓
Admin updates status
  ↓
Ready for Pickup / Completed / Cancelled
```

## 6. Important deployment rule

Laravel should be served from its `public/` directory rather than exposing the project root. This prevents sensitive files such as `.env`, `storage`, and application source from being directly served by the web server. Laravel installation documentation: https://laravel.com/docs/13.x/installation

## 7. Remaining production hardening

Before public deployment, add:

- HTTPS
- regular database backups
- server-level PDF/file size limits consistent with Laravel validation
- audit logs for administrative actions
- a real production mail provider if notifications are added
- least-privilege database credentials
- a production web server configured to point at `public/`
- a formal staff role/permission matrix if more roles are introduced
- automated tests for authentication, upload, request workflow, and authorization
