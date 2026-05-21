<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('service-requests', ServiceRequestController::class);
    Route::resource('quotations', QuotationController::class);
    Route::resource('assignments', AssignmentController::class);
    Route::resource('tasks', TaskController::class);
    Route::resource('procurement-requests', ProcurementRequestController::class);
    
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/download/{id}', [ReportController::class, 'download'])->name('reports.download');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/auth.php';
