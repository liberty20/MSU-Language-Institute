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
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NoticeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $courses = \App\Models\Course::with('department')
        ->where('is_published', true)
        ->get();

    $intakes = \App\Models\CourseIntake::with(['course', 'instructor'])
        ->where('status', 'open')
        ->get();

    $contactInfo = \App\Models\SystemSetting::get('short_courses_contact_info', [
        'email' => 'language.institute@msu.ac.zw',
        'phone' => '+263 54 2260331',
        'mobile' => '+263 772 123 456',
        'location' => 'MSU Gweru Main Campus, Gweru, Zimbabwe',
        'hours' => 'Monday - Friday: 8:00 AM - 4:30 PM'
    ]);

    return Inertia::render('Welcome', [
        'courses' => $courses,
        'intakes' => $intakes,
        'contactInfo' => $contactInfo,
        'canLogin' => Route::has('login'),
    ]);
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();
        
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();
        
        return response()->json([
            'status' => 'success',
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput,
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
    $hasCourses = \Illuminate\Support\Facades\Schema::hasTable('courses');
    $hasIntakes = \Illuminate\Support\Facades\Schema::hasTable('course_intakes');
    $hasEnrollments = \Illuminate\Support\Facades\Schema::hasTable('course_enrollments');
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('service_requests');
    
    $departmentCount = \DB::table('departments')->count();
    $courseCount = \DB::table('courses')->count();
    $enrollmentCount = \DB::table('course_enrollments')->count();
    $expert = \App\Models\User::where('email', 'expert@msunli.edu')->first();
    $expertDept = ($expert && $expert->department) ? $expert->department->code : null;
    
    return response()->json([
        'has_notes' => $hasNotes,
        'has_payments' => $hasPaymentsTable,
        'has_bank_accounts' => $hasBankAccountsTable,
        'has_courses' => $hasCourses,
        'has_intakes' => $hasIntakes,
        'has_enrollments' => $hasEnrollments,
        'service_requests_columns' => $columns,
        'departments_count' => $departmentCount,
        'courses_count' => $courseCount,
        'enrollments_count' => $enrollmentCount,
        'expert_department_code' => $expertDept,
    ]);
});

