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
    Route::post('service-requests/{serviceRequest}/attach', [ServiceRequestController::class, 'attachDocument'])->name('service-requests.attach');
    Route::post('service-requests/{serviceRequest}/deliver', [ServiceRequestController::class, 'deliverRequest'])->name('service-requests.deliver');
    Route::get('completed-tasks', [ServiceRequestController::class, 'completedTasksIndex'])->name('completed-tasks.index');
    Route::get('reviews', [ServiceRequestController::class, 'reviews'])->name('reviews.index');
    Route::post('service-requests/{serviceRequest}/rate', [ServiceRequestController::class, 'rate'])->name('service-requests.rate');

    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
    Route::resource('assignments', AssignmentController::class);
    Route::post('assignments/{assignment}/complete', [AssignmentController::class, 'completeAssignment'])->name('assignments.complete');
    Route::get('procurement-requests', function () {
        return redirect()->away('https://procurementreq.msu.ac.zw/');
    })->name('procurement-requests.index');
    Route::resource('procurement-requests', ProcurementRequestController::class)->except(['index']);
    
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/download/{id}', [ReportController::class, 'download'])->name('reports.download');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/auth.php';
