<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Portal\DocumentController as PortalDocumentController;
use App\Http\Controllers\Portal\RequestController as PortalRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PortalDocumentController::class, 'index'])
    ->name('home');

Route::get('/documents/{document}/file', [PortalDocumentController::class, 'file'])
    ->name('public.documents.file');

/*
|--------------------------------------------------------------------------
| Public Request Routes
|--------------------------------------------------------------------------
*/

Route::get('/requests/create', [PortalRequestController::class, 'create'])
    ->name('public.requests.create');

Route::post('/requests', [PortalRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.requests.store');

Route::get('/requests/list', [PortalRequestController::class, 'index'])
    ->name('public.requests.index');

Route::patch('/requests/{request}/cancel', [PortalRequestController::class, 'cancel'])
    ->name('public.requests.cancel');

Route::delete('/requests/{request}', [PortalRequestController::class, 'destroy'])
    ->name('public.requests.destroy');

Route::get('/requests/{request}/success', [PortalRequestController::class, 'success'])
    ->name('public.requests.success');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/admin/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/admin/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        // Dashboard
        Route::get('/', fn () => redirect()->route('admin.dashboard'));

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Document Management
        |--------------------------------------------------------------------------
        */

        // List documents
        Route::get('/documents', [AdminDocumentController::class, 'index'])
            ->name('documents.index');

        // Show publish document form
        Route::get('/documents/create', [AdminDocumentController::class, 'create'])
            ->name('documents.create');

        // Publish/upload a document
        Route::post('/documents', [AdminDocumentController::class, 'store'])
            ->name('documents.store');

        // Edit document form
        Route::get('/documents/{document}/edit', [AdminDocumentController::class, 'edit'])
            ->name('documents.edit');

        // Update document
        Route::post('/documents/{document}', [AdminDocumentController::class, 'update'])
            ->name('documents.update');

        // Archive document without permanently deleting it
        Route::patch('/documents/{document}/archive', [AdminDocumentController::class, 'archive'])
            ->name('documents.archive');

        // Permanently delete document
        Route::delete('/documents/{document}', [AdminDocumentController::class, 'destroy'])
            ->name('documents.destroy');

        // View/download document file
        Route::get('/documents/{document}/file', [AdminDocumentController::class, 'file'])
            ->name('documents.file');

        /*
        |--------------------------------------------------------------------------
        | Request Management
        |--------------------------------------------------------------------------
        */

        Route::get('/requests', [AdminRequestController::class, 'index'])
            ->name('requests.index');

        Route::put('/requests/{documentRequest}', [AdminRequestController::class, 'update'])
            ->name('requests.update');

        // Permanently delete a Completed or Cancelled request
        Route::delete('/requests/{documentRequest}', [AdminRequestController::class, 'destroy'])
            ->name('requests.destroy');

        // View the valid ID uploaded with a request (admin only)
        Route::get('/requests/{documentRequest}/valid-id', [AdminRequestController::class, 'validId'])
            ->name('requests.valid-id');

        // View the selfie uploaded with a request (admin only)
        Route::get('/requests/{documentRequest}/selfie', [AdminRequestController::class, 'selfie'])
            ->name('requests.selfie');
    });