Route::get('courses-catalog', [CourseController::class, 'publicCatalog'])->name('courses.catalog');
Route::get('short-courses', [CourseController::class, 'publicPortal'])->name('courses.public');
Route::post('courses/apply', [CourseController::class, 'submitApplication'])->name('courses.apply');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Short Courses Management (Admin/Staff)
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('course-intakes', [CourseController::class, 'storeIntake'])->name('course-intakes.store');
    Route::put('course-intakes/{intake}', [CourseController::class, 'updateIntake'])->name('course-intakes.update');
    Route::get('course-enrollments', [CourseController::class, 'enrollments'])->name('course-enrollments.index');
    Route::get('course-enrollments/export', [CourseController::class, 'exportReport'])->name('course-enrollments.export');
    Route::get('course-enrollments/{intakeId}/export-students', [CourseController::class, 'exportCourseStudents'])->name('course-enrollments.export-students');
    Route::post('course-enrollments/manual', [CourseController::class, 'manualEnroll'])->name('course-enrollments.manual');
    Route::post('course-enrollments/{enrollment}/verify', [CourseController::class, 'verifyPayment'])->name('course-enrollments.verify');
    Route::post('course-enrollments/{enrollment}/certificate', [CourseController::class, 'issueCertificate'])->name('course-enrollments.certificate');

    // Course Applications Review Workflow (Admin/Staff)
    Route::get('course-applications', [CourseController::class, 'applicationsList'])->name('course-applications.index');
    Route::get('course-applications/{id}', [CourseController::class, 'applicationDetails'])->name('course-applications.show');
    Route::get('course-applications/{id}/download/{type}', [CourseController::class, 'downloadApplicationFile'])->name('course-applications.download-file');
    Route::match(['post', 'put', 'patch'], 'course-applications/{id}/verify', [CourseController::class, 'verifyApplication'])->name('course-applications.verify');
    Route::match(['post', 'put', 'patch'], 'course-applications/{id}/recommend', [CourseController::class, 'recommendApplication'])->name('course-applications.recommend');
    Route::match(['post', 'put', 'patch'], 'course-applications/{id}/approve', [CourseController::class, 'approveApplication'])->name('course-applications.approve');
    Route::match(['post', 'put', 'patch'], 'course-applications/{id}/reject', [CourseController::class, 'rejectApplication'])->name('course-applications.reject');

    // Student Enrollment Routes
    Route::get('my-courses', [CourseController::class, 'studentDashboard'])->name('student.courses');

    // Centralized Notifications Routes
    Route::get('notifications', [DashboardController::class, 'getNotifications'])->name('notifications.index');
    Route::post('notifications/{id}/read', [DashboardController::class, 'markNotificationAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [DashboardController::class, 'markAllNotificationsAsRead'])->name('notifications.read-all');
    Route::get('notifications-history', [DashboardController::class, 'notificationsHistory'])->name('notifications.history');

    // Notices Management Routes (Admin + Students)
    Route::get('notices', [NoticeController::class, 'index'])->name('notices.index');
    Route::post('notices', [NoticeController::class, 'store'])->name('notices.store');
    Route::post('notices/{notice}', [NoticeController::class, 'update'])->name('notices.update');
    Route::post('notices/{notice}/publish', [NoticeController::class, 'publish'])->name('notices.publish');
    Route::delete('notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');

    // Centralized Messaging / Chat Routes
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/messages/{contactId}', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('chat/messages', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('chat/read/{contactId}', [ChatController::class, 'markAsRead'])->name('chat.read');
    Route::get('chat/unread-count', [ChatController::class, 'getUnreadCount'])->name('chat.unread-count');

    // Student Short Course Portal Routes
    Route::middleware('role:student')->group(function () {
        Route::get('student/continuous-assessment', [\App\Http\Controllers\StudentPortalController::class, 'caMarks'])->name('student.ca');
        Route::get('student/continuous-assessment/download', [\App\Http\Controllers\StudentPortalController::class, 'downloadCaReport'])->name('student.ca.download');
        Route::get('student/timetable', [\App\Http\Controllers\StudentPortalController::class, 'timetable'])->name('student.timetable');
        Route::get('student/assignments', [\App\Http\Controllers\StudentPortalController::class, 'assignments'])->name('student.assignments');
        Route::post('student/assignments/{assignment}/submit', [\App\Http\Controllers\StudentPortalController::class, 'submitAssignment'])->name('student.assignments.submit');
        Route::post('student/testimonials', [\App\Http\Controllers\StudentPortalController::class, 'submitTestimonial'])->name('student.testimonials.store');
        Route::get('student/learning-content', [\App\Http\Controllers\StudentPortalController::class, 'learningContent'])->name('student.learning-content');
    });

    // Instructor Short Course Portal Routes
    Route::middleware('instructor')->group(function () {
        Route::get('instructor/enrollments', [\App\Http\Controllers\InstructorPortalController::class, 'enrollments'])->name('instructor.enrollments');
        Route::get('instructor/enrollments/export/{intakeId}', [\App\Http\Controllers\InstructorPortalController::class, 'exportEnrollments'])->name('instructor.enrollments.export');
        
        Route::get('instructor/timetable', [\App\Http\Controllers\InstructorPortalController::class, 'timetableIndex'])->name('instructor.timetable.index');
        Route::post('instructor/timetable', [\App\Http\Controllers\InstructorPortalController::class, 'timetableStore'])->name('instructor.timetable.store');
        Route::post('instructor/timetable/copy', [\App\Http\Controllers\InstructorPortalController::class, 'timetableCopy'])->name('instructor.timetable.copy');
        Route::put('instructor/timetable/{id}', [\App\Http\Controllers\InstructorPortalController::class, 'timetableUpdate'])->name('instructor.timetable.update');
        Route::delete('instructor/timetable/{id}', [\App\Http\Controllers\InstructorPortalController::class, 'timetableDestroy'])->name('instructor.timetable.destroy');
        
        Route::get('instructor/ca', [\App\Http\Controllers\InstructorPortalController::class, 'caIndex'])->name('instructor.ca.index');
        Route::post('instructor/ca/store', [\App\Http\Controllers\InstructorPortalController::class, 'caStore'])->name('instructor.ca.store');
        Route::post('instructor/ca/bulk-upload', [\App\Http\Controllers\InstructorPortalController::class, 'caBulkUpload'])->name('instructor.ca.bulk-upload');
        
        Route::get('instructor/assignments', [\App\Http\Controllers\InstructorPortalController::class, 'assignmentsIndex'])->name('instructor.assignments.index');
        Route::post('instructor/assignments', [\App\Http\Controllers\InstructorPortalController::class, 'assignmentsStore'])->name('instructor.assignments.store');
        Route::put('instructor/assignments/{assignment}', [\App\Http\Controllers\InstructorPortalController::class, 'assignmentsUpdate'])->name('instructor.assignments.update');
        Route::delete('instructor/assignments/{assignment}', [\App\Http\Controllers\InstructorPortalController::class, 'assignmentsDestroy'])->name('instructor.assignments.destroy');
        Route::get('instructor/assignments/{assignment}/submissions', [\App\Http\Controllers\InstructorPortalController::class, 'assignmentsSubmissions'])->name('instructor.assignments.submissions');
        Route::post('instructor/submissions/{submission}/grade', [\App\Http\Controllers\InstructorPortalController::class, 'gradeSubmission'])->name('instructor.submissions.grade');
        Route::get('instructor/completed-tasks', [\App\Http\Controllers\InstructorPortalController::class, 'completedTasks'])->name('instructor.completed-tasks');
        
        // Learning Content Management Routes
        Route::get('instructor/learning-content', [\App\Http\Controllers\InstructorPortalController::class, 'learningContentIndex'])->name('instructor.learning-content.index');
        Route::post('instructor/learning-content', [\App\Http\Controllers\InstructorPortalController::class, 'learningContentStore'])->name('instructor.learning-content.store');
        Route::put('instructor/learning-content/{id}', [\App\Http\Controllers\InstructorPortalController::class, 'learningContentUpdate'])->name('instructor.learning-content.update');
        Route::delete('instructor/learning-content/{id}', [\App\Http\Controllers\InstructorPortalController::class, 'learningContentDestroy'])->name('instructor.learning-content.destroy');
    });

    Route::resource('clients', ClientController::class);
    Route::resource('service-requests', ServiceRequestController::class);
    Route::post('service-requests/{service_request}/attach', [ServiceRequestController::class, 'attachDocument'])->name('service-requests.attach');
    Route::post('service-requests/{service_request}/deliver', [ServiceRequestController::class, 'deliverRequest'])->name('service-requests.deliver');
    Route::get('documents/{document}/download', [ServiceRequestController::class, 'downloadDocument'])->name('documents.download');
    
    // Centralized Secure File Preview and Download
    Route::get('files/preview/{type}/{id}/{field?}', [\App\Http\Controllers\FileManagementController::class, 'preview'])->name('files.preview');
    Route::get('files/download/{type}/{id}/{field?}', [\App\Http\Controllers\FileManagementController::class, 'download'])->name('files.download');
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
        Route::get('audit-trail', [DashboardController::class, 'auditTrail'])->name('audit-trail');
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::resource('users', UserController::class);
        
        // System Settings Panel routes
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/units', [\App\Http\Controllers\Admin\SettingsController::class, 'storeUnit'])->name('settings.units.store');
        Route::put('settings/units/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'updateUnit'])->name('settings.units.update');
        Route::delete('settings/units/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroyUnit'])->name('settings.units.destroy');

        Route::post('settings/sections', [\App\Http\Controllers\Admin\SettingsController::class, 'storeSection'])->name('settings.sections.store');
        Route::put('settings/sections/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSection'])->name('settings.sections.update');
        Route::delete('settings/sections/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroySection'])->name('settings.sections.destroy');

        Route::post('settings/roles', [\App\Http\Controllers\Admin\SettingsController::class, 'storeRole'])->name('settings.roles.store');
        Route::delete('settings/roles/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroyRole'])->name('settings.roles.destroy');
        Route::post('settings/short-courses', [\App\Http\Controllers\Admin\SettingsController::class, 'updateShortCoursesPortal'])->name('settings.short-courses.update');
        Route::post('settings/config', [\App\Http\Controllers\Admin\SettingsController::class, 'updateConfig'])->name('settings.config.update');
        Route::post('settings/reset/request', [\App\Http\Controllers\Admin\SettingsController::class, 'initiateResetRequest'])->name('settings.reset.request');
        Route::post('settings/reset/action', [\App\Http\Controllers\Admin\SettingsController::class, 'actionResetRequest'])->name('settings.reset.action');
        Route::post('settings/reset', [\App\Http\Controllers\Admin\SettingsController::class, 'resetData'])->name('settings.reset');
        Route::post('testimonials/approve', [\App\Http\Controllers\Admin\SettingsController::class, 'approveTestimonial'])->name('admin.testimonials.approve');
        Route::post('testimonials/reject', [\App\Http\Controllers\Admin\SettingsController::class, 'rejectTestimonial'])->name('admin.testimonials.reject');
        Route::delete('testimonials/{idx}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroyTestimonial'])->name('admin.testimonials.destroy');
    });

    Route::middleware('role:deputy_director')->group(function () {
        Route::get('deputy/settings', function() {
            return redirect()->route('admin.settings.index');
        })->name('deputy.settings');
    });

    // Profile Management Routes for All Authenticated Users
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
});

require __DIR__.'/auth.php';
