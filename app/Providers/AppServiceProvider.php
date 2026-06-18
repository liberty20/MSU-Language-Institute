<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public static function getUsersByRole($roleName)
    {
        if (class_exists(\Spatie\Permission\Models\Role::class) && \Spatie\Permission\Models\Role::whereName($roleName)->exists()) {
            return \App\Models\User::role($roleName)->get();
        }
        return collect();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Register DatabaseNotification Lifecycle Event Listeners for Auditing (Disabled under Audit Scope Optimization policy)

        // 1. Course Management Triggers
        \App\Models\Course::updated(function ($course) {
            $intakes = $course->intakes()->with(['instructor', 'enrollments.user'])->get();
            foreach ($intakes as $intake) {
                if ($intake->instructor) {
                    $intake->instructor->notify(new \App\Notifications\SystemNotification('Courses', 'Course Updated', 'The course "' . $course->title . '" has been updated.', route('instructor.enrollments')));
                }
                foreach ($intake->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $enrollment->user->notify(new \App\Notifications\SystemNotification('Courses', 'Course Updated', 'The course "' . $course->title . '" has been updated.', route('student.courses')));
                    }
                }
            }
        });

        \App\Models\CourseIntake::created(function ($intake) {
            if ($intake->instructor_id && $intake->instructor) {
                $intake->instructor->notify(new \App\Notifications\SystemNotification(
                    'Courses',
                    'Assigned to Course Intake',
                    'You have been assigned as the instructor for course "' . ($intake->course ? $intake->course->title : 'Short Course') . '" (Intake: ' . $intake->name . ').',
                    route('instructor.enrollments')
                ));
            }
        });

        \App\Models\CourseIntake::updated(function ($intake) {
            if ($intake->isDirty('instructor_id') && $intake->instructor_id && $intake->instructor) {
                $intake->instructor->notify(new \App\Notifications\SystemNotification(
                    'Courses',
                    'Assigned to Course Intake',
                    'You have been assigned as the instructor for course "' . ($intake->course ? $intake->course->title : 'Short Course') . '" (Intake: ' . $intake->name . ').',
                    route('instructor.enrollments')
                ));
            }
        });

        \App\Models\CourseEnrollment::created(function ($enrollment) {
            $intake = $enrollment->intake;
            $course = $intake ? $intake->course : null;
            $student = $enrollment->user;
            if ($student && $course) {
                $student->notify(new \App\Notifications\SystemNotification(
                    'Enrollment',
                    'Enrolled in Course',
                    'You have been enrolled into course "' . $course->title . '" (Intake: ' . $intake->name . ').',
                    route('student.courses')
                ));
            }
            if ($intake && $intake->instructor) {
                $intake->instructor->notify(new \App\Notifications\SystemNotification(
                    'Enrollment',
                    'New Student Enrolled',
                    'A new student ' . ($student ? $student->name : '') . ' has enrolled into your course "' . ($course ? $course->title : 'Short Course') . '".',
                    route('instructor.enrollments')
                ));
            }
        });

        \App\Models\CourseEnrollment::updated(function ($enrollment) {
            if ($enrollment->isDirty('payment_status') && $enrollment->payment_status === 'verified') {
                $intake = $enrollment->intake;
                $course = $intake ? $intake->course : null;
                $student = $enrollment->user;
                if ($student && $course) {
                    $student->notify(new \App\Notifications\SystemNotification(
                        'Enrollment',
                        'Enrollment Completed',
                        'Your enrollment and payment for course "' . $course->title . '" have been verified and completed.',
                        route('student.courses')
                    ));
                }
                if ($intake && $intake->instructor) {
                    $intake->instructor->notify(new \App\Notifications\SystemNotification(
                        'Enrollment',
                        'Student Enrollment Completed',
                        'Enrollment and payment completed for student ' . ($student ? $student->name : '') . ' in course "' . ($course ? $course->title : 'Short Course') . '".',
                        route('instructor.enrollments')
                    ));
                }
            }
        });

        // 2. Student Enrollment Triggers
        \App\Models\CourseApplication::created(function ($app) {
            $admins = AppServiceProvider::getUsersByRole('admin_assistant');
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification('Enrollment', 'New Application Submitted', 'A new student application from "' . $app->name . '" is awaiting verification.', route('course-applications.show', $app->id)));
            }

            // Student applicant confirmation (if registered user matches)
            $user = \App\Models\User::whereEmail($app->email)->first();
            if ($user) {
                $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Application Submitted', 'Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been submitted successfully.', route('student.courses')));
            }
        });

        \App\Models\CourseApplication::updated(function ($app) {
            if ($app->isDirty('status')) {
                $user = \App\Models\User::whereEmail($app->email)->first();
                
                if ($app->status === 'verified') {
                    $dDs = AppServiceProvider::getUsersByRole('deputy_director');
                    foreach ($dDs as $dD) {
                        $dD->notify(new \App\Notifications\SystemNotification('Approvals', 'Application Verified', 'Application for "' . $app->name . '" has been verified and is awaiting recommendation.', route('course-applications.show', $app->id)));
                    }
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Application Under Review', 'Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been verified and is now under review.', route('student.courses')));
                    }
                } elseif ($app->status === 'recommended') {
                    $eDs = AppServiceProvider::getUsersByRole('executive_director');
                    foreach ($eDs as $eD) {
                        $eD->notify(new \App\Notifications\SystemNotification('Approvals', 'Application Recommended', 'Application for "' . $app->name . '" has been recommended and is awaiting final approval.', route('course-applications.show', $app->id)));
                    }
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Application Recommended', 'Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been recommended for approval.', route('student.courses')));
                    }
                } elseif ($app->status === 'approved') {
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Enrollment Approved', 'Congratulations! Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been approved.', route('student.courses')));
                    }
                }
            }
        });

        // 3. Quotations Triggers
        \App\Models\Quotation::created(function ($q) {
            $dDs = AppServiceProvider::getUsersByRole('deputy_director');
            foreach ($dDs as $dD) {
                $dD->notify(new \App\Notifications\SystemNotification('Approvals', 'New Quotation Drafted', 'A new quotation Reference #' . $q->id . ' has been created and is awaiting recommendation.', route('quotations.show', $q->id)));
            }
            $creator = \App\Models\User::find($q->prepared_by);
            if ($creator) {
                $creator->notify(new \App\Notifications\SystemNotification('Quotations', 'Quotation Drafted', 'You have drafted a new quotation Reference #' . $q->id . '.', route('quotations.show', $q->id)));
            }
        });

        \App\Models\Quotation::updated(function ($q) {
            if ($q->isDirty('status')) {
                if ($q->status === 'submitted') {
                    $dDs = AppServiceProvider::getUsersByRole('deputy_director');
                    foreach ($dDs as $dD) {
                        $dD->notify(new \App\Notifications\SystemNotification('Approvals', 'Quotation Awaiting Recommendation', 'Quotation Ref #' . $q->id . ' is awaiting recommendation.', route('quotations.show', $q->id)));
                    }
                } elseif ($q->status === 'pending_approval') {
                    $eDs = AppServiceProvider::getUsersByRole('executive_director');
                    foreach ($eDs as $eD) {
                        $eD->notify(new \App\Notifications\SystemNotification('Approvals', 'Quotation Awaiting Approval', 'Quotation Ref #' . $q->id . ' is awaiting final approval.', route('quotations.show', $q->id)));
                    }
                } elseif ($q->status === 'approved') {
                    $user = \App\Models\User::find($q->prepared_by);
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Quotations', 'Quotation Approved', 'Quotation Ref #' . $q->id . ' has been approved.', route('quotations.show', $q->id)));
                    }
                } elseif ($q->status === 'rejected') {
                    $user = \App\Models\User::find($q->prepared_by);
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Quotations', 'Quotation Rejected', 'Quotation Ref #' . $q->id . ' has been rejected.', route('quotations.show', $q->id)));
                    }
                }
            }
        });

        // 4. Task Management Triggers
        \App\Models\Assignment::created(function ($a) {
            $user = $a->assignedTo;
            $by = $a->assignedBy;
            if ($user) {
                $user->notify(new \App\Notifications\SystemNotification('Tasks', 'New Task Assignment', 'You have been assigned a new task: "' . $a->role_in_task . '"' . ($by ? ' by ' . $by->name : '') . '.', route('completed-tasks.index')));
            }
        });

        \App\Models\Assignment::updated(function ($a) {
            if ($a->isDirty('assigned_to')) {
                $newAssignee = \App\Models\User::find($a->assigned_to);
                $oldAssignee = \App\Models\User::find($a->getOriginal('assigned_to'));
                $by = $a->assignedBy;
                
                if ($newAssignee) {
                    $newAssignee->notify(new \App\Notifications\SystemNotification(
                        'Tasks',
                        'Task Assigned (Reassignment)',
                        'You have been assigned a task: "' . $a->role_in_task . '"' . ($by ? ' by ' . $by->name : '') . '.',
                        route('completed-tasks.index')
                    ));
                }
                
                if ($oldAssignee) {
                    $oldAssignee->notify(new \App\Notifications\SystemNotification(
                        'Tasks',
                        'Task Reassigned',
                        'The task "' . $a->role_in_task . '" previously assigned to you has been reassigned to someone else.',
                        route('completed-tasks.index')
                    ));
                }
            }
        });

        \App\Models\Task::created(function ($task) {
            $a = $task->assignment;
            if ($a && $a->assignedTo) {
                $a->assignedTo->notify(new \App\Notifications\SystemNotification('Tasks', 'New Task Added', 'A new sub-task has been added to your assignment: "' . $task->title . '".', route('completed-tasks.index')));
            }
        });

        \App\Models\Task::updated(function ($task) {
            if ($task->isDirty('status') && $task->status === 'completed') {
                $a = $task->assignment;
                if ($a && $a->assignedBy) {
                    $a->assignedBy->notify(new \App\Notifications\SystemNotification('Tasks', 'Task Completed', 'Your assigned task "' . $task->title . '" has been completed.', route('completed-tasks.index')));
                }
            }
        });

        // 5. Assessments Triggers
        \App\Models\CourseAssignment::created(function ($ca) {
            $intake = $ca->intake;
            if ($intake) {
                $enrollments = \App\Models\CourseEnrollment::whereCourseIntakeId($intake->id)->with('user')->get();
                foreach ($enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $enrollment->user->notify(new \App\Notifications\SystemNotification('Assessments', 'New Course Assignment', 'A new coursework assignment "' . $ca->title . '" has been published.', route('student.assignments')));
                    }
                }
            }
        });

        \App\Models\CourseAssignmentSubmission::created(function ($cas) {
            $ca = $cas->assignment;
            $intake = $ca ? $ca->intake : null;
            if ($intake && $intake->instructor) {
                $intake->instructor->notify(new \App\Notifications\SystemNotification('Assessments', 'Assignment Submission', 'A student has uploaded a submission for assignment "' . $ca->title . '".', route('instructor.assignments.index')));
            }
        });

        \App\Models\CourseAssignmentSubmission::updated(function ($cas) {
            if ($cas->isDirty('grade') || $cas->isDirty('feedback')) {
                $student = $cas->user;
                $ca = $cas->assignment;
                if ($student && $ca) {
                    $student->notify(new \App\Notifications\SystemNotification('Assessments', 'Assignment Graded', 'Your submission for assignment "' . $ca->title . '" has been graded.', route('student.assignments')));
                }
            }
        });

        \App\Models\CourseCaMark::saved(function ($mark) {
            $student = $mark->user;
            $course = $mark->course;
            if ($student) {
                $student->notify(new \App\Notifications\SystemNotification('Assessments', 'Marks Uploaded', 'New assessment marks have been posted for course "' . ($course ? $course->title : 'Short Course') . '".', route('student.ca')));
            }
        });

        // 6. Timetable Triggers
        \App\Models\CourseTimetable::created(function ($ct) {
            $intake = $ct->intake;
            if ($intake) {
                $enrollments = \App\Models\CourseEnrollment::whereCourseIntakeId($intake->id)->with('user')->get();
                foreach ($enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $enrollment->user->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Added', 'A new class schedule has been posted for "' . $intake->course->title . '".', route('student.timetable')));
                    }
                }
                if ($intake->instructor) {
                    $intake->instructor->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Added', 'A new class schedule slot has been posted for your course "' . $intake->course->title . '".', route('instructor.timetable.index')));
                }
            }
        });

        \App\Models\CourseTimetable::updated(function ($ct) {
            $intake = $ct->intake;
            if ($intake) {
                $enrollments = \App\Models\CourseEnrollment::whereCourseIntakeId($intake->id)->with('user')->get();
                foreach ($enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $enrollment->user->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Updated', 'Class schedule has been adjusted for "' . $intake->course->title . '".', route('student.timetable')));
                    }
                }
                if ($intake->instructor) {
                    $intake->instructor->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Updated', 'Class schedule slot has been adjusted for your course "' . $intake->course->title . '".', route('instructor.timetable.index')));
                }
            }
        });

        \App\Models\CourseTimetable::deleted(function ($ct) {
            $intake = $ct->intake;
            if ($intake) {
                $enrollments = \App\Models\CourseEnrollment::whereCourseIntakeId($intake->id)->with('user')->get();
                foreach ($enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $enrollment->user->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Cancelled', 'Class scheduled for "' . $intake->course->title . '" has been cancelled.', route('student.timetable')));
                    }
                }
                if ($intake->instructor) {
                    $intake->instructor->notify(new \App\Notifications\SystemNotification('Timetables', 'Class Timetable Cancelled', 'Class scheduled slot for your course "' . $intake->course->title . '" has been cancelled.', route('instructor.timetable.index')));
                }
            }
        });

        // 7. Service Requests / Quotations Requests Triggers (for Administrative Assistant)
        \App\Models\ServiceRequest::created(function ($sr) {
            if (\Spatie\Permission\Models\Role::whereName('admin_assistant')->exists()) {
                $admins = \App\Models\User::role('admin_assistant')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\SystemNotification(
                        'System Alerts',
                        'New Service Request',
                        'A new service request "' . $sr->title . '" has been submitted by ' . ($sr->client ? ($sr->client->organization ?? $sr->client->contact_person) : 'Client') . '.',
                        route('service-requests.show', $sr->id)
                    ));
                }
            }
        });

        // 8. Document Submission Triggers (for Administrative Assistant)
        \App\Models\UploadedDocument::created(function ($doc) {
            $uploader = $doc->uploader;
            if (\Spatie\Permission\Models\Role::whereName('admin_assistant')->exists()) {
                $admins = \App\Models\User::role('admin_assistant')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\SystemNotification(
                        'System Alerts',
                        'New Document Submission',
                        'A new document "' . $doc->filename . '" has been uploaded by ' . ($uploader ? $uploader->name : 'unknown') . '.',
                        route('dashboard')
                    ));
                }
            }
        });

        // 9. Payment / Proof of Payment Triggers (for Administrative Assistant / Secretaries)
        \App\Models\Payment::created(function ($payment) {
            if (\Spatie\Permission\Models\Role::whereName('admin_assistant')->exists()) {
                $admins = \App\Models\User::role('admin_assistant')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\SystemNotification(
                        'Approvals',
                        'Payment Verification Required',
                        'A new proof of payment has been uploaded for Quotation Ref #' . $payment->quotation_id . ' and requires verification.',
                        route('payments.index')
                    ));
                }
            }
        });

        // 10. User Account Management & Provisioning Triggers (for ICT Administrator)
        \App\Models\User::created(function ($user) {
            if (\Spatie\Permission\Models\Role::whereName('ict_administrator')->exists()) {
                $ictAdmins = \App\Models\User::role('ict_administrator')->get();
                $roleName = $user->hasRole('student') ? 'Student' : ($user->roles->first() ? $user->roles->first()->name : 'User');
                
                foreach ($ictAdmins as $ict) {
                    $ict->notify(new \App\Notifications\SystemNotification(
                        'System Alerts',
                        'User Account Created',
                        'A new ' . $roleName . ' account has been provisioned for "' . $user->name . '" (' . $user->email . ').',
                        route('admin.users.index')
                    ));
                }
            }
        });

        \App\Models\User::updated(function ($user) {
            if ($user->isDirty('password')) {
                $user->notify(new \App\Notifications\SystemNotification(
                    'System Alerts',
                    'Password Updated',
                    'Your account password has been updated. If you did not do this, please contact the ICT Administrator immediately.',
                    route('profile.edit')
                ));

                if (\Spatie\Permission\Models\Role::whereName('ict_administrator')->exists()) {
                    $ictAdmins = \App\Models\User::role('ict_administrator')->get();
                    foreach ($ictAdmins as $ict) {
                        $ict->notify(new \App\Notifications\SystemNotification(
                            'System Alerts',
                            'User Password Changed',
                            'The password for user "' . $user->name . '" (' . $user->email . ') has been updated.',
                            route('admin.users.index')
                        ));
                    }
                }
            }
        });

        // 11. Reports Audit & Authorization Triggers
        \App\Models\Report::created(function ($report) {
            if (\Spatie\Permission\Models\Role::whereName('deputy_director')->exists()) {
                $dDs = \App\Models\User::role('deputy_director')->get();
                foreach ($dDs as $dD) {
                    $dD->notify(new \App\Notifications\SystemNotification(
                        'Approvals',
                        'New Report Generated',
                        'A new report "' . $report->title . '" has been generated and is awaiting review.',
                        route('reports.index')
                    ));
                }
            }

            if (\Spatie\Permission\Models\Role::whereName('executive_director')->exists()) {
                $eDs = \App\Models\User::role('executive_director')->get();
                foreach ($eDs as $eD) {
                    $eD->notify(new \App\Notifications\SystemNotification(
                        'Approvals',
                        'New Report Generated',
                        'A new report "' . $report->title . '" has been generated and is awaiting final authorization.',
                        route('reports.index')
                    ));
                }
            }
        });

        // DB Query Listener for Data Preservation Backup (limited to composite pivot tables only)
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (!\App\Services\UserBackupService::$shouldBackup) {
                return;
            }

            $sql = strtolower($query->sql);
            if (
                strpos($sql, 'insert') !== false ||
                strpos($sql, 'update') !== false ||
                strpos($sql, 'delete') !== false
            ) {
                if (
                    strpos($sql, 'model_has_roles') !== false ||
                    strpos($sql, 'role_has_permissions') !== false ||
                    strpos($sql, 'model_has_permissions') !== false
                ) {
                    \App\Services\UserBackupService::backup(true);
                }

                // If role changes, trigger user-client synchronization
                if (strpos($sql, 'model_has_roles') !== false) {
                    $userIds = [];
                    foreach ($query->bindings as $binding) {
                        if (is_numeric($binding)) {
                            $userIds[] = (int)$binding;
                        }
                    }
                    preg_match_all('/\b\d+\b/', $query->sql, $matches);
                    foreach ($matches[0] as $match) {
                        $userIds[] = (int)$match;
                    }
                    $userIds = array_unique($userIds);
                    foreach ($userIds as $userId) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $user->unsetRelation('roles');
                            \App\Services\UserBackupService::syncFromUser($user);
                        }
                    }
                }
            }
        });

        // Artisan Command Starting Listener to Disable Backup during Database Operations
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\CommandStarting::class, function ($event) {
            if (app()->runningUnitTests()) {
                return;
            }
            if (in_array($event->command, ['migrate', 'db:seed', 'migrate:fresh', 'migrate:refresh', 'migrate:rollback', 'migrate:reset'])) {
                \App\Services\UserBackupService::$shouldBackup = false;
            }
        });

        // Artisan Command Finished Listener for Automated Restore
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\CommandFinished::class, function ($event) {
            if (app()->runningUnitTests()) {
                return;
            }
            if (in_array($event->command, ['migrate', 'db:seed', 'migrate:fresh', 'migrate:refresh', 'migrate:rollback', 'migrate:reset'])) {
                \App\Services\UserBackupService::$shouldBackup = true;
                \App\Services\UserBackupService::restore(true, false);
            }
        });

        // Register model observers to trigger backup safely on primary model changes
        $backupModels = [
            \App\Models\User::class,
            \App\Models\Client::class,
            \App\Models\Course::class,
            \App\Models\CourseIntake::class,
            \App\Models\CourseEnrollment::class,
            \App\Models\SystemSetting::class,
            \App\Models\BankAccount::class,
            \App\Models\Department::class,
            \App\Models\MsunliSection::class,
            \App\Models\MsunliRole::class,
            \App\Models\ServiceRequest::class,
            \App\Models\Quotation::class,
            \App\Models\Assignment::class,
            \App\Models\Task::class,
            \App\Models\Approval::class,
            \App\Models\Payment::class,
            \App\Models\UploadedDocument::class,
            \App\Models\CourseApplication::class,
            \App\Models\CourseApplicationLog::class,
            \App\Models\CourseTimetable::class,
            \App\Models\CourseAssignment::class,
            \App\Models\CourseAssignmentSubmission::class,
            \App\Models\CourseCaMark::class,
            \App\Models\ProcurementRequest::class,
            \App\Models\KpiRecord::class,
            \App\Models\StaffSchedule::class,
            \App\Models\Comment::class,
            \App\Models\Report::class,
            \App\Models\EmailLog::class,
            \App\Models\LearningContent::class,
            \Spatie\Permission\Models\Role::class,
            \Spatie\Permission\Models\Permission::class,
        ];

        foreach ($backupModels as $modelClass) {
            $modelClass::saved(function () {
                \App\Services\UserBackupService::backup(true);
            });
            $modelClass::deleted(function () {
                \App\Services\UserBackupService::backup(true);
            });
        }

        // Auditable Models Observers for Audit Trail
        $auditableModels = [
            \App\Models\User::class,
            \App\Models\Client::class,
            \App\Models\Course::class,
            \App\Models\CourseIntake::class,
            \App\Models\CourseEnrollment::class,
            \App\Models\SystemSetting::class,
            \App\Models\BankAccount::class,
            \App\Models\Department::class,
            \App\Models\MsunliSection::class,
            \App\Models\MsunliRole::class,
            \App\Models\ServiceRequest::class,
            \App\Models\Quotation::class,
            \App\Models\Assignment::class,
            \App\Models\Task::class,
        ];

        foreach ($auditableModels as $modelClass) {
            $modelClass::updating(function ($model) {
                $dirty = $model->getDirty();
                if (empty($dirty)) {
                    return;
                }

                $previousValues = [];
                $newValues = [];
                foreach ($dirty as $key => $value) {
                    if ($key === 'updated_at' || $key === 'created_at') {
                        continue;
                    }
                    $previousValues[$key] = $model->getOriginal($key);
                    $newValues[$key] = $value;
                }

                if (empty($newValues)) {
                    return;
                }

                $user = auth()->user();
                $userStr = $user ? ($user->name . ' (' . $user->email . ')') : 'system/guest';

                $properties = [
                    'user_performing_action' => $userStr,
                    'date_and_time' => now()->toIso8601String(),
                    'previous_values' => $previousValues,
                    'new_values' => $newValues,
                    'ip_address' => request() ? request()->ip() : null,
                    'action_type' => 'Update',
                ];

                $className = class_basename($model);
                \App\Models\ActivityLog::log(
                    strtolower($className) . '_updated',
                    "Updated {$className} #{$model->id}",
                    $model,
                    $properties
                );
            });

            $modelClass::created(function ($model) {
                $user = auth()->user();
                $userStr = $user ? ($user->name . ' (' . $user->email . ')') : 'system/guest';

                $values = $model->toArray();
                if ($model instanceof \App\Models\User) {
                    unset($values['password'], $values['remember_token']);
                }

                $properties = [
                    'user_performing_action' => $userStr,
                    'date_and_time' => now()->toIso8601String(),
                    'values' => $values,
                    'ip_address' => request() ? request()->ip() : null,
                    'action_type' => 'Create',
                ];

                $className = class_basename($model);
                \App\Models\ActivityLog::log(
                    strtolower($className) . '_created',
                    "Created {$className} #{$model->id}",
                    $model,
                    $properties
                );
            });

            $modelClass::deleted(function ($model) {
                $user = auth()->user();
                $userStr = $user ? ($user->name . ' (' . $user->email . ')') : 'system/guest';

                $values = $model->toArray();
                if ($model instanceof \App\Models\User) {
                    unset($values['password'], $values['remember_token']);
                }

                $properties = [
                    'user_performing_action' => $userStr,
                    'date_and_time' => now()->toIso8601String(),
                    'values' => $values,
                    'ip_address' => request() ? request()->ip() : null,
                    'action_type' => 'Delete',
                ];

                $className = class_basename($model);
                \App\Models\ActivityLog::log(
                    strtolower($className) . '_deleted',
                    "Deleted {$className} #{$model->id}",
                    $model,
                    $properties
                );
            });
        }

        // Relational and hierarchy consistency guards
        \App\Models\CourseEnrollment::saving(function ($enrollment) {
            if (!\App\Models\User::whereId($enrollment->user_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated user does not exist.");
            }
            if (!\App\Models\CourseIntake::whereId($enrollment->course_intake_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated course intake does not exist.");
            }
        });

        \App\Models\CourseIntake::saving(function ($intake) {
            if (!\App\Models\Course::whereId($intake->course_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated course does not exist.");
            }
            if ($intake->instructor_id) {
                $instructor = \App\Models\User::with('department')->find($intake->instructor_id);
                if (!$instructor) {
                    throw new \InvalidArgumentException("Invalid relationship: The associated instructor does not exist.");
                }
                if (!$instructor->is_active) {
                    throw new \InvalidArgumentException("Invalid relationship: Selected instructor is inactive.");
                }
                if ($instructor->department && $instructor->department->code === 'AOS') {
                    throw new \InvalidArgumentException("Invalid relationship: AOS staff members cannot be assigned as instructors.");
                }
            }
            if ($intake->start_date && $intake->end_date && strtotime($intake->end_date) <= strtotime($intake->start_date)) {
                throw new \InvalidArgumentException("Validation error: End date must be after start date.");
            }
        });

        \App\Models\ServiceRequest::saving(function ($sr) {
            if (!\App\Models\Client::whereId($sr->client_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated client does not exist.");
            }
        });

        \App\Models\Quotation::saving(function ($q) {
            if (!\App\Models\ServiceRequest::whereId($q->service_request_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated service request does not exist.");
            }
        });

        \App\Models\Assignment::saving(function ($a) {
            if (!\App\Models\ServiceRequest::whereId($a->service_request_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated service request does not exist.");
            }
            if (!\App\Models\User::whereId($a->assigned_to)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The assigned user does not exist.");
            }
        });

        \App\Models\Task::saving(function ($t) {
            if (!\App\Models\Assignment::whereId($t->assignment_id)->exists()) {
                throw new \InvalidArgumentException("Invalid relationship: The associated assignment does not exist.");
            }
        });

        \App\Models\User::saving(function ($user) {
            if ($user->section_id && $user->department_id) {
                $section = \App\Models\MsunliSection::find($user->section_id);
                if ($section && (int)$section->unit_id !== (int)$user->department_id) {
                    throw new \InvalidArgumentException("Invalid hierarchy: The selected section does not belong to the selected Unit.");
                }
            }
            if ($user->msunli_role_id && $user->section_id) {
                $role = \App\Models\MsunliRole::find($user->msunli_role_id);
                if ($role && (int)$role->section_id !== (int)$user->section_id) {
                    throw new \InvalidArgumentException("Invalid hierarchy: The selected role is not configured under the selected section.");
                }
            }
        });

        // Register client & user synchronization observers
        \App\Models\Client::saving(function ($client) {
            \App\Services\UserBackupService::syncFromClient($client);
        });

        \App\Models\Client::deleted(function ($client) {
            \App\Services\UserBackupService::deleteUserForClient($client);
        });

        \App\Models\User::saved(function ($user) {
            // Auto-assign language_expert role if the user belongs to a unit other than AOS, and is not a client or student
            if ($user->department_id) {
                $user->loadMissing('department');
                if ($user->department && $user->department->code !== 'AOS') {
                    $hasExpert = $user->roles()->whereName('language_expert')->exists();

                    $isStudentOrClient = $user->roles()->whereIn('name', ['student', 'client'])->exists();

                    if (!$hasExpert && !$isStudentOrClient) {
                        $role = \Spatie\Permission\Models\Role::whereName('language_expert')->first();
                        if ($role) {
                            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                                'role_id' => $role->id,
                                'model_type' => get_class($user),
                                'model_id' => $user->id
                            ]);
                            // Clear Spatie permission cache
                            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                        }
                    }
                }
            }
            \App\Services\UserBackupService::syncFromUser($user);
        });

        \App\Models\User::deleted(function ($user) {
            \App\Services\UserBackupService::deleteClientForUser($user);
        });

        // Automated Course Intake Expiration Check (Real-time auto completion)
        if (!app()->runningInConsole()) {
            try {
                $expiredIntakesExist = \Illuminate\Support\Facades\DB::table('course_intakes')
                    ->whereRaw('status != ?', ['completed'])
                    ->whereRaw('end_date <= ?', [now()->toDateString()])
                    ->exists();

                if ($expiredIntakesExist) {
                    $intakesToComplete = \App\Models\CourseIntake::whereRaw('status != ?', ['completed'])
                        ->whereRaw('end_date <= ?', [now()->toDateString()])
                        ->get();

                    foreach ($intakesToComplete as $intake) {
                        $intake->status = 'completed';
                        $intake->save();
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Failed to automatically complete expired course intakes: " . $e->getMessage());
            }
        }

        // Audit log for login failures
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            $email = $event->credentials['email'] ?? 'unknown';
            $user = $event->user;

            \App\Models\ActivityLog::log(
                'login_failed',
                "Failed login attempt for email: {$email}",
                $user,
                [
                    'email' => $email,
                    'date_and_time' => now()->toIso8601String(),
                    'ip_address' => request() ? request()->ip() : null,
                ]
            );
        });
    }
}
