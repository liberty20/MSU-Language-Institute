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
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ProfileController;

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

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return response()->json([
            'status' => 'success',
            'output' => \Illuminate\Support\Facades\Artisan::output(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
});

Route::get('/db-test', function () {
    $hasNotes = \Illuminate\Support\Facades\Schema::hasColumn('service_requests', 'notes');
    $hasPaymentsTable = \Illuminate\Support\Facades\Schema::hasTable('payments');
    $hasBankAccountsTable = \Illuminate\Support\Facades\Schema::hasTable('bank_accounts');
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('service_requests');
    
    return response()->json([
        'has_notes' => $hasNotes,
        'has_payments' => $hasPaymentsTable,
        'has_bank_accounts' => $hasBankAccountsTable,
        'service_requests_columns' => $columns,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('service-requests', ServiceRequestController::class);
    Route::post('service-requests/{service_request}/attach', [ServiceRequestController::class, 'attachDocument'])->name('service-requests.attach');
    Route::post('service-requests/{service_request}/deliver', [ServiceRequestController::class, 'deliverRequest'])->name('service-requests.deliver');
    Route::get('documents/{document}/download', [ServiceRequestController::class, 'downloadDocument'])->name('documents.download');
    Route::get('completed-tasks', [ServiceRequestController::class, 'completedTasksIndex'])->name('completed-tasks.index');
    Route::get('reviews', [ServiceRequestController::class, 'reviews'])->name('reviews.index');
    Route::post('service-requests/{service_request}/rate', [ServiceRequestController::class, 'rate'])->name('service-requests.rate');

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

    // Payments routes for clients
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store']);
    Route::get('payments/{payment}/proof', [PaymentController::class, 'viewProof'])->name('payments.proof');

    // Finance routes for executive_director and deputy_director
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('finance/bank-accounts', [FinanceController::class, 'storeBankAccount'])->name('finance.bank-accounts.store');
    Route::put('finance/bank-accounts/{bankAccount}', [FinanceController::class, 'updateBankAccount'])->name('finance.bank-accounts.update');
    Route::patch('finance/bank-accounts/{bankAccount}/toggle', [FinanceController::class, 'toggleBankAccount'])->name('finance.bank-accounts.toggle');
    Route::post('finance/payments/{payment}/verify', [FinanceController::class, 'verifyPayment'])->name('finance.payments.verify');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::resource('users', UserController::class);
    });

    // Profile Management Routes for All Authenticated Users
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
