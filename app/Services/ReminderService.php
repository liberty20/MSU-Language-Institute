<?php

namespace App\Services;

use App\Models\User;
use App\Models\Assignment;
use App\Models\ServiceRequest;
use App\Models\Quotation;
use App\Models\CourseEnrollment;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Payment;
use App\Models\CourseApplication;
use App\Models\CourseIntake;
use App\Models\Task;
use App\Models\ReminderLog;
use App\Models\SystemSetting;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderService
{
    /**
     * Get all outstanding tasks for a user.
     */
    public function getOutstandingTasks(User $user)
    {
        $tasks = [];
        $seenIds = [];
        $tz = config('app.timezone') ?: 'UTC';
        $today = Carbon::now($tz)->startOfDay();

        // Helper to add task and prevent duplicates
        $addTask = function ($task) use (&$tasks, &$seenIds) {
            if (!in_array($task['id'], $seenIds)) {
                $tasks[] = $task;
                $seenIds[] = $task['id'];
            }
        };

        // 1. STUDENT MODULES
        if ($user->primary_category === 'Student') {
            // A. Course Assignments (to submit)
            $enrolledIntakeIds = CourseEnrollment::where('user_id', $user->id)
                ->where('enrollment_status', 'active')
                ->pluck('course_intake_id');

            $assignments = CourseAssignment::with('intake.course')
                ->whereIn('course_intake_id', $enrolledIntakeIds)
                ->get();

            foreach ($assignments as $asg) {
                $hasSubmitted = CourseAssignmentSubmission::where('course_assignment_id', $asg->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$hasSubmitted) {
                    $due = Carbon::parse($asg->due_date, $tz)->startOfDay();
                    $daysDiff = $today->diffInDays($due, false);
                    $isOverdue = $daysDiff < 0;

                    $priority = 'medium';
                    if ($isOverdue) {
                        $priority = 'critical';
                    } elseif ($daysDiff <= 3) {
                        $priority = 'high';
                    }

                    $addTask([
                        'id' => "assignment-{$asg->id}",
                        'title' => "Incomplete Coursework: {$asg->title}",
                        'module' => 'Assignments',
                        'required_action' => 'Submit',
                        'due_date' => $asg->due_date->format('Y-m-d H:i'),
                        'days_diff' => $daysDiff,
                        'priority' => $priority,
                        'status' => $isOverdue ? 'Overdue' : 'Pending Submission',
                        'action_url' => route('student.assignments'),
                        'remindable_type' => CourseAssignment::class,
                        'remindable_id' => $asg->id,
                        'description' => "You have not submitted the coursework assignment for course: " . ($asg->intake->course->title ?? 'N/A'),
                    ]);
                }
            }

            // B. Unpaid course enrollment fees
            $unpaidEnrollments = CourseEnrollment::with('intake.course')
                ->where('user_id', $user->id)
                ->whereIn('payment_status', ['pending', 'failed'])
                ->whereNull('payment_proof_path')
                ->get();

            foreach ($unpaidEnrollments as $enrollment) {
                $addTask([
                    'id' => "enrollment-pay-{$enrollment->id}",
                    'title' => "Unpaid Course Fees: " . ($enrollment->intake->course->title ?? 'N/A'),
                    'module' => 'Finance',
                    'required_action' => 'Upload Payment Proof',
                    'due_date' => null,
                    'days_diff' => null,
                    'priority' => 'high',
                    'status' => 'Payment Awaiting',
                    'action_url' => route('student.courses'),
                    'remindable_type' => CourseEnrollment::class,
                    'remindable_id' => $enrollment->id,
                    'description' => "Please upload the payment proof to activate your enrollment in course: " . ($enrollment->intake->course->title ?? 'N/A'),
                ]);
            }

            // C. Unread Announcements
            $announcements = Announcement::with('course')
                ->whereIn('course_id', function ($query) use ($enrolledIntakeIds) {
                    $query->select('course_id')
                        ->from('course_intakes')
                        ->whereIn('id', $enrolledIntakeIds);
                })
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->get();

            foreach ($announcements as $ann) {
                $hasRead = AnnouncementRead::where('announcement_id', $ann->id)
                    ->where('student_id', $user->id)
                    ->exists();

                if (!$hasRead) {
                    $addTask([
                        'id' => "announcement-unread-{$ann->id}",
                        'title' => "Unread Notice: {$ann->title}",
                        'module' => 'Notices',
                        'required_action' => 'Read Announcement',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'low',
                        'status' => 'Unread',
                        'action_url' => route('student.announcements.index'),
                        'remindable_type' => Announcement::class,
                        'remindable_id' => $ann->id,
                        'description' => "New course notice published in: " . ($ann->course->title ?? 'N/A'),
                    ]);
                }
            }
        }

        // 2. CLIENT MODULES
        if ($user->primary_category === 'Client') {
            // A. Service Requests awaiting quotation or response
            $pendingRequests = ServiceRequest::where('submitted_by', $user->id)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingRequests as $req) {
                $daysDiff = null;
                if ($req->deadline) {
                    $due = Carbon::parse($req->deadline, $tz)->startOfDay();
                    $daysDiff = $today->diffInDays($due, false);
                }

                $addTask([
                    'id' => "request-pending-{$req->id}",
                    'title' => "Request Awaiting Response: {$req->title}",
                    'module' => 'Service Requests',
                    'required_action' => 'Awaiting Quotation',
                    'due_date' => $req->deadline ? $req->deadline->format('Y-m-d') : null,
                    'days_diff' => $daysDiff,
                    'priority' => 'medium',
                    'status' => 'Submitted',
                    'action_url' => route('service-requests.show', $req->id),
                    'remindable_type' => ServiceRequest::class,
                    'remindable_id' => $req->id,
                    'description' => "Your language service request Ref: {$req->reference_number} is pending administrative review.",
                ]);
            }

            // B. Quotations Awaiting Signature / Payment
            $unpaidQuotations = Quotation::with('serviceRequest')
                ->whereHas('serviceRequest', function ($q) use ($user) {
                    $q->where('submitted_by', $user->id);
                })
                ->where('status', 'approved')
                ->get();

            foreach ($unpaidQuotations as $quot) {
                // If payment proof is not yet uploaded
                $hasPayment = Payment::where('quotation_id', $quot->id)->exists();
                if (!$hasPayment) {
                    $daysDiff = null;
                    if ($quot->valid_until) {
                        $due = Carbon::parse($quot->valid_until, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "quotation-client-{$quot->id}",
                        'title' => "Quotation Action Needed: Ref #{$quot->id}",
                        'module' => 'Quotations',
                        'required_action' => 'Approve & Pay',
                        'due_date' => $quot->valid_until ? $quot->valid_until->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'high',
                        'status' => ($daysDiff !== null && $daysDiff < 0) ? 'Overdue' : 'Awaiting Payment',
                        'action_url' => route('quotations.show', $quot->id),
                        'remindable_type' => Quotation::class,
                        'remindable_id' => $quot->id,
                        'description' => "Quotation for \"{$quot->serviceRequest->title}\" is ready. Please review, approve and upload payment proof.",
                    ]);
                }
            }
        }

        // 3. STAFF / INSTRUCTORS / EXECUTIVES MODULES
        if ($user->primary_category === 'Staff') {
            $userRoles = $user->getRoleNames();

            // A. Quotation Review, Recommendation & Approvals
            if ($user->hasRole('coordinator')) {
                // Reviews (Stage 1)
                $submittedQuots = Quotation::with('serviceRequest')
                    ->where('status', 'submitted')
                    ->get();

                foreach ($submittedQuots as $quot) {
                    $daysDiff = null;
                    if ($quot->valid_until) {
                        $due = Carbon::parse($quot->valid_until, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "quotation-review-{$quot->id}",
                        'title' => "Review Quotation: Ref #{$quot->id}",
                        'module' => 'Approvals',
                        'required_action' => 'Review',
                        'due_date' => $quot->valid_until ? $quot->valid_until->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'high',
                        'status' => 'Awaiting Review',
                        'action_url' => route('quotations.show', $quot->id),
                        'remindable_type' => Quotation::class,
                        'remindable_id' => $quot->id,
                        'description' => "Quotation prepared for \"{$quot->serviceRequest->title}\" requires coordinator review.",
                    ]);
                }
            }

            if ($user->hasRole('deputy_director')) {
                // Recommendations (Stage 2)
                $submittedQuots = Quotation::with('serviceRequest')
                    ->where('status', 'reviewed')
                    ->get();

                foreach ($submittedQuots as $quot) {
                    $daysDiff = null;
                    if ($quot->valid_until) {
                        $due = Carbon::parse($quot->valid_until, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "quotation-recommend-{$quot->id}",
                        'title' => "Recommend Quotation: Ref #{$quot->id}",
                        'module' => 'Approvals',
                        'required_action' => 'Recommend',
                        'due_date' => $quot->valid_until ? $quot->valid_until->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'high',
                        'status' => 'Awaiting Recommendation',
                        'action_url' => route('quotations.show', $quot->id),
                        'remindable_type' => Quotation::class,
                        'remindable_id' => $quot->id,
                        'description' => "Quotation prepared for \"{$quot->serviceRequest->title}\" requires recommendation.",
                    ]);
                }
            }

            if ($user->hasRole('executive_director')) {
                // Final Approvals (Stage 3)
                $pendingQuots = Quotation::with('serviceRequest')
                    ->where('status', 'pending_approval')
                    ->get();

                foreach ($pendingQuots as $quot) {
                    $daysDiff = null;
                    if ($quot->valid_until) {
                        $due = Carbon::parse($quot->valid_until, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "quotation-approve-{$quot->id}",
                        'title' => "Approve Quotation: Ref #{$quot->id}",
                        'module' => 'Approvals',
                        'required_action' => 'Approve',
                        'due_date' => $quot->valid_until ? $quot->valid_until->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'high',
                        'status' => 'Awaiting Final Approval',
                        'action_url' => route('quotations.show', $quot->id),
                        'remindable_type' => Quotation::class,
                        'remindable_id' => $quot->id,
                        'description' => "Quotation prepared for \"{$quot->serviceRequest->title}\" requires final approval.",
                    ]);
                }
            }

            // B. Course Applications workflow (Verify, Recommend, Approve)
            if ($user->hasAnyRole(['admin_assistant', 'ict_administrator'])) {
                $pendingApps = CourseApplication::where('status', 'pending')->get();
                foreach ($pendingApps as $app) {
                    $addTask([
                        'id' => "course-app-verify-{$app->id}",
                        'title' => "Verify Course Application: {$app->full_name}",
                        'module' => 'Approvals',
                        'required_action' => 'Verify',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'medium',
                        'status' => 'Awaiting Verification',
                        'action_url' => route('course-applications.show', $app->id),
                        'remindable_type' => CourseApplication::class,
                        'remindable_id' => $app->id,
                        'description' => "New enrollment application from {$app->full_name} needs verification.",
                    ]);
                }
            }

            if ($user->hasRole('coordinator')) {
                $verifiedApps = CourseApplication::whereIn('status', ['verified', 'recommended'])->get();
                foreach ($verifiedApps as $app) {
                    $addTask([
                        'id' => "course-app-approve-{$app->id}",
                        'title' => "Approve Course Application: {$app->full_name}",
                        'module' => 'Approvals',
                        'required_action' => 'Approve',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'high',
                        'status' => 'Awaiting Final Approval',
                        'action_url' => route('course-applications.show', $app->id),
                        'remindable_type' => CourseApplication::class,
                        'remindable_id' => $app->id,
                        'description' => "Verified enrollment application from {$app->full_name} needs final approval.",
                    ]);
                }
            }

            // C. Client Payments Verification (Finance)
            if ($user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator'])) {
                $pendingPayments = Payment::where('status', 'pending')->get();
                foreach ($pendingPayments as $pay) {
                    $addTask([
                        'id' => "payment-verify-{$pay->id}",
                        'title' => "Verify Client Payment: Ref #{$pay->id}",
                        'module' => 'Finance',
                        'required_action' => 'Verify Payment',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'high',
                        'status' => 'Pending Verification',
                        'action_url' => route('finance.index'),
                        'remindable_type' => Payment::class,
                        'remindable_id' => $pay->id,
                        'description' => "Client uploaded proof of payment. Please verify funds received.",
                    ]);
                }
            }

            // D. Course Enrollment Payments Verification (Finance)
            if ($user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
                $pendingEnrollments = CourseEnrollment::where('payment_status', 'pending')
                    ->whereNotNull('payment_proof_path')
                    ->with('user')
                    ->get();

                foreach ($pendingEnrollments as $e) {
                    if ($e->user) {
                        $addTask([
                            'id' => "enrollment-verify-{$e->id}",
                            'title' => "Verify Enrollment Payment: {$e->user->name}",
                            'module' => 'Finance',
                            'required_action' => 'Verify Enrollment Payment',
                            'due_date' => null,
                            'days_diff' => null,
                            'priority' => 'high',
                            'status' => 'Pending Verification',
                            'action_url' => route('course-enrollments.index'),
                            'remindable_type' => CourseEnrollment::class,
                            'remindable_id' => $e->id,
                            'description' => "Student {$e->user->name} uploaded proof of course payment. Please verify.",
                        ]);
                    }
                }
            }

            // E. Quotations that have not yet been created (Quotations module)
            if ($user->hasAnyRole(['admin_assistant', 'ict_administrator', 'secretary'])) {
                // ServiceRequests pending with no quotation
                $unquotedRequests = ServiceRequest::where('status', 'pending')
                    ->whereDoesntHave('quotations')
                    ->get();

                foreach ($unquotedRequests as $req) {
                    $assignee = $req->assigned_to ? User::find($req->assigned_to) : null;
                    if ($assignee && $assignee->hasAnyRole(['admin_assistant', 'ict_administrator', 'secretary'])) {
                        if ($user->id !== $assignee->id) {
                            continue;
                        }
                    }

                    $daysDiff = null;
                    if ($req->deadline) {
                        $due = Carbon::parse($req->deadline, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "quotation-create-{$req->id}",
                        'title' => "Create Quotation for Request Ref: {$req->reference_number}",
                        'module' => 'Quotations',
                        'required_action' => 'Create Quotation',
                        'due_date' => $req->deadline ? $req->deadline->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'medium',
                        'status' => 'Awaiting Quotation',
                        'action_url' => route('quotations.create'),
                        'remindable_type' => ServiceRequest::class,
                        'remindable_id' => $req->id,
                        'description' => "Service Request \"{$req->title}\" is pending quotation preparation.",
                    ]);
                }
            }

            // F. Service requests that have not yet been responded to (Service Requests module)
            if ($user->hasAnyRole(['secretary', 'receptionist', 'admin_assistant', 'ict_administrator'])) {
                $unrespondedRequests = ServiceRequest::where('status', 'pending')->get();
                foreach ($unrespondedRequests as $req) {
                    $assignee = $req->assigned_to ? User::find($req->assigned_to) : null;
                    if ($assignee && $assignee->hasAnyRole(['secretary', 'receptionist', 'admin_assistant', 'ict_administrator'])) {
                        if ($user->id !== $assignee->id) {
                            continue;
                        }
                    }

                    $daysDiff = null;
                    if ($req->deadline) {
                        $due = Carbon::parse($req->deadline, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "request-respond-{$req->id}",
                        'title' => "Respond to Request Ref: {$req->reference_number}",
                        'module' => 'Service Requests',
                        'required_action' => 'Respond/Process',
                        'due_date' => $req->deadline ? $req->deadline->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'medium',
                        'status' => 'Unresponded',
                        'action_url' => route('service-requests.show', $req->id),
                        'remindable_type' => ServiceRequest::class,
                        'remindable_id' => $req->id,
                        'description' => "New language service request: \"{$req->title}\" has not yet been processed.",
                    ]);
                }
            }

            // G. Instructor Portal - Submissions that have not yet been marked (Assignments module)
            $instructorIntakes = CourseIntake::where('instructor_id', $user->id)->pluck('id');
            if ($instructorIntakes->isNotEmpty()) {
                $unmarkedSubmissions = CourseAssignmentSubmission::whereNull('graded_at')
                    ->whereHas('assignment', function ($q) use ($instructorIntakes) {
                        $q->whereIn('course_intake_id', $instructorIntakes);
                    })
                    ->with(['student', 'assignment'])
                    ->get();

                foreach ($unmarkedSubmissions as $sub) {
                    $daysDiff = null;
                    if ($sub->assignment && $sub->assignment->due_date) {
                        $due = Carbon::parse($sub->assignment->due_date, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "submission-grade-{$sub->id}",
                        'title' => "Grade Coursework: {$sub->student->name} - {$sub->assignment->title}",
                        'module' => 'Assignments',
                        'required_action' => 'Mark/Grade',
                        'due_date' => ($sub->assignment && $sub->assignment->due_date) ? $sub->assignment->due_date->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => 'high',
                        'status' => 'Pending Grading',
                        'action_url' => route('instructor.assignments.submissions', $sub->course_assignment_id),
                        'remindable_type' => CourseAssignmentSubmission::class,
                        'remindable_id' => $sub->id,
                        'description' => "Student coursework submission requires grading: " . ($sub->assignment->title ?? 'N/A'),
                    ]);
                }
            }

            // H. Language Service Assignments (assigned work to Language Experts/Part Time Staff)
            if ($user->hasAnyRole(['language_expert', 'part_time_staff', 'secretary'])) {
                $assignments = Assignment::where('assigned_to', $user->id)
                    ->whereIn('status', ['assigned', 'accepted', 'in_progress'])
                    ->with('serviceRequest')
                    ->get();

                foreach ($assignments as $asg) {
                    $daysDiff = null;
                    if ($asg->serviceRequest && $asg->serviceRequest->deadline) {
                        $due = Carbon::parse($asg->serviceRequest->deadline, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $addTask([
                        'id' => "assignment-{$asg->id}",
                        'title' => ($asg->status === 'assigned' ? "Review Assignment Request" : "In Progress Assignment") . ": " . ($asg->serviceRequest->title ?? 'N/A'),
                        'module' => 'Assignments',
                        'required_action' => $asg->status === 'assigned' ? 'Accept/Reject Assignment' : 'Complete Assignment',
                        'due_date' => ($asg->serviceRequest && $asg->serviceRequest->deadline) ? $asg->serviceRequest->deadline->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => $asg->status === 'assigned' ? 'high' : 'medium',
                        'status' => ucfirst($asg->status),
                        'action_url' => route('assignments.show', $asg->id),
                        'remindable_type' => Assignment::class,
                        'remindable_id' => $asg->id,
                        'description' => "Specialist work allocated: Role in task \"{$asg->role_in_task}\" for service Ref " . ($asg->serviceRequest->reference_number ?? 'N/A'),
                    ]);
                }
            }

            // I. Tasks under Assignments (Task model)
            $assignmentIds = Assignment::where('assigned_to', $user->id)->pluck('id');
            if ($assignmentIds->isNotEmpty()) {
                $pendingTasks = Task::whereIn('assignment_id', $assignmentIds)
                    ->where('status', '!=', 'completed')
                    ->get();

                foreach ($pendingTasks as $t) {
                    $daysDiff = null;
                    if ($t->due_date) {
                        $due = Carbon::parse($t->due_date, $tz)->startOfDay();
                        $daysDiff = $today->diffInDays($due, false);
                    }

                    $isOverdue = $daysDiff !== null && $daysDiff < 0;
                    $priority = 'medium';
                    if ($isOverdue) {
                        $priority = 'critical';
                    } elseif ($daysDiff !== null && $daysDiff <= 1) {
                        $priority = 'high';
                    }

                    $addTask([
                        'id' => "task-staff-{$t->id}",
                        'title' => "Incomplete Task: {$t->title}",
                        'module' => 'Assignments',
                        'required_action' => 'Complete Task',
                        'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                        'days_diff' => $daysDiff,
                        'priority' => $priority,
                        'status' => $isOverdue ? 'Overdue' : ucfirst($t->status),
                        'action_url' => route('completed-tasks.index'),
                        'remindable_type' => Task::class,
                        'remindable_id' => $t->id,
                        'description' => "Sub-task for assigned language service program: {$t->description}",
                    ]);
                }
            }

            // J. Deliverables Workflow (review, director_approval, admin_submission)
            // 1. Review (Stage 1) -> Coordinators
            if ($user->hasRole('coordinator')) {
                $reviewRequests = ServiceRequest::where('status', 'review')->get();

                foreach ($reviewRequests as $req) {
                    $assignee = $req->assigned_to ? User::find($req->assigned_to) : null;
                    if ($assignee && $assignee->hasRole('coordinator')) {
                        if ($user->id !== $assignee->id) {
                            continue;
                        }
                    }

                    $addTask([
                        'id' => "deliverable-review-{$req->id}",
                        'title' => "Review Deliverable for Request Ref: {$req->reference_number}",
                        'module' => 'Deliverables',
                        'required_action' => 'Review Deliverable',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'high',
                        'status' => 'Pending Review',
                        'action_url' => route('service-requests.show', $req->id),
                        'remindable_type' => ServiceRequest::class,
                        'remindable_id' => $req->id,
                        'description' => "A specialist has uploaded a deliverable for \"{$req->title}\" which needs your review.",
                    ]);
                }
            }

            // 2. Director Approval (Stage 2) -> Directors
            if ($user->hasAnyRole(['executive_director', 'deputy_director'])) {
                $approvalRequests = ServiceRequest::where('status', 'director_approval')->get();

                foreach ($approvalRequests as $req) {
                    $addTask([
                        'id' => "deliverable-approve-{$req->id}",
                        'title' => "Approve Deliverable for Request Ref: {$req->reference_number}",
                        'module' => 'Deliverables',
                        'required_action' => 'Approve Deliverable',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'high',
                        'status' => 'Pending Approval',
                        'action_url' => route('service-requests.show', $req->id),
                        'remindable_type' => ServiceRequest::class,
                        'remindable_id' => $req->id,
                        'description' => "A deliverable for \"{$req->title}\" has been forwarded for final director approval.",
                    ]);
                }
            }

            // 3. Admin Submission (Stage 3) -> Admin Assistants
            if ($user->hasRole('admin_assistant')) {
                $submissionRequests = ServiceRequest::where('status', 'admin_submission')->get();

                foreach ($submissionRequests as $req) {
                    $assignee = $req->assigned_to ? User::find($req->assigned_to) : null;
                    if ($assignee && $assignee->hasRole('admin_assistant')) {
                        if ($user->id !== $assignee->id) {
                            continue;
                        }
                    }

                    $addTask([
                        'id' => "deliverable-submit-{$req->id}",
                        'title' => "Submit Deliverable to Client: Ref {$req->reference_number}",
                        'module' => 'Deliverables',
                        'required_action' => 'Submit to Client',
                        'due_date' => null,
                        'days_diff' => null,
                        'priority' => 'high',
                        'status' => 'Pending Submission',
                        'action_url' => route('service-requests.show', $req->id),
                        'remindable_type' => ServiceRequest::class,
                        'remindable_id' => $req->id,
                        'description' => "The approved deliverable for \"{$req->title}\" is ready to be sent to the client.",
                    ]);
                }
            }

            // K. CC Reviews -> Specific CC Reviewers
            if (\Illuminate\Support\Facades\Schema::hasTable('cc_reviews')) {
                $ccReviews = DB::table('cc_reviews')
                    ->where('reviewer_id', $user->id)
                    ->where('status', 'pending')
                    ->get();

                foreach ($ccReviews as $review) {
                    $req = ServiceRequest::find($review->service_request_id);
                    if ($req) {
                        $addTask([
                            'id' => "cc-review-{$review->id}",
                            'title' => "CC Review: " . $req->title,
                            'module' => 'CC Reviews',
                            'required_action' => 'Respond to CC Review',
                            'due_date' => null,
                            'days_diff' => null,
                            'priority' => 'medium',
                            'status' => 'Pending Review',
                            'action_url' => route('service-requests.show', $req->id),
                            'remindable_type' => ServiceRequest::class,
                            'remindable_id' => $req->id,
                            'description' => "Coordinator review has been requested for deliverable on Ref: {$req->reference_number}.",
                        ]);
                    }
                }
            }
        }

        return $tasks;
    }

    /**
     * Send reminders to all active users.
     */
    public function sendReminders()
    {
        $users = User::where('is_active', true)->get();
        $sentCount = 0;

        // Configuration settings
        $intervals = SystemSetting::get('task_reminder_intervals', [7, 3, 1, 0]);

        foreach ($users as $user) {
            $outstanding = $this->getOutstandingTasks($user);

            if (empty($outstanding)) {
                continue;
            }

            foreach ($outstanding as $task) {
                // Determine trigger_type based on due date
                $triggerType = 'pending_action';
                
                $isDeadlineCheckingNeeded = in_array($task['remindable_type'], [Task::class, CourseAssignment::class]);

                if (isset($task['due_date']) && !empty($task['due_date'])) {
                    $daysDiff = $task['days_diff'];
                    if ($daysDiff !== null) {
                        if ($daysDiff < 0) {
                            $triggerType = 'overdue';
                        } elseif ($isDeadlineCheckingNeeded) {
                            if (in_array($daysDiff, $intervals)) {
                                $triggerType = "approaching_{$daysDiff}";
                            } else {
                                // Days remaining is not in configured alert thresholds; skip dispatch
                                continue;
                            }
                        } else {
                            // For other workflow actions with a due date, default to pending_action daily reminder
                            $triggerType = 'pending_action';
                        }
                    }
                }

                // Prevent duplicate notifications based on trigger logic
                $query = DB::table('reminder_logs')
                    ->where('user_id', $user->id)
                    ->where('remindable_type', $task['remindable_type'])
                    ->where('remindable_id', $task['remindable_id'])
                    ->where('trigger_type', $triggerType);

                if ($triggerType === 'overdue' || $triggerType === 'pending_action') {
                    // Send once per calendar day (limit daily spamming)
                    $alreadySent = $query->where('sent_at', '>=', now()->startOfDay())->exists();
                } else {
                    // Send once per milestone interval (e.g. at 3 days)
                    $alreadySent = $query->exists();
                }

                if ($alreadySent) {
                    continue;
                }

                // Send dynamic notification
                SystemNotification::sendUnique(
                    $user,
                    $task['module'],
                    $task['title'],
                    $task['description'],
                    $task['action_url'],
                    [
                        'task_id' => $task['id'],
                        'priority' => $task['priority'],
                        'due_date' => $task['due_date'],
                    ]
                );

                // Fetch database notification record ID to link audit trail
                $dbNotification = $user->notifications()->orderBy('created_at', 'desc')->first();

                // Store audit trail entry
                DB::table('reminder_logs')->insert([
                    'user_id' => $user->id,
                    'remindable_type' => $task['remindable_type'],
                    'remindable_id' => $task['remindable_id'],
                    'module' => $task['module'],
                    'trigger_type' => $triggerType,
                    'priority' => $task['priority'],
                    'channels' => json_encode(['in_app', 'email']),
                    'sent_at' => now(),
                    'notification_id' => $dbNotification ? $dbNotification->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Mark pending reminders as completed.
     */
    public static function markAsCompleted($remindableType, $remindableId, $userId = null)
    {
        $query = DB::table('reminder_logs')
            ->where('remindable_type', $remindableType)
            ->where('remindable_id', $remindableId)
            ->whereNull('task_completed_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $query->update([
            'task_completed_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Log click acknowledgement on a notification.
     */
    public static function markAsAcknowledged($notificationId)
    {
        DB::table('reminder_logs')
            ->where('notification_id', $notificationId)
            ->whereNull('acknowledged_at')
            ->update([
                'acknowledged_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Log clearance/dismissal on a notification.
     */
    public static function markAsDismissed($notificationId)
    {
        DB::table('reminder_logs')
            ->where('notification_id', $notificationId)
            ->whereNull('dismissed_at')
            ->update([
                'dismissed_at' => now(),
                'updated_at' => now()
            ]);
    }
}
