# Architecture Document

## 1. Overview

The SB Secretary Portal is designed to support document management for a local government office. The current prototype uses a lightweight Node.js/Express architecture, but the planned production version will be built using Laravel with PHP and an SQL database.

The goal of the migration is to preserve the existing business workflow while improving data persistence, validation, security, and maintainability. The system will support the upload, storage, search, viewing, and request management of official public records.

---

## 2. Architectural Principles for the Laravel Migration

### Maintainability
The application should follow clean Laravel conventions, with routes, controllers, models, migrations, policies, and views clearly separated.

### Data Integrity
The system should move away from file-only storage and use SQL tables to store documents, users, request records, and metadata.

### Security
Authentication, authorization, validation, and access control should be handled in Laravel’s framework conventions rather than ad hoc browser logic.

### Scalability
The design should support future growth in document count, request volume, and staff roles without requiring a rewrite.

### Usability
The admin dashboard and public portal must remain simple and office-friendly for non-technical users.

---

## 3. Target System Architecture

### Technology Stack
- PHP
- Laravel
- SQL database (MySQL or PostgreSQL)
- Blade templates
- Eloquent ORM
- Laravel Storage for file uploads
- optional Livewire or Alpine.js only if needed for simple interactive UI

### Layered Architecture
The application will be organized into the following layers:

1. Presentation layer
   - public portal pages
   - admin dashboard pages
   - Blade templates and forms

2. Application layer
   - controllers
   - request validation
   - service classes
   - authorization logic

3. Data layer
   - Eloquent models
   - migrations
   - relational SQL tables

4. Storage layer
   - uploaded files in Laravel storage or public disk
   - metadata stored in SQL database

---

## 4. High-Level Component Structure

### Admin Module
Responsibilities:
- login and secure access
- upload PDF documents
- search and filter records
- view document metadata
- update request status
- manage document archive and visibility

### Public Module
Responsibilities:
- show available documents
- search records by keyword or ID
- open PDF files
- send request for certified copies

### Request Module
Responsibilities:
- capture request details
- store requests in SQL
- manage workflow statuses
- allow admin updates

### Document Module
Responsibilities:
- store document metadata and file references
- validate file type and size
- enforce categories and visibility rules
- support open/read access from admin or public views

---

## 5. Core Laravel Components

### Routes
Laravel routes should separate concerns clearly:

- admin routes for staff-only functions
- public routes for public access
- authenticated API routes where needed
- request and document management routes

### Controllers
Suggested controllers:
- AuthController
- DocumentController
- RequestController
- PublicDocumentController
- AdminDashboardController

### Models
Suggested models:
- User
- Document
- Request
- Category
- DocumentType

### Migrations
Each table should be created and managed through migrations, for example:
- users
- documents
- requests
- categories
- document_types

### Storage
Use Laravel storage for uploaded files:
- storage/app/public/documents
- public symlink via php artisan storage:link

This replaces the current local docs folder approach and makes the app more robust.

---

## 6. Database Design Recommendations

### Users Table
Stores admin accounts and potentially public account data if needed.

### Documents Table
Stores the document record and metadata.

Required fields:
- id
- document_number
- title
- type
- session_number
- document_date
- author
- tags
- is_public
- file_name
- file_path
- mime_type
- file_size
- uploaded_by
- created_at
- updated_at

### Requests Table
Tracks certified copy requests.

Required fields:
- id
- requester_name
- contact_number
- document_id
- copies
- pickup_date
- status
- notes
- created_at
- updated_at

### Categories Table
Supports organized classification of records.

---

## 7. Document Workflow in Laravel

### Upload workflow
1. Admin submits a form with document metadata and PDF file.
2. Laravel validates the request.
3. Laravel stores the file in the configured storage disk.
4. A document record is created in the database.
5. Admin is redirected to document list or confirmation page.

### Search workflow
1. User submits keyword or filter criteria.
2. Controller queries the database using Eloquent.
3. Matching documents are returned to the view.
4. Results are displayed with metadata and file preview/download links.

### Request workflow
1. Public user fills out the certified copy form.
2. Request is validated and saved to the database.
3. Admin staff can view request records and update their status.
4. Status updates are reflected in the public dashboard or request history.

---

## 8. Security Architecture

### Authentication
Use Laravel Breeze, Jetstream, or Fortify depending on project complexity.

### Authorization
Use policies or gates to restrict:
- admin-only document management
- admin-only request status updates
- public-only search and request forms

### Input Validation
Every request must validate:
- uploaded file type
- file size
- required form fields
- allowed document categories
- numeric values and dates

### File Security
- store files outside the web root when possible
- validate MIME types
- restrict extension and content types properly
- avoid exposing raw uploads without access control

---

## 9. Presentation Architecture

### Admin Dashboard
Use Blade templates with a clean layout for:
- upload form
- document table
- search/filter controls
- request management panel
- summary cards and activity metrics

### Public Portal
Use Blade templates for:
- search entry
- document listing
- document preview links
- request form

### Frontend Interaction
For simple functions, use plain Blade + JavaScript. If needed, add Livewire or Alpine for lightweight interactivity without introducing excessive complexity.

---

## 10. Data Flow and Interaction Model

### Browser -> Laravel Controller -> Database -> View
This is the standard pattern for the Laravel implementation.

Example flow:
- user submits document upload form
- route handles request
- controller validates data
- service stores file and record
- redirect or return response

### File flow
- uploaded PDF enters Laravel request
- file saved in storage disk
- metadata recorded in documents table
- document route returns public or protected URL

---

## 11. Recommended Project Structure

A typical Laravel structure for this project would include:

- app/Http/Controllers/Admin/
- app/Http/Controllers/Public/
- app/Models/User.php
- app/Models/Document.php
- app/Models/Request.php
- app/Services/
- database/migrations/
- database/seeders/
- resources/views/admin/
- resources/views/public/
- routes/web.php
- routes/api.php
- public/storage symlink

---

## 12. Migration Plan

### Phase 1: Foundation
- set up Laravel project
- configure SQL database
- create migrations for users, documents, requests, categories
- establish authentication

### Phase 2: Document features
- convert upload workflow
- implement file storage and validation
- build document listing and search

### Phase 3: Request workflow
- create public request form
- persist requests in the database
- add admin request management

### Phase 4: Refinement
- add authorization and access policies
- improve UI and validation
- enhance notifications and reporting

---

## 13. Risks to Address During Migration

- keeping legacy file-based thinking instead of using database-first design
- weak validation of uploaded PDFs
- overexposing public documents without permission rules
- incomplete request persistence
- poor role separation between admin and public access

---

## 14. Summary

The current project is a prototype that proves the core business workflow. The Laravel migration should preserve that workflow while improving the foundation: a proper SQL database, secure authentication, strong validation, better file handling, and a maintainable MVC structure.

This migration is the correct next step because it turns the project from a simple local office tool into a robust, scalable government document management system.
