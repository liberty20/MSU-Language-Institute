<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Quotation;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->hasRole('student')) {
            return redirect()->route('student.courses');
        }
        
        $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary']);
        $isClient = $user->hasRole('client');

        $stats = [];
        
        if ($isClient) {
            // Client dashboard:
            // "leave his or her active request, pending task and in progress."
            // Active requests: client's service requests that are not completed or cancelled
            $stats['active_requests'] = ServiceRequest::where('submitted_by', $user->id)
                ->whereNotIn('status', ['completed'])->count();
            
            // Pending requests: client's service requests that are pending
            $stats['pending_tasks'] = ServiceRequest::where('submitted_by', $user->id)
                ->where('status', 'pending')->count();
            
            // In progress requests: client's service requests that are in progress
            $stats['in_progress_requests'] = ServiceRequest::where('submitted_by', $user->id)
                ->where('status', 'in_progress')->count();

            // "the approved value should match with total amount of her or his verified proof of payment please do separate the currency as it is."
            $stats['total_revenue'] = Payment::where('payments.client_id', $user->id)
                ->where('payments.status', 'verified')
                ->join('quotations', 'payments.quotation_id', '=', 'quotations.id')
                ->selectRaw('quotations.currency, SUM(payments.amount_paid) as total')
                ->groupBy('quotations.currency')
                ->pluck('total', 'currency')
                ->toArray();
                
            $stats['assigned_tasks'] = 0;
            $stats['total_clients'] = 0; // redundant
        } else {
            // Staff / Admin dashboard:
            $stats['total_clients'] = Client::count();
            
            if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff') || $user->instructedIntakes()->exists()) {
                // Staff scoped stats
                $activeReqsQuery = ServiceRequest::whereNotIn('status', ['completed']);
                $pendingTasksQuery = Task::whereIn('status', ['todo', 'in_progress']);
                
                $expert = \App\Models\User::with('department')->find($user->id);
                if ($expert && $expert->department_id) {
                    $activeReqsQuery->where('department_id', $expert->department_id);
                    $pendingTasksQuery->whereHas('assignment.serviceRequest', fn($q) => $q->where('department_id', $expert->department_id));
                    
                    $stats['department_name'] = $expert->department ? $expert->department->name : 'N/A';
                    $stats['department_code'] = $expert->department ? $expert->department->code : 'N/A';
                }
                
                $stats['active_requests'] = $activeReqsQuery->whereHas('assignments', fn($q) => $q->where('assigned_to', $user->id))->count();
                $stats['pending_tasks']   = $pendingTasksQuery->whereHas('assignment', fn($q) => $q->where('assigned_to', $user->id))->count();
                $stats['total_revenue']   = 0;
                $stats['assigned_tasks']  = \App\Models\Assignment::whereNotIn('status', ['completed'])->count();

                // Instructor Assigned Courses stats
                $assignedIntakes = \App\Models\CourseIntake::where('instructor_id', $user->id)
                    ->with(['course', 'enrollments'])
                    ->get();
                
                $myCourses = [];
                $totalStudents = 0;
                
                foreach ($assignedIntakes as $intake) {
                    $studentCount = $intake->enrollments->where('payment_status', 'verified')->count();
                    $totalStudents += $studentCount;
                    
                    $myCourses[] = [
                        'id' => $intake->id,
                        'course_name' => $intake->course->title,
                        'course_code' => $intake->course->code,
                        'enrolled_students' => $studentCount,
                        'status' => ucwords($intake->status),
                    ];
                }
                
                $stats['assigned_courses_list'] = $myCourses;
                $stats['total_courses_assigned'] = $assignedIntakes->count();
                $stats['total_students_assigned'] = $totalStudents;
            } else {
                // Admin stats
                $stats['active_requests'] = ServiceRequest::whereNotIn('status', ['completed'])->count();
                $stats['pending_tasks']   = Task::whereIn('status', ['todo', 'in_progress'])->count();
                $stats['total_revenue']   = Quotation::where('status', 'approved')->sum('amount');
                $stats['assigned_tasks']  = \App\Models\Assignment::whereNotIn('status', ['completed'])->count();
            }
        }

        // Recent Requests Table (Only shown for non-clients)
        $recentRequests = collect();
        if (!$isClient) {
            $recentRequestsQuery = ServiceRequest::with('client')->orderBy('created_at', 'desc');
            
            if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
                $recentRequestsQuery->whereHas('assignments', fn($q) => $q->where('assigned_to', $user->id));
            }
            
            $expert = \App\Models\User::find($user->id);
            if ($expert && $expert->department_id) {
                $recentRequestsQuery->where('department_id', $expert->department_id);
            }
            
            $recentRequests = $recentRequestsQuery->take(8)->get()
                ->map(fn($r) => [
                    'id'        => $r->id,
                    'reference' => $r->reference_number,
                    'client'    => $r->client ? ($r->client->organization ?? $r->client->contact_person) : 'N/A',
                    'service'   => ucwords(str_replace('_', ' ', $r->service_category)),
                    'priority'  => $r->priority,
                    'status'    => $r->status,
                    'date'      => $r->created_at->format('M d, Y'),
                ]);
        }

        $recentActivities = collect();
        if ($user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director', 'admin_assistant'])) {
            $recentActivities = \App\Models\ActivityLog::orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => ucwords(str_replace('_', ' ', $log->action)),
                        'description' => $log->description,
                        'created_at' => $log->created_at->diffForHumans(),
                    ];
                });
        }

        return Inertia::render('Dashboard', [
            'stats'               => $stats,
            'recentRequests'      => $recentRequests,
            'recentActivities'    => $recentActivities,
        ]);
    }

    public function auditTrail()
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director', 'admin_assistant'])) {
            abort(403, 'Unauthorized access to Audit Trail.');
        }

        $query = \App\Models\ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Apply advanced administrative audit filters
        // 1. General search query
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. User Filter (performing the action)
        if (request('user')) {
            $searchUser = request('user');
            $query->whereHas('user', function($qu) use ($searchUser) {
                $qu->where('name', 'like', "%{$searchUser}%")
                  ->orWhere('email', 'like', "%{$searchUser}%");
            });
        }

        // 3. Action Type Filter
        if (request('action_type')) {
            $query->where('action', 'like', "%" . request('action_type') . "%");
        }

        // 4. Affected User / Record Search
        if (request('affected_user')) {
            $affected = request('affected_user');
            $query->where(function($q) use ($affected) {
                $q->where('description', 'like', "%{$affected}%")
                  ->orWhere('properties', 'like', "%{$affected}%");
            });
        }

        // 5. Module Filter
        if (request('module')) {
            $module = request('module');
            if ($module === 'User Management') {
                $query->where(function($q) {
                    $q->where('subject_type', 'like', '%User%')
                      ->orWhere('subject_type', 'like', '%Role%')
                      ->orWhere('subject_type', 'like', '%Permission%')
                      ->orWhere('action', 'failed_role_assignment')
                      ->orWhere('action', 'like', '%user%')
                      ->orWhere('action', 'like', '%role%')
                      ->orWhere('action', 'like', '%permission%');
                });
            } elseif ($module === 'Course & Enrollment') {
                $query->where(function($q) {
                    $q->where(function($sq) {
                        $sq->where('subject_type', 'like', '%Course%')
                           ->where('subject_type', 'not like', '%CourseApplication%');
                    })
                    ->orWhere('subject_type', 'like', '%Intake%')
                    ->orWhere('subject_type', 'like', '%Enrollment%')
                    ->orWhere('subject_type', 'like', '%Timetable%')
                    ->orWhere('subject_type', 'like', '%Assignment%')
                    ->orWhere('subject_type', 'like', '%CaMark%')
                    ->orWhere('action', 'like', '%timetable%')
                    ->orWhere('action', 'like', '%assignment%')
                    ->orWhere('action', 'like', '%ca_mark%')
                    ->orWhere('action', 'like', '%enrollment%');
                });
            } elseif ($module === 'Approval Workflows') {
                $query->where(function($q) {
                    $q->where('subject_type', 'like', '%CourseApplication%')
                      ->orWhere('action', 'like', '%application%');
                });
            } elseif ($module === 'Quotation Management') {
                $query->where(function($q) {
                    $q->where('subject_type', 'like', '%Quotation%')
                      ->orWhere('subject_type', 'like', '%Payment%')
                      ->orWhere('action', 'like', '%quotation%')
                      ->orWhere('action', 'like', '%payment%');
                });
            } elseif ($module === 'System Administration') {
                $query->where(function($q) {
                    $q->where('subject_type', 'like', '%Setting%')
                      ->orWhere('action', 'like', '%setting%');
                });
            }
        }

        // 6. Date Range Filters
        if (request('date_start')) {
            $query->whereDate('created_at', '>=', request('date_start'));
        }
        if (request('date_end')) {
            $query->whereDate('created_at', '<=', request('date_end'));
        }

        $logs = $query->paginate(25)->withQueryString()->through(function ($log) {
            // Determine Module based on triggers
            $module = 'System Administration';
            $sType = $log->subject_type;
            $action = strtolower($log->action);
            
            if (
                strpos($sType, 'User') !== false || 
                strpos($sType, 'Role') !== false || 
                strpos($sType, 'Permission') !== false ||
                $action === 'failed_role_assignment' ||
                strpos($action, 'user') !== false ||
                strpos($action, 'role') !== false ||
                strpos($action, 'permission') !== false
            ) {
                $module = 'User Management';
            } elseif (
                strpos($sType, 'Course') !== false || 
                strpos($sType, 'Intake') !== false || 
                strpos($sType, 'Enrollment') !== false || 
                strpos($sType, 'Timetable') !== false || 
                strpos($sType, 'Assignment') !== false || 
                strpos($sType, 'CaMark') !== false ||
                strpos($action, 'timetable') !== false ||
                strpos($action, 'assignment') !== false ||
                strpos($action, 'ca_mark') !== false ||
                strpos($action, 'enrollment') !== false ||
                strpos($action, 'course') !== false
            ) {
                if (strpos($sType, 'CourseApplication') !== false || strpos($action, 'application') !== false) {
                    $module = 'Approval Workflows';
                } else {
                    $module = 'Course & Enrollment';
                }
            } elseif (
                strpos($sType, 'Quotation') !== false || 
                strpos($sType, 'Payment') !== false ||
                strpos($action, 'quotation') !== false ||
                strpos($action, 'payment') !== false
            ) {
                $module = 'Quotation Management';
            } elseif (
                strpos($sType, 'SystemSetting') !== false ||
                strpos($action, 'setting') !== false
            ) {
                $module = 'System Administration';
            }
            
            // Format User Performing Action
            $userPerforming = 'System Auto';
            if ($log->user) {
                $role = $log->user->roles->first() ? $log->user->roles->first()->name : 'Staff';
                $userPerforming = $log->user->name . ' (' . ucwords(str_replace('_', ' ', $role)) . ')';
            }
            
            // Format Affected Record / Affected User
            $affected = 'N/A';
            if ($log->subject_type) {
                $baseType = class_basename($log->subject_type);
                $affected = $baseType . ' #' . ($log->subject_id ?? 'N/A');
            }
            
            if (preg_match('/(user|student|instructor|application for|quotation) ([A-Za-z0-9\s\.\-\@\_]+)/i', $log->description, $matches)) {
                $candidate = trim($matches[2]);
                if (strlen($candidate) > 2 && strtolower($candidate) !== 'unauthorized' && strpos($candidate, 'attempt') === false) {
                    $affected = $candidate;
                }
            }
            
            // Extract previous and new values
            $prevVal = 'N/A';
            $newVal = 'N/A';
            
            $props = [];
            if ($log->properties) {
                $props = is_array($log->properties) ? $log->properties : json_decode($log->properties, true);
            }
            
            if (isset($props['previous_role']) || isset($props['new_role'])) {
                $prevVal = $props['previous_role'] ?? 'None';
                $newVal = $props['new_role'] ?? 'None';
            } elseif (isset($props['previous_values']) || isset($props['new_values'])) {
                $prev = $props['previous_values'] ?? [];
                $new = $props['new_values'] ?? [];
                
                $prevVal = count($prev) > 0 ? (is_array($prev) ? json_encode($prev) : $prev) : 'N/A';
                $newVal = count($new) > 0 ? (is_array($new) ? json_encode($new) : $new) : 'N/A';
            } elseif (isset($props['old_value']) || isset($props['new_value'])) {
                $prevVal = $props['old_value'] ?? 'N/A';
                $newVal = $props['new_value'] ?? 'N/A';
            }
            
            return [
                'id' => $log->id,
                'created_at' => $log->created_at->format('d-M-Y H:i'),
                'user_performing' => $userPerforming,
                'action' => ucwords(str_replace('_', ' ', $log->action)),
                'affected' => $affected,
                'module' => $module,
                'previous_value' => $prevVal,
                'new_value' => $newVal,
                'ip_address' => $log->ip_address ?? 'N/A',
                'description' => $log->description,
            ];
        });

        return Inertia::render('Admin/AuditTrail', [
            'logs' => $logs,
            'filters' => request()->only(['search', 'user', 'action_type', 'module', 'affected_user', 'date_start', 'date_end']),
        ]);
    }

    public function getNotifications()
    {
        $user = Auth::user();
        if ($user) {
            try {
                $this->checkDeadlines($user);
            } catch (\Exception $e) {
                \Log::error("Error checking deadlines: " . $e->getMessage());
            }

            // Clean up invalid/orphaned notifications
            $allNotifications = $user->notifications()->get();
            foreach ($allNotifications as $notification) {
                if (!$this->isNotificationValid($notification, $user)) {
                    $notification->delete();
                }
            }
        }

        $notifications = $user ? $user->notifications()->orderBy('created_at', 'desc')->take(20)->get() : collect();
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }

    private function isNotificationValid($notification, $user)
    {
        $data = $notification->data;
        if (!is_array($data)) {
            return true;
        }

        // 1. Check direct ID references
        $directIds = [
            'task_id' => \App\Models\Task::class,
            'quotation_id' => \App\Models\Quotation::class,
            'course_assignment_id' => \App\Models\CourseAssignment::class,
            'course_intake_id' => \App\Models\CourseIntake::class,
            'course_id' => \App\Models\Course::class,
            'client_id' => \App\Models\Client::class,
            'service_request_id' => \App\Models\ServiceRequest::class,
            'user_id' => \App\Models\User::class,
            'payment_id' => \App\Models\Payment::class,
            'document_id' => \App\Models\UploadedDocument::class,
            'report_id' => \App\Models\Report::class,
            'course_application_id' => \App\Models\CourseApplication::class,
        ];

        foreach ($directIds as $key => $modelClass) {
            if (isset($data[$key]) && !empty($data[$key])) {
                if (!$modelClass::where('id', $data[$key])->exists()) {
                    return false;
                }
            }
        }

        // 2. Check action URL references
        $actionUrl = $data['action_url'] ?? null;
        if ($actionUrl && $actionUrl !== '#') {
            $path = parse_url($actionUrl, PHP_URL_PATH);
            $trimmedPath = trim($path, '/');
            
            // Check segment based IDs (e.g. quotations/9999)
            $segments = explode('/', $trimmedPath);
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $key = $segments[$i];
                $val = $segments[$i + 1];
                if (is_numeric($val)) {
                    $modelClass = null;
                    switch ($key) {
                        case 'quotations':
                            $modelClass = \App\Models\Quotation::class;
                            break;
                        case 'tasks':
                            $modelClass = \App\Models\Task::class;
                            break;
                        case 'assignments':
                            $modelClass = \App\Models\Assignment::class;
                            break;
                        case 'courses':
                            $modelClass = \App\Models\Course::class;
                            break;
                        case 'course-applications':
                            $modelClass = \App\Models\CourseApplication::class;
                            break;
                        case 'course-enrollments':
                            $modelClass = \App\Models\CourseEnrollment::class;
                            break;
                        case 'course-intakes':
                            $modelClass = \App\Models\CourseIntake::class;
                            break;
                        case 'service-requests':
                            $modelClass = \App\Models\ServiceRequest::class;
                            break;
                        case 'users':
                            $modelClass = \App\Models\User::class;
                            break;
                        case 'departments':
                            $modelClass = \App\Models\Department::class;
                            break;
                        case 'payments':
                            $modelClass = \App\Models\Payment::class;
                            break;
                        case 'documents':
                            $modelClass = \App\Models\UploadedDocument::class;
                            break;
                        case 'reports':
                            $modelClass = \App\Models\Report::class;
                            break;
                    }
                    if ($modelClass && !$modelClass::where('id', $val)->exists()) {
                        return false;
                    }
                }
            }

            $userRoles = $user->getRoleNames();
            $adminRoles = ['ict_administrator', 'admin_assistant', 'deputy_director', 'executive_director'];

            // Check authorization for the URL route
            try {
                $request = \Illuminate\Http\Request::create($actionUrl, 'GET');
                $route = \Illuminate\Support\Facades\Route::getRoutes()->match($request);
                if ($route) {
                    $middleware = $route->gatherMiddleware();
                    foreach ($middleware as $m) {
                        if (substr($m, 0, 5) === 'role:') {
                            $roles = explode(',', substr($m, 5));
                            if ($userRoles->intersect($roles)->isEmpty()) {
                                return false;
                            }
                        }
                        if (substr($m, 0, 11) === 'permission:') {
                            $permissions = explode(',', substr($m, 11));
                            $userPermissions = $user->getAllPermissions()->pluck('name');
                            if ($userPermissions->intersect($permissions)->isEmpty()) {
                                return false;
                            }
                        }
                        if ($m === 'instructor') {
                            $isInst = $userRoles->intersect(['language_expert', 'part_time_staff'])->isNotEmpty() || $user->instructedIntakes()->exists();
                            if (!$isInst) {
                                return false;
                            }
                        }
                    }
                }
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                // If route does not exist but it's an internal admin URL, check roles anyway
                if (strpos($trimmedPath, 'admin') !== false) {
                    if ($userRoles->intersect($adminRoles)->isEmpty()) {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                return false;
            }

            // Fallback prefix role check
            if (substr($trimmedPath, 0, 5) === 'admin' || strpos($trimmedPath, '/admin/') !== false) {
                if ($userRoles->intersect($adminRoles)->isEmpty()) {
                    return false;
                }
            }
        }

        return true;
    }

    private function checkDeadlines($user)
    {
        if (!$user) return;

        $now = now();
        $fortyEightHoursLater = now()->addDays(2);

        // 1. Task Management Deadlines (Tasks under Assignment)
        $assignmentIds = \App\Models\Assignment::where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed'])
            ->pluck('id');

        if ($assignmentIds->isNotEmpty()) {
            // Overdue Tasks
            $overdueTasks = \App\Models\Task::whereIn('assignment_id', $assignmentIds)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now)
                ->get();

            foreach ($overdueTasks as $task) {
                // Prevent duplicates
                $exists = $user->notifications()
                    ->where('data->task_id', $task->id)
                    ->where('data->alert_type', 'overdue')
                    ->exists();

                if (!$exists) {
                    $user->notify(new \App\Notifications\SystemNotification(
                        'Tasks',
                        'Task Overdue',
                        'Your assigned task "' . $task->title . '" is overdue!',
                        route('completed-tasks.index'),
                        ['task_id' => $task->id, 'alert_type' => 'overdue']
                    ));
                }
            }

            // Approaching Tasks (due in next 48 hours)
            $approachingTasks = \App\Models\Task::whereIn('assignment_id', $assignmentIds)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$now, $fortyEightHoursLater])
                ->get();

            foreach ($approachingTasks as $task) {
                $exists = $user->notifications()
                    ->where('data->task_id', $task->id)
                    ->where('data->alert_type', 'approaching')
                    ->exists();

                if (!$exists) {
                    $user->notify(new \App\Notifications\SystemNotification(
                        'Tasks',
                        'Task Due Soon',
                        'Your assigned task "' . $task->title . '" is due on ' . \Carbon\Carbon::parse($task->due_date)->format('M d, Y') . '.',
                        route('completed-tasks.index'),
                        ['task_id' => $task->id, 'alert_type' => 'approaching']
                    ));
                }
            }
        }

        // 2. Assessments / Course Assignment Deadlines (Students & Instructors)
        if ($user->hasRole('student')) {
            $intakeIds = \App\Models\CourseEnrollment::where('user_id', $user->id)
                ->where('payment_status', 'verified')
                ->pluck('course_intake_id');

            if ($intakeIds->isNotEmpty()) {
                $assignments = \App\Models\CourseAssignment::whereIn('course_intake_id', $intakeIds)
                    ->whereNotNull('due_date')
                    ->get();

                foreach ($assignments as $asg) {
                    $submitted = \App\Models\CourseAssignmentSubmission::where('course_assignment_id', $asg->id)
                        ->where('user_id', $user->id)
                        ->exists();

                    if (!$submitted) {
                        $due = \Carbon\Carbon::parse($asg->due_date);
                        if ($due->isPast()) {
                            $exists = $user->notifications()
                                ->where('data->course_assignment_id', $asg->id)
                                ->where('data->alert_type', 'overdue')
                                ->exists();

                            if (!$exists) {
                                $user->notify(new \App\Notifications\SystemNotification(
                                    'Assessments',
                                    'Assignment Overdue',
                                    'Your coursework assignment "' . $asg->title . '" is overdue!',
                                    route('student.assignments'),
                                    ['course_assignment_id' => $asg->id, 'alert_type' => 'overdue']
                                ));
                            }
                        } elseif ($due->between($now, $fortyEightHoursLater)) {
                            $exists = $user->notifications()
                                ->where('data->course_assignment_id', $asg->id)
                                ->where('data->alert_type', 'approaching')
                                ->exists();

                            if (!$exists) {
                                $user->notify(new \App\Notifications\SystemNotification(
                                    'Assessments',
                                    'Assignment Due Soon',
                                    'Your coursework assignment "' . $asg->title . '" is due on ' . $due->format('M d, H:i') . '.',
                                    route('student.assignments'),
                                    ['course_assignment_id' => $asg->id, 'alert_type' => 'approaching']
                                ));
                            }
                        }
                    }
                }
            }
        } elseif ($user->hasRole('language_expert') || $user->hasRole('part_time_staff') || $user->instructedIntakes()->exists()) {
            $intakeIds = \App\Models\CourseIntake::where('instructor_id', $user->id)->pluck('id');
            if ($intakeIds->isNotEmpty()) {
                $assignments = \App\Models\CourseAssignment::whereIn('course_intake_id', $intakeIds)
                    ->whereNotNull('due_date')
                    ->get();

                foreach ($assignments as $asg) {
                    $due = \Carbon\Carbon::parse($asg->due_date);
                    if ($due->between($now, $fortyEightHoursLater)) {
                        $exists = $user->notifications()
                            ->where('data->course_assignment_id', $asg->id)
                            ->where('data->alert_type', 'instructor_approaching')
                            ->exists();

                        if (!$exists) {
                            $user->notify(new \App\Notifications\SystemNotification(
                                'Assessments',
                                'Assignment Deadline Approaching',
                                'The deadline for assignment "' . $asg->title . '" is on ' . $due->format('M d, H:i') . '.',
                                route('instructor.assignments.index'),
                                ['course_assignment_id' => $asg->id, 'alert_type' => 'instructor_approaching']
                            ));
                        }
                    }
                }
            }
        }
    }

    public function markNotificationAsRead(Request $request, $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['status' => 'success']);
    }

    public function markAllNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['status' => 'success']);
    }
}
