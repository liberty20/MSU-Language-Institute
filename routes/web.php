<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;

use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('service-requests', ServiceRequestController::class);
    Route::resource('quotations', QuotationController::class);
    Route::resource('tasks', TaskController::class);
    Route::resource('assignments', AssignmentController::class);
    Route::resource('procurement-requests', ProcurementRequestController::class);
    Route::resource('reports', ReportController::class);
    Route::post('approvals', [ApprovalController::class, 'store'])->name('approvals.store');
});

require __DIR__.'/auth.php';
