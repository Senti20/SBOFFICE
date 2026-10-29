# Architecture Essentials

## 1. Purpose
This project is a document management portal for an SB secretary office. The goal is to move the current prototype into a Laravel + PHP + SQL architecture so that the system becomes more maintainable, secure, and production-ready.

## 2. Target Stack
- PHP
- Laravel Framework
- SQL database (MySQL or PostgreSQL)
- Blade for views
- Eloquent for database access
- Laravel storage for uploaded PDFs

## 3. Core Architecture

### Presentation layer
- admin dashboard for staff tasks
- public portal for records access
- Blade views and simple JavaScript interactions

### Application layer
- routes for staff and public actions
- controllers for document and request flows
- validation and authorization logic
- service classes where business logic becomes more complex

### Data layer
- SQL tables for users, documents, requests, and categories
- Eloquent models managing data access
- database migrations for schema management

### Storage layer
- uploaded PDFs stored using Laravel storage
- metadata retained in the database
- file URLs generated based on secured storage access rules

## 4. Current Prototype vs. Laravel Target
The current prototype works with:
- local file storage in a docs folder
- static HTML pages
- frontend-only request handling in some cases
- direct server-side file access

The Laravel target replaces that with:
- SQL-backed persistence for documents and requests
- structured user management and roles
- validation and authorization based on Laravel conventions
- stable archive management for public records

## 5. Main Functional Modules

### Admin module
- login and secure access
- upload PDF documents
- search and filter records
- manage document metadata
- change request status

### Public module
- public document listing
- keyword search
- open documents
- submit request for certified copy

### Request module
- capture request details
- store status in SQL
- allow admin review and update

## 6. Document Storage Design
In the Laravel version:
- uploaded PDFs are stored in a configured storage disk
- the database stores the associated metadata
- access is controlled by routes and policies instead of raw direct file exposure

This is more secure and more scalable than simply saving everything in a docs folder.

## 7. Key Design Decisions
- Use SQL for all persistent business data
- Use Laravel conventions for routing, controllers, and models
- Keep both admin and public interfaces simple and focused
- Validate all uploads and form submissions
- Separate role-based access clearly between admin and public users

## 8. Strengths of the Laravel Direction
- cleaner and more maintainable code organization
- better data integrity and query support
- more secure authentication and authorization
- easier future scaling and feature development
- aligned with professional web application standards

## 9. Limitations of the Current Prototype
- local file-only storage is fragile for long-term use
- no proper database relationships or indexes
- weak permissions model
- limited persistence for request records
- harder to extend or maintain over time

## 10. Migration Priority
The initial Laravel migration should focus on:
1. users and authentication
2. documents table and upload workflow
3. public document listing and search
4. request records and status management
5. admin dashboard and permissions

## 11. Summary
The architecture should evolve from a local static prototype into a Laravel-based system that stores records in SQL, handles uploads through Laravel storage, and supports secure admin/public workflows. This direction is the correct foundation for a production-ready document portal.
