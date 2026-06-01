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
            $admins = \App\Models\User::role('admin_assistant')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification('Enrollment', 'New Application Submitted', 'A new student application from "' . $app->name . '" is awaiting verification.', route('course-applications.show', $app->id)));
            }

            // Student applicant confirmation (if registered user matches)
            $user = \App\Models\User::where('email', $app->email)->first();
            if ($user) {
                $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Application Submitted', 'Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been submitted successfully.', route('student.courses')));
            }
        });

        \App\Models\CourseApplication::updated(function ($app) {
            if ($app->isDirty('status')) {
                $user = \App\Models\User::where('email', $app->email)->first();
                
                if ($app->status === 'verified') {
                    $dDs = \App\Models\User::role('deputy_director')->get();
                    foreach ($dDs as $dD) {
                        $dD->notify(new \App\Notifications\SystemNotification('Approvals', 'Application Verified', 'Application for "' . $app->name . '" has been verified and is awaiting recommendation.', route('course-applications.show', $app->id)));
                    }
                    if ($user) {
                        $user->notify(new \App\Notifications\SystemNotification('Enrollment', 'Application Under Review', 'Your application for "' . ($app->course ? $app->course->title : 'Short Course') . '" has been verified and is now under review.', route('student.courses')));
                    }
                } elseif ($app->status === 'recommended') {
                    $eDs = \App\Models\User::role('executive_director')->get();
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
            $dDs = \App\Models\User::role('deputy_director')->get();
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
                    $dDs = \App\Models\User::role('deputy_director')->get();
                    foreach ($dDs as $dD) {
                        $dD->notify(new \App\Notifications\SystemNotification('Approvals', 'Quotation Awaiting Recommendation', 'Quotation Ref #' . $q->id . ' is awaiting recommendation.', route('quotations.show', $q->id)));
                    }
                } elseif ($q->status === 'pending_approval') {
                    $eDs = \App\Models\User::role('executive_director')->get();
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
                $enrollments = \App\Models\CourseEnrollment::where('course_intake_id', $intake->id)->with('user')->get();
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
                $enrollments = \App\Models\CourseEnrollment::where('course_intake_id', $intake->id)->with('user')->get();
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
                $enrollments = \App\Models\CourseEnrollment::where('course_intake_id', $intake->id)->with('user')->get();
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
                $enrollments = \App\Models\CourseEnrollment::where('course_intake_id', $intake->id)->with('user')->get();
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
            $admins = \App\Models\User::role('admin_assistant')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification(
                    'System Alerts',
                    'New Service Request',
                    'A new service request "' . $sr->title . '" has been submitted by ' . ($sr->client ? ($sr->client->organization ?? $sr->client->contact_person) : 'Client') . '.',
                    route('service-requests.show', $sr->id)
                ));
            }
        });

        // 8. Document Submission Triggers (for Administrative Assistant)
        \App\Models\UploadedDocument::created(function ($doc) {
            $uploader = $doc->uploader;
            $admins = \App\Models\User::role('admin_assistant')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification(
                    'System Alerts',
                    'New Document Submission',
                    'A new document "' . $doc->filename . '" has been uploaded by ' . ($uploader ? $uploader->name : 'unknown') . '.',
                    route('dashboard')
                ));
            }
        });

        // 9. Payment / Proof of Payment Triggers (for Administrative Assistant / Secretaries)
        \App\Models\Payment::created(function ($payment) {
            $admins = \App\Models\User::role('admin_assistant')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification(
                    'Approvals',
                    'Payment Verification Required',
                    'A new proof of payment has been uploaded for Quotation Ref #' . $payment->quotation_id . ' and requires verification.',
                    route('payments.index')
                ));
            }
        });

        // 10. User Account Management & Provisioning Triggers (for ICT Administrator)
        \App\Models\User::created(function ($user) {
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
        });

        \App\Models\User::updated(function ($user) {
            if ($user->isDirty('password')) {
                $user->notify(new \App\Notifications\SystemNotification(
                    'System Alerts',
                    'Password Updated',
                    'Your account password has been updated. If you did not do this, please contact the ICT Administrator immediately.',
                    route('profile.edit')
                ));

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
        });

        // 11. Reports Audit & Authorization Triggers
        \App\Models\Report::created(function ($report) {
            $dDs = \App\Models\User::role('deputy_director')->get();
            foreach ($dDs as $dD) {
                $dD->notify(new \App\Notifications\SystemNotification(
                    'Approvals',
                    'New Report Generated',
                    'A new report "' . $report->title . '" has been generated and is awaiting review.',
                    route('reports.index')
                ));
            }

            $eDs = \App\Models\User::role('executive_director')->get();
            foreach ($eDs as $eD) {
                $eD->notify(new \App\Notifications\SystemNotification(
                    'Approvals',
                    'New Report Generated',
                    'A new report "' . $report->title . '" has been generated and is awaiting final authorization.',
                    route('reports.index')
                ));
            }
        });
    }
}
