<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\Quotation;
use App\Models\Client;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Approval;
use App\Models\Department;
use App\Models\User;
use App\Models\KpiRecord;
use App\Models\Payment;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $destDir = public_path('images/reports');
        if (!file_exists($destDir)) {
            @mkdir($destDir, 0755, true);
        }
        $srcFile = 'l:/Projects/MSULI/Documents/msuli build.jpg';
        $destFile = $destDir . '/msuli-build.jpg';
        if (file_exists($srcFile) && !file_exists($destFile)) {
            @copy($srcFile, $destFile);
        }
    }

    /**
     * Display the reports and performance analytics dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isManagement = $user->hasAnyRole(['executive_director', 'deputy_director', 'admin_assistant', 'secretary', 'ict_administrator']);

        // Base Queries for Filtering
        $query = ServiceRequest::query();
        $taskQuery = Task::query();

        // 1. Enforce Role-Based Access Controls
        if (!$isManagement) {
            // Staff members can only see their own assigned requests and tasks
            $query->where('assigned_to', $user->id);
            $taskQuery->whereHas('assignment', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        } else {
            // Management can filter by department and staff members
            if ($request->filled('department_id')) {
                $deptId = $request->department_id;
                $query->where('department_id', $deptId);
                $taskQuery->whereHas('assignment.serviceRequest', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }

            if ($request->filled('assigned_to')) {
                $staffId = $request->assigned_to;
                $query->where('assigned_to', $staffId);
                $taskQuery->whereHas('assignment', function ($q) use ($staffId) {
                    $q->where('assigned_to', $staffId);
                });
            }
        }

        // 2. Apply Date Range Filters
        if ($request->filled('date_start')) {
            $query->where('created_at', '>=', $request->date_start . ' 00:00:00');
            $taskQuery->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }
        if ($request->filled('date_end')) {
            $query->where('created_at', '<=', $request->date_end . ' 23:59:59');
            $taskQuery->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        // 3. Apply Service Category Filter
        if ($request->filled('service_category')) {
            $category = $request->service_category;
            $query->where('service_category', $category);
            $taskQuery->whereHas('assignment.serviceRequest', function ($q) use ($category) {
                $q->where('service_category', $category);
            });
        }

        // 4. Clone main query for Pie Chart aggregates before applying status filter
        $baseQueryForStatus = clone $query;

        // Apply Service Request Status Filter directly to the main query
        if ($request->filled('task_status')) {
            $statusFilter = $request->task_status;
            $now = Carbon::now()->toDateString();
            
            if ($statusFilter === 'completed') {
                $query->where('status', 'completed');
            } elseif ($statusFilter === 'in_progress') {
                $query->whereIn('status', ['in_progress', 'review'])
                      ->where(function($q) use ($now) {
                          $q->whereNull('deadline')->orWhere('deadline', '>=', $now);
                      });
            } elseif ($statusFilter === 'pending') {
                $query->whereIn('status', ['pending', 'quoted', 'approved'])
                      ->where(function($q) use ($now) {
                          $q->whereNull('deadline')->orWhere('deadline', '>=', $now);
                      });
            } elseif ($statusFilter === 'overdue') {
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      ->whereNotNull('deadline')
                      ->where('deadline', '<', $now);
            }
        }

        // --- Aggregating Dashboard Widget Metrics ---
        $totalRequests = (clone $query)->count();
        
        $activeAssignments = Assignment::whereNotIn('status', ['completed'])
            ->whereHas('serviceRequest', function ($q) use ($request, $isManagement, $user) {
                if (!$isManagement) {
                    $q->where('assigned_to', $user->id);
                } else {
                    if ($request->filled('assigned_to')) {
                        $q->where('assigned_to', $request->assigned_to);
                    }
                }
                if ($request->filled('service_category')) {
                    $q->where('service_category', $request->service_category);
                }
            })->count();

        $completedServices = (clone $query)->where('status', 'completed')->count();
        
        // Approvals are scoped
        if ($isManagement) {
            $pendingApprovals = Approval::where('status', 'pending')->count();
        } else {
            $pendingApprovals = Approval::where('status', 'pending')
                ->where('approver_id', $user->id)->count();
        }

        // Calculate KPI Performance Percentage based on Service Request completions
        $completionRate = ($totalRequests > 0) ? round(($completedServices / $totalRequests) * 100, 1) : 100.0;
        $kpiPercentage = $completionRate;

        // Turnaround Time calculation
        $completedRequests = (clone $query)->where('status', 'completed')->whereNotNull('completed_at')->get();
        $turnaroundDays = [];
        foreach ($completedRequests as $r) {
            $turnaroundDays[] = Carbon::parse($r->created_at)->diffInDays(Carbon::parse($r->completed_at));
        }
        $avgTurnaroundTime = count($turnaroundDays) > 0 ? round(array_sum($turnaroundDays) / count($turnaroundDays), 1) : 0.0;

        // --- Service Request Status counts for Pie/Donut Chart ---
        $nowStr = Carbon::now()->toDateString();
        $taskStatuses = [
            'completed' => (clone $baseQueryForStatus)->where('status', 'completed')->count(),
            'in_progress' => (clone $baseQueryForStatus)->whereIn('status', ['in_progress', 'review'])
                ->where(function($q) use ($nowStr) {
                    $q->whereNull('deadline')->orWhere('deadline', '>=', $nowStr);
                })->count(),
            'pending' => (clone $baseQueryForStatus)->whereIn('status', ['pending', 'quoted', 'approved'])
                ->where(function($q) use ($nowStr) {
                    $q->whereNull('deadline')->orWhere('deadline', '>=', $nowStr);
                })->count(),
            'overdue' => (clone $baseQueryForStatus)->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('deadline')
                ->where('deadline', '<', $nowStr)->count(),
        ];

        // --- Category Volume and Performance for Bar Chart ---
        $categoriesList = ['translation', 'editing', 'brailling', 'consultancy', 'sign_language'];
        $categoryVolume = [];
        $categoryTurnaround = [];
        foreach ($categoriesList as $cat) {
            $catLabel = ucwords(str_replace('_', ' ', $cat));
            
            $volQuery = ServiceRequest::where('service_category', $cat);
            $compQuery = ServiceRequest::where('service_category', $cat)->where('status', 'completed')->whereNotNull('completed_at');
            
            if (!$isManagement) {
                $volQuery->where('assigned_to', $user->id);
                $compQuery->where('assigned_to', $user->id);
            }
            
            $categoryVolume[$catLabel] = $volQuery->count();
            
            // Turnaround per category
            $catCompleted = $compQuery->get();
            $catDays = [];
            foreach ($catCompleted as $cc) {
                $catDays[] = Carbon::parse($cc->created_at)->diffInDays(Carbon::parse($cc->completed_at));
            }
            $categoryTurnaround[$catLabel] = count($catDays) > 0 ? round(array_sum($catDays) / count($catDays), 1) : 0.0;
        }

        // --- Staff Productivity Metrics ---
        $staff = User::where('primary_category', 'Staff')->get();
        
        $staffProductivity = [];
        foreach ($staff as $s) {
            if (!$isManagement && $s->id !== $user->id) {
                continue;
            }

            $tasksDone = Task::where('status', 'completed')
                ->whereHas('assignment', function($q) use ($s) {
                    $q->where('assigned_to', $s->id);
                })->count();
            
            $reqsDone = ServiceRequest::where('status', 'completed')
                ->where('assigned_to', $s->id)->count();

            if ($tasksDone > 0 || $reqsDone > 0) {
                $staffProductivity[] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'department' => $s->department ? $s->department->name : 'General',
                    'tasks_completed' => $tasksDone,
                    'requests_completed' => $reqsDone,
                    'score' => ($tasksDone * 10) + ($reqsDone * 25),
                ];
            }
        }
        usort($staffProductivity, fn($a, $b) => $b['score'] <=> $a['score']);

        // --- Client Satisfaction Metrics ---
        $avgSatisfaction = ServiceRequest::whereNotNull('rating');
        if (!$isManagement) {
            $avgSatisfaction->where('assigned_to', $user->id);
        }
        $avgSatisfactionScore = $avgSatisfaction->avg('rating');
        $avgSatisfactionScore = ($avgSatisfactionScore && $avgSatisfactionScore > 0) ? round($avgSatisfactionScore * 20, 1) : 94.0;

        // --- Trend Analysis over 6 months ---
        $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
        $rawDateFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $yearMonth = $date->format('Y-m');
            $monthName = $date->format('M Y');
            
            $volQ = ServiceRequest::whereRaw("{$rawDateFormat} = ?", [$yearMonth]);
            $compQ = ServiceRequest::where('status', 'completed')
                ->whereRaw("{$rawDateFormat} = ?", [$yearMonth]);

            if (!$isManagement) {
                $volQ->where('assigned_to', $user->id);
                $compQ->where('assigned_to', $user->id);
            }

            $monthlyTrends[] = [
                'month' => $monthName,
                'volume' => $volQ->count(),
                'completed' => $compQ->count(),
            ];
        }

        // Fetch persisted reports from the database with Searchable/Filterable options
        $reportsQuery = Report::with('generatedByUser');
        if (!$isManagement) {
            $reportsQuery->where('generated_by', $user->id);
        }

        // Apply dynamic search inside the generated reports table
        if ($request->filled('search')) {
            $reportsQuery->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('archive_type')) {
            $reportsQuery->where('report_type', $request->archive_type);
        }

        $reports = $reportsQuery->orderBy('created_at', 'desc')->paginate(10);

        // Fetch student enrollment stats & list
        $seBase = \App\Models\CourseEnrollment::whereHas('user', function($q) {
            $q->where('primary_category', 'Student');
        });
        if ($request->filled('student_course_id')) {
            $seBase->whereHas('intake.course', function($q) use ($request) {
                $q->where('id', $request->student_course_id);
            });
        }
        if ($request->filled('student_unit_id')) {
            $seBase->whereHas('intake.course.department', function($q) use ($request) {
                $q->where('id', $request->student_unit_id);
            });
        }
        if ($request->filled('date_start')) {
            $seBase->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }
        if ($request->filled('date_end')) {
            $seBase->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        $studentEnrollmentStats = [
            'total' => (clone $seBase)->count(),
            'active' => (clone $seBase)->where('enrollment_status', 'active')->count(),
            'pending' => (clone $seBase)->where('enrollment_status', 'pending')->count(),
            'completed' => (clone $seBase)->where('enrollment_status', 'completed')->count(),
            'dropped' => (clone $seBase)->where('enrollment_status', 'dropped')->count(),
        ];

        // --- Student Enrollment Trend Analysis over 6 months ---
        $enrollmentMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $yearMonth = $date->format('Y-m');
            $monthName = $date->format('M Y');
            
            $enrollQ = \App\Models\CourseEnrollment::whereHas('user', function($q) {
                $q->where('primary_category', 'Student');
            })->whereRaw("{$rawDateFormat} = ?", [$yearMonth]);
            if ($request->filled('student_course_id')) {
                $enrollQ->whereHas('intake.course', function($q) use ($request) {
                    $q->where('id', $request->student_course_id);
                });
            }
            if ($request->filled('student_unit_id')) {
                $enrollQ->whereHas('intake.course.department', function($q) use ($request) {
                    $q->where('id', $request->student_unit_id);
                });
            }

            $enrollmentMonthly[] = [
                'month' => $monthName,
                'count' => $enrollQ->count(),
            ];
        }

        $seQuery = \App\Models\CourseEnrollment::whereHas('user', function($q) {
            $q->where('primary_category', 'Student');
        })->with(['user', 'intake.course.department']);
        if ($request->filled('student_course_id')) {
            $seQuery->whereHas('intake.course', function($q) use ($request) {
                $q->where('id', $request->student_course_id);
            });
        }
        if ($request->filled('student_unit_id')) {
            $seQuery->whereHas('intake.course.department', function($q) use ($request) {
                $q->where('id', $request->student_unit_id);
            });
        }
        if ($request->filled('student_enrollment_status')) {
            $seQuery->where('enrollment_status', $request->student_enrollment_status);
        }
        if ($request->filled('date_start')) {
            $seQuery->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }
        if ($request->filled('date_end')) {
            $seQuery->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }
        $enrolledStudents = $seQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'students_page')->withQueryString();

        // Fetch supporting filter options
        $filterOptions = [
            'departments' => Department::select('id', 'name', 'code')->get(),
            'courses' => \App\Models\Course::select('id', 'title', 'code')->get(),
            'staff' => User::where('primary_category', 'Staff')->select('id', 'name', 'email')->get(),
            'categories' => array_map(fn($c) => ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))], $categoriesList),
        ];

        return Inertia::render('Reports/Index', [
            'byCategory' => $categoryVolume,
            'categoryTurnaround' => $categoryTurnaround,
            'byStatus' => $taskStatuses,
            'monthly' => $monthlyTrends,
            'serviceRequests' => (clone $query)->with(['client', 'assignedTo'])->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
            'totals' => [
                'total_requests' => $totalRequests,
                'active_assignments' => $activeAssignments,
                'completed_services' => $completedServices,
                'pending_approvals' => $pendingApprovals,
                'kpi_performance' => $kpiPercentage,
                'avg_turnaround' => $avgTurnaroundTime,
                'client_satisfaction' => $avgSatisfactionScore,
                'revenue_generated' => \App\Models\Payment::where('payments.status', 'verified')
                    ->join('quotations', 'payments.quotation_id', '=', 'quotations.id')
                    ->selectRaw('quotations.currency, SUM(payments.amount_paid) as total')
                    ->groupBy('quotations.currency')
                    ->pluck('total', 'currency')
                    ->toArray(),
                'short_courses_revenue' => \App\Models\CourseEnrollment::where('course_enrollments.payment_status', 'verified')
                    ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                    ->join('courses', 'course_intakes.course_id', '=', 'courses.id')
                    ->selectRaw('courses.currency, SUM(courses.price) as total')
                    ->groupBy('courses.currency')
                    ->pluck('total', 'currency')
                    ->toArray(),
            ],
            'studentEnrollmentStats' => $studentEnrollmentStats,
            'enrolledStudents' => $enrolledStudents,
            'enrollmentMonthly' => $enrollmentMonthly,
            'staffProductivity' => $staffProductivity,
            'reports' => $reports,
            'filterOptions' => $filterOptions,
            'currentFilters' => $request->only(['date_start', 'date_end', 'department_id', 'service_category', 'assigned_to', 'task_status', 'search', 'archive_type', 'student_course_id', 'student_unit_id', 'student_enrollment_status']),
            'isManagement' => $isManagement
        ]);
    }

    /**
     * Store and generate a persistent report file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'report_type' => 'required|string',
            'format' => 'required|in:pdf,excel',
        ]);

        $user = Auth::user();
        $title = $request->title;
        $type = $request->report_type;
        $format = $request->format;
        $filters = $request->get('filters', []);

        // Aggregate matching data depending on the specialized report type selected
        $data = $this->aggregateReportData($filters, $type);
        $data['title'] = $title;
        $data['report_type'] = $type;
        $data['generated_by'] = $user->name;
        $data['generated_date'] = Carbon::now()->format('F d, Y h:i A');

        $filename = 'report_' . $type . '_' . time() . '.' . ($format == 'pdf' ? 'pdf' : 'csv');
        $storagePath = 'reports/' . $filename;

        if ($format == 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', $data);
            Storage::disk('public')->put($storagePath, $pdf->output());
        } else {
            $csvStream = fopen('php://temp', 'r+');
            fputcsv($csvStream, ['MSUNLI OPERATIONAL & COMPREHENSIVE SPECIALIZED REPORT']);
            fputcsv($csvStream, ['Title:', $title]);
            fputcsv($csvStream, ['Category Type:', ucwords(str_replace('_', ' ', $type))]);
            fputcsv($csvStream, ['Generated By:', $user->name]);
            fputcsv($csvStream, ['Compilation Date:', $data['generated_date']]);
            fputcsv($csvStream, []);

            if ($type === 'student_enrollment') {
                fputcsv($csvStream, ['STUDENT ENROLLMENT METRICS']);
                fputcsv($csvStream, ['Total Enrolled', $data['enrollment_stats']['total']]);
                fputcsv($csvStream, ['Active Students', $data['enrollment_stats']['active']]);
                fputcsv($csvStream, ['Pending Students', $data['enrollment_stats']['pending']]);
                fputcsv($csvStream, ['Completed Students', $data['enrollment_stats']['completed']]);
                fputcsv($csvStream, ['Dropped Students', $data['enrollment_stats']['dropped']]);
            } else {
                fputcsv($csvStream, ['KEY PERFORMANCE METRICS']);
                fputcsv($csvStream, ['Total Volumetric requests', $data['totals']['total_requests']]);
                fputcsv($csvStream, ['Active Assignments', $data['totals']['active_assignments']]);
                fputcsv($csvStream, ['Completed Operations', $data['totals']['completed_services']]);
                fputcsv($csvStream, ['Avg Turnaround Speed (Days)', $data['totals']['avg_turnaround']]);
                fputcsv($csvStream, ['Customer Satisfaction Index', $data['totals']['client_satisfaction']]);
            }
            fputcsv($csvStream, []);

            // Dynamic Excel column builder based on report type
            if ($type === 'quotations') {
                fputcsv($csvStream, ['QUOTATION AUDIT LEDGER']);
                fputcsv($csvStream, ['Reference Number', 'Prepared By', 'Amount', 'Currency', 'Valid Until', 'Financial Status']);
                foreach ($data['quotations'] as $quote) {
                    fputcsv($csvStream, [
                        $quote->reference_number,
                        $quote->preparedBy ? $quote->preparedBy->name : 'System Generated',
                        $quote->amount,
                        $quote->currency,
                        $quote->valid_until ? $quote->valid_until->format('Y-m-d') : 'N/A',
                        ucwords($quote->status)
                    ]);
                }
            } elseif ($type === 'workflow_progress') {
                fputcsv($csvStream, ['WORKFLOW TASK METRICS']);
                fputcsv($csvStream, ['Task Title', 'Status', 'Priority', 'Due Date', 'Specialist Assigned']);
                foreach ($data['tasks'] as $task) {
                    fputcsv($csvStream, [
                        $task->title,
                        ucwords(str_replace('_', ' ', $task->status)),
                        ucwords($task->priority),
                        $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A',
                        ($task->assignment && $task->assignment->assignedTo) ? $task->assignment->assignedTo->name : 'Unassigned'
                    ]);
                }
            } elseif ($type === 'staff_workload') {
                fputcsv($csvStream, ['STAFF CAPACITY & WORKLOAD WIDGETS']);
                fputcsv($csvStream, ['Staff Member', 'Department', 'Tasks Completed', 'Requests Completed', 'Workload Score']);
                foreach ($data['staff_workload'] as $sw) {
                    fputcsv($csvStream, [
                        $sw['name'],
                        $sw['department'],
                        $sw['tasks_completed'],
                        $sw['requests_completed'],
                        $sw['score'] . ' pts'
                    ]);
                }
            } elseif ($type === 'kpi_performance') {
                fputcsv($csvStream, ['KPI SCORECARD METRICS']);
                fputcsv($csvStream, ['Metric Record', 'Specialist/Subject', 'Metric Value', 'Aggregation Timestamp']);
                foreach ($data['kpis'] as $kpi) {
                    fputcsv($csvStream, [
                        ucwords(str_replace('_', ' ', $kpi->metric_type)),
                        $kpi->user ? $kpi->user->name : 'General MSULI',
                        $kpi->metric_value,
                        $kpi->created_at->format('Y-m-d H:i')
                    ]);
                }
            } elseif ($type === 'student_enrollment') {
                fputcsv($csvStream, ['STUDENT ENROLLMENT REGISTER']);
                fputcsv($csvStream, ['Student Name', 'Email Address', 'Course Name', 'MSUNLI Unit', 'Tuition Paid', 'Payment Status', 'Enrollment Status', 'Enrolled Date']);
                foreach ($data['student_enrollments'] as $se) {
                    fputcsv($csvStream, [
                        $se->user ? $se->user->name : 'N/A',
                        $se->user ? $se->user->email : 'N/A',
                        $se->intake && $se->intake->course ? $se->intake->course->title : 'N/A',
                        ($se->intake && $se->intake->course && $se->intake->course->department) ? $se->intake->course->department->code : 'N/A',
                        $se->amount_paid ?? '0.00',
                        ucwords($se->payment_status),
                        ucwords($se->enrollment_status),
                        $se->created_at->format('Y-m-d')
                    ]);
                }
            } else {
                fputcsv($csvStream, ['CLIENT LANGUAGE SERVICES RECORDS']);
                fputcsv($csvStream, ['Reference Number', 'Client Organization', 'Category', 'Priority', 'Status', 'Submitted Date']);
                foreach ($data['requests'] as $req) {
                    fputcsv($csvStream, [
                        $req->reference_number,
                        $req->client ? ($req->client->organization ?? $req->client->contact_person) : 'N/A',
                        ucwords(str_replace('_', ' ', $req->service_category)),
                        ucwords($req->priority),
                        ucwords($req->status),
                        $req->created_at->format('Y-m-d')
                    ]);
                }
            }

            rewind($csvStream);
            Storage::disk('public')->put($storagePath, stream_get_contents($csvStream));
            fclose($csvStream);
        }

        // Insert database record
        Report::create([
            'generated_by' => $user->id,
            'report_type' => $type,
            'title' => $title,
            'parameters' => $filters,
            'file_path' => '/reports/download/' . $filename,
            'format' => $format,
        ]);

        return redirect()->back()->with('success', 'Specialized Report generated successfully!');
    }

    /**
     * Instant dynamically generated export (streamed).
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel',
            'report_type' => 'nullable|string',
        ]);

        $type = $request->get('report_type', 'client_services');
        $filters = $request->only(['date_start', 'date_end', 'department_id', 'service_category', 'assigned_to', 'task_status', 'student_course_id', 'student_unit_id', 'student_enrollment_status']);
        
        $data = $this->aggregateReportData($filters, $type);
        $data['title'] = 'Dynamic ' . ucwords(str_replace('_', ' ', $type)) . ' Report';
        $data['report_type'] = $type;
        $data['generated_by'] = Auth::user()->name;
        $data['generated_date'] = Carbon::now()->format('F d, Y h:i A');

        if ($request->format == 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', $data);
            return $pdf->download('operational_report_' . $type . '_' . time() . '.pdf');
        } else {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="operational_' . $type . '_report_' . time() . '.csv"',
            ];

            $callback = function() use ($data, $type) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['MSUNLI OPERATIONAL ANALYTICS']);
                fputcsv($file, ['Report Category:', ucwords(str_replace('_', ' ', $type))]);
                fputcsv($file, ['Generated By:', $data['generated_by']]);
                fputcsv($file, ['Date:', $data['generated_date']]);
                fputcsv($file, []);

                if ($type === 'student_enrollment') {
                    fputcsv($file, ['STUDENT ENROLLMENT METRICS']);
                    fputcsv($file, ['Total Enrolled', $data['enrollment_stats']['total']]);
                    fputcsv($file, ['Active Students', $data['enrollment_stats']['active']]);
                    fputcsv($file, ['Pending Students', $data['enrollment_stats']['pending']]);
                    fputcsv($file, ['Completed Students', $data['enrollment_stats']['completed']]);
                    fputcsv($file, ['Dropped Students', $data['enrollment_stats']['dropped']]);
                } else {
                    fputcsv($file, ['KPI METRIC SUMMARY']);
                    fputcsv($file, ['Total Volumetric Requests', $data['totals']['total_requests']]);
                    fputcsv($file, ['Active Assignments', $data['totals']['active_assignments']]);
                    fputcsv($file, ['Completed Services', $data['totals']['completed_services']]);
                    fputcsv($file, ['KPI Performance Score', $data['totals']['kpi_performance'] . '%']);
                    fputcsv($file, ['Avg Turnaround Speed (Days)', $data['totals']['avg_turnaround']]);
                }
                fputcsv($file, []);

                // Column maps matching report type
                if ($type === 'quotations') {
                    fputcsv($file, ['QUOTATION AUDIT LEDGER']);
                    fputcsv($file, ['Reference Number', 'Prepared By', 'Amount', 'Currency', 'Valid Until', 'Financial Status']);
                    foreach ($data['quotations'] as $quote) {
                        fputcsv($file, [
                            $quote->reference_number,
                            $quote->preparedBy ? $quote->preparedBy->name : 'System Generated',
                            $quote->amount,
                            $quote->currency,
                            $quote->valid_until ? $quote->valid_until->format('Y-m-d') : 'N/A',
                            ucwords($quote->status)
                        ]);
                    }
                } elseif ($type === 'workflow_progress') {
                    fputcsv($file, ['WORKFLOW TASK METRICS']);
                    fputcsv($file, ['Task Title', 'Status', 'Priority', 'Due Date', 'Specialist Assigned']);
                    foreach ($data['tasks'] as $task) {
                        fputcsv($file, [
                            $task->title,
                            ucwords(str_replace('_', ' ', $task->status)),
                            ucwords($task->priority),
                            $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A',
                            ($task->assignment && $task->assignment->assignedTo) ? $task->assignment->assignedTo->name : 'Unassigned'
                        ]);
                    }
                } elseif ($type === 'staff_workload') {
                    fputcsv($file, ['STAFF CAPACITY & WORKLOAD WIDGETS']);
                    fputcsv($file, ['Staff Member', 'Department', 'Tasks Completed', 'Requests Completed', 'Workload Score']);
                    foreach ($data['staff_workload'] as $sw) {
                        fputcsv($file, [
                            $sw['name'],
                            $sw['department'],
                            $sw['tasks_completed'],
                            $sw['requests_completed'],
                            $sw['score'] . ' pts'
                        ]);
                    }
                } elseif ($type === 'kpi_performance') {
                    fputcsv($file, ['KPI SCORECARD METRICS']);
                    fputcsv($file, ['Metric Record', 'Specialist/Subject', 'Metric Value', 'Aggregation Timestamp']);
                    foreach ($data['kpis'] as $kpi) {
                        fputcsv($file, [
                            ucwords(str_replace('_', ' ', $kpi->metric_type)),
                            $kpi->user ? $kpi->user->name : 'General MSULI',
                            $kpi->metric_value,
                            $kpi->created_at->format('Y-m-d H:i')
                        ]);
                    }
                } elseif ($type === 'student_enrollment') {
                    fputcsv($file, ['STUDENT ENROLLMENT REGISTER']);
                    fputcsv($file, ['Student Name', 'Email Address', 'Course Name', 'MSUNLI Unit', 'Tuition Paid', 'Payment Status', 'Enrollment Status', 'Enrolled Date']);
                    foreach ($data['student_enrollments'] as $se) {
                        fputcsv($file, [
                            $se->user ? $se->user->name : 'N/A',
                            $se->user ? $se->user->email : 'N/A',
                            $se->intake && $se->intake->course ? $se->intake->course->title : 'N/A',
                            ($se->intake && $se->intake->course && $se->intake->course->department) ? $se->intake->course->department->code : 'N/A',
                            $se->amount_paid ?? '0.00',
                            ucwords($se->payment_status),
                            ucwords($se->enrollment_status),
                            $se->created_at->format('Y-m-d')
                        ]);
                    }
                } else {
                    fputcsv($file, ['CLIENT LANGUAGE SERVICES RECORDS']);
                    fputcsv($file, ['Reference Number', 'Client Organization', 'Category', 'Priority', 'Status', 'Submitted Date']);
                    foreach ($data['requests'] as $req) {
                        fputcsv($file, [
                            $req->reference_number,
                            $req->client ? ($req->client->organization ?? $req->client->contact_person) : 'N/A',
                            ucwords(str_replace('_', ' ', $req->service_category)),
                            ucwords($req->priority),
                            ucwords($req->status),
                            $req->created_at->format('Y-m-d')
                        ]);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    /**
     * Securely download a generated report file.
     */
    public function download($filename)
    {
        $path = 'reports/' . $filename;
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'The requested report file does not exist.');
        }

        return Storage::disk('public')->download($path);
    }

    private function checkReportAccess()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }
        $isManagement = $user->hasAnyRole(['executive_director', 'deputy_director', 'admin_assistant', 'secretary', 'ict_administrator']);
        if (!$isManagement) {
            abort(403, 'Unauthorized. You do not have report permissions.');
        }
    }

    private function resolveDateRange($preset, $dateStartInput, $dateEndInput)
    {
        $dateStart = null;
        $dateEnd = null;

        if ($preset) {
            if ($preset === 'today') {
                $dateStart = Carbon::today()->startOfDay();
                $dateEnd = Carbon::today()->endOfDay();
            } elseif ($preset === 'this_week') {
                $dateStart = Carbon::now()->startOfWeek();
                $dateEnd = Carbon::now()->endOfWeek();
            } elseif ($preset === 'this_month') {
                $dateStart = Carbon::now()->startOfMonth();
                $dateEnd = Carbon::now()->endOfMonth();
            } elseif ($preset === 'this_quarter') {
                $dateStart = Carbon::now()->startOfQuarter();
                $dateEnd = Carbon::now()->endOfQuarter();
            } elseif ($preset === 'this_year') {
                $dateStart = Carbon::now()->startOfYear();
                $dateEnd = Carbon::now()->endOfYear();
            } elseif (str_starts_with($preset, 'fy_')) {
                $year = (int) substr($preset, 3);
                $dateStart = Carbon::create($year, 1, 1)->startOfDay();
                $dateEnd = Carbon::create($year, 12, 31)->endOfDay();
            }
        } else {
            if ($dateStartInput) {
                $dateStart = Carbon::parse($dateStartInput)->startOfDay();
            }
            if ($dateEndInput) {
                $dateEnd = Carbon::parse($dateEndInput)->endOfDay();
            }
        }

        return [$dateStart, $dateEnd];
    }

    private function getLanguageServicesRevenueData(Request $request)
    {
        $filters = $request->only([
            'preset', 'date_start', 'date_end', 'service_category', 'client_id', 'payment_status', 'payment_method', 'search', 'currency', 'sort_by', 'sort_desc'
        ]);

        [$dateStart, $dateEnd] = $this->resolveDateRange(
            $filters['preset'] ?? null,
            $filters['date_start'] ?? null,
            $filters['date_end'] ?? null
        );

        // Build base query
        $query = Payment::with(['client', 'serviceRequest.client', 'quotation.serviceRequest', 'verifiedBy'])
            ->leftJoin('quotations', 'payments.quotation_id', '=', 'quotations.id')
            ->select('payments.*', 'quotations.currency as quotation_currency');

        if ($dateStart) {
            $query->where('payments.created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('payments.created_at', '<=', $dateEnd);
        }
        if (!empty($filters['service_category'])) {
            $cat = $filters['service_category'];
            $query->whereHas('serviceRequest', function($q) use ($cat) {
                $q->where('service_category', $cat);
            });
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payments.status', $filters['payment_status']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where('bank_used', $filters['payment_method']);
        }
        if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
            $query->where('quotations.currency', $filters['currency']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('bank_used', 'like', $search)
                  ->orWhere('payments.id', 'like', $search)
                  ->orWhereHas('client', function($sq) use ($search) {
                      $sq->where('name', 'like', $search);
                  })
                  ->orWhereHas('serviceRequest.client', function($sq) use ($search) {
                      $sq->where('organization', 'like', $search)
                        ->orWhere('contact_person', 'like', $search);
                  })
                  ->orWhereHas('quotation', function($sq) use ($search) {
                      $sq->where('reference_number', 'like', $search);
                  })
                  ->orWhereHas('serviceRequest', function($sq) use ($search) {
                      $sq->where('title', 'like', $search);
                  });
            });
        }

        return [$query, $dateStart, $dateEnd, $filters];
    }

    private function calculateLanguageServicesRevenueAggregates($query, $dateStart, $dateEnd, $filters)
    {
        // Cache key based on serialized parameters
        $cacheKey = 'lang_rev_agg_' . md5(serialize([$filters, $dateStart ? $dateStart->toDateTimeString() : null, $dateEnd ? $dateEnd->toDateTimeString() : null]));

        return Cache::remember($cacheKey, 60, function() use ($query, $dateStart, $dateEnd, $filters) {
            $totalRevenue = [];
            $totalTransactions = [];
            $averageRevenue = [];
            $revenueGrowth = [];
            $outstandingPayments = [];

            // Get all currencies present in the query
            $activeCurrencies = (clone $query)
                ->select('quotations.currency as currency')
                ->distinct()
                ->whereNotNull('quotations.currency')
                ->pluck('currency')
                ->toArray();

            if (empty($activeCurrencies)) {
                if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
                    $activeCurrencies = [$filters['currency']];
                } else {
                    $activeCurrencies = ['USD', 'ZAR', 'ZWG'];
                }
            }

            // Total Revenue & Transactions by Currency
            $revenueRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('SUM(payments.amount_paid) as total, COUNT(*) as tx_count')
                ->where('payments.status', 'verified')
                ->groupBy('quotations.currency')
                ->get();
            
            $verifiedCountByCurrency = [];
            foreach ($revenueRaw as $row) {
                $totalRevenue[$row->currency] = (float)$row->total;
                $verifiedCountByCurrency[$row->currency] = (int)$row->tx_count;
            }

            // Total Transactions (overall records including pending/rejected) by Currency
            $allTxRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('COUNT(*) as tx_count')
                ->groupBy('quotations.currency')
                ->get();
            foreach ($allTxRaw as $row) {
                $totalTransactions[$row->currency] = (int)$row->tx_count;
            }

            // Average Revenue per Transaction by Currency
            foreach ($activeCurrencies as $curr) {
                $tot = $totalRevenue[$curr] ?? 0.0;
                $cnt = $verifiedCountByCurrency[$curr] ?? 0;
                $averageRevenue[$curr] = $cnt > 0 ? (float)($tot / $cnt) : 0.0;
            }

            // Outstanding Payments (sum of remaining balances on approved quotes matching the date range / filters)
            $quoteQuery = Quotation::where('status', 'approved');
            if (!empty($filters['service_category'])) {
                $cat = $filters['service_category'];
                $quoteQuery->whereHas('serviceRequest', function($q) use ($cat) {
                    $q->where('service_category', $cat);
                });
            }
            if (!empty($filters['client_id'])) {
                $quoteQuery->whereHas('serviceRequest', function($q) use ($filters) {
                    $q->where('submitted_by', $filters['client_id']);
                });
            }
            if ($dateStart) {
                $quoteQuery->where('created_at', '>=', $dateStart);
            }
            if ($dateEnd) {
                $quoteQuery->where('created_at', '<=', $dateEnd);
            }
            if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
                $quoteQuery->where('currency', $filters['currency']);
            }

            $outstandingRaw = $quoteQuery
                ->withSum(['payments as total_verified' => function($q) {
                    $q->where('status', 'verified');
                }], 'amount_paid')
                ->get()
                ->groupBy('currency');

            foreach ($outstandingRaw as $curr => $quotes) {
                $outstandingPayments[$curr] = $quotes->sum(fn($q) => max(0.0, (float)($q->amount - ($q->total_verified ?? 0))));
            }

            // Growth calculation by currency
            $diffDays = 30;
            if ($dateStart && $dateEnd) {
                $diffDays = max(1, $dateStart->diffInDays($dateEnd));
                $prevStart = $dateStart->copy()->subDays($diffDays + 1)->startOfDay();
                $prevEnd = $dateStart->copy()->subDay()->endOfDay();
            } else {
                $prevStart = Carbon::now()->subDays(60)->startOfDay();
                $prevEnd = Carbon::now()->subDays(31)->endOfDay();
                $dateStart = Carbon::now()->subDays(30)->startOfDay();
                $dateEnd = Carbon::now()->endOfDay();
            }

            // Build base query for growth (ignoring date parameters)
            $growthQuery = Payment::join('quotations', 'payments.quotation_id', '=', 'quotations.id');
            if (!empty($filters['service_category'])) {
                $cat = $filters['service_category'];
                $growthQuery->whereHas('serviceRequest', function($q) use ($cat) {
                    $q->where('service_category', $cat);
                });
            }
            if (!empty($filters['client_id'])) {
                $growthQuery->where('client_id', $filters['client_id']);
            }
            if (!empty($filters['payment_status'])) {
                $growthQuery->where('payments.status', $filters['payment_status']);
            }
            if (!empty($filters['payment_method'])) {
                $growthQuery->where('bank_used', $filters['payment_method']);
            }
            if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
                $growthQuery->where('quotations.currency', $filters['currency']);
            }

            $currentRevenueRaw = (clone $growthQuery)
                ->where('payments.status', 'verified')
                ->where('payments.created_at', '>=', $dateStart)
                ->where('payments.created_at', '<=', $dateEnd)
                ->selectRaw('quotations.currency as currency, SUM(payments.amount_paid) as total')
                ->groupBy('quotations.currency')
                ->pluck('total', 'currency')
                ->toArray();

            $prevRevenueRaw = (clone $growthQuery)
                ->where('payments.status', 'verified')
                ->where('payments.created_at', '>=', $prevStart)
                ->where('payments.created_at', '<=', $prevEnd)
                ->selectRaw('quotations.currency as currency, SUM(payments.amount_paid) as total')
                ->groupBy('quotations.currency')
                ->pluck('total', 'currency')
                ->toArray();

            foreach ($activeCurrencies as $curr) {
                $curVal = (float)($currentRevenueRaw[$curr] ?? 0.0);
                $prevVal = (float)($prevRevenueRaw[$curr] ?? 0.0);
                if ($prevVal > 0) {
                    $revenueGrowth[$curr] = (($curVal - $prevVal) / $prevVal) * 100;
                } else {
                    $revenueGrowth[$curr] = $curVal > 0 ? 100.0 : 0.0;
                }
            }

            // Fill empty active currencies to ensure API contracts
            foreach ($activeCurrencies as $curr) {
                if (!isset($totalRevenue[$curr])) $totalRevenue[$curr] = 0.0;
                if (!isset($totalTransactions[$curr])) $totalTransactions[$curr] = 0;
                if (!isset($averageRevenue[$curr])) $averageRevenue[$curr] = 0.0;
                if (!isset($revenueGrowth[$curr])) $revenueGrowth[$curr] = 0.0;
                if (!isset($outstandingPayments[$curr])) $outstandingPayments[$curr] = 0.0;
            }

            // Visualizations
            $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
            $dateFormat = $isSqlite ? "strftime('%Y-%m-%d', payments.created_at)" : "DATE_FORMAT(payments.created_at, '%Y-%m-%d')";
            $monthFormat = $isSqlite ? "strftime('%Y-%m', payments.created_at)" : "DATE_FORMAT(payments.created_at, '%Y-%m')";

            // 1. Revenue Trend (Line Chart)
            $trendRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw("{$dateFormat} as date_group, SUM(payments.amount_paid) as total")
                ->where('payments.status', 'verified')
                ->groupBy('quotations.currency', 'date_group')
                ->orderBy('date_group')
                ->get();
            
            $trend = [];
            foreach ($trendRaw as $r) {
                $trend[$r->currency][] = ['date' => $r->date_group, 'total' => (float)$r->total];
            }

            // 2. Revenue by Month (Bar Chart)
            $byMonthRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw("{$monthFormat} as month_group, SUM(payments.amount_paid) as total")
                ->where('payments.status', 'verified')
                ->groupBy('quotations.currency', 'month_group')
                ->orderBy('month_group')
                ->get();

            $byMonth = [];
            foreach ($byMonthRaw as $r) {
                $byMonth[$r->currency][] = [
                    'month' => Carbon::parse($r->month_group . '-01')->format('M Y'),
                    'total' => (float)$r->total
                ];
            }

            // 3. Revenue by Service Category (Bar Chart)
            $byServiceRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('service_requests.service_category, SUM(payments.amount_paid) as total')
                ->where('payments.status', 'verified')
                ->join('service_requests', 'payments.service_request_id', '=', 'service_requests.id')
                ->groupBy('quotations.currency', 'service_requests.service_category')
                ->get();

            $byService = [];
            foreach ($byServiceRaw as $r) {
                $byService[$r->currency][] = [
                    'category' => ucwords(str_replace('_', ' ', $r->service_category)),
                    'total' => (float)$r->total
                ];
            }

            // 4. Payment Method Distribution (Pie Chart)
            $byMethodRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('bank_used as method, SUM(payments.amount_paid) as total, COUNT(*) as count')
                ->where('payments.status', 'verified')
                ->groupBy('quotations.currency', 'bank_used')
                ->get();

            $byMethod = [];
            foreach ($byMethodRaw as $r) {
                $byMethod[$r->currency][] = [
                    'method' => $r->method,
                    'total' => (float)$r->total,
                    'count' => $r->count
                ];
            }

            // 5. Revenue by Status (Pie Chart)
            $byStatusRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('payments.status, SUM(payments.amount_paid) as total, COUNT(*) as count')
                ->groupBy('quotations.currency', 'payments.status')
                ->get();

            $byStatus = [];
            foreach ($byStatusRaw as $r) {
                $byStatus[$r->currency][] = [
                    'status' => ucwords($r->status),
                    'total' => (float)$r->total,
                    'count' => $r->count
                ];
            }

            // 6. Top Services (Horizontal Bar Chart)
            $topServicesRaw = (clone $query)
                ->select('quotations.currency as currency')
                ->selectRaw('service_requests.title, SUM(payments.amount_paid) as total')
                ->where('payments.status', 'verified')
                ->join('service_requests', 'payments.service_request_id', '=', 'service_requests.id')
                ->groupBy('quotations.currency', 'service_requests.title')
                ->orderBy('total', 'desc')
                ->get();

            $topServices = [];
            foreach ($topServicesRaw as $r) {
                $topServices[$r->currency][] = [
                    'title' => $r->title,
                    'total' => (float)$r->total
                ];
            }
            // Slice to top 5 per currency
            foreach ($topServices as $curr => $list) {
                $topServices[$curr] = array_slice($list, 0, 5);
            }

            return [
                'kpis' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'average_revenue' => $averageRevenue,
                    'revenue_growth' => $revenueGrowth,
                    'outstanding_payments' => $outstandingPayments,
                ],
                'charts' => [
                    'trend' => $trend,
                    'by_month' => $byMonth,
                    'by_service' => $byService,
                    'by_method' => $byMethod,
                    'by_status' => $byStatus,
                    'top_services' => $topServices,
                ]
            ];
        });
    }

    public function languageServicesRevenue(Request $request)
    {
        $this->checkReportAccess();

        [$query, $dateStart, $dateEnd, $filters] = $this->getLanguageServicesRevenueData($request);

        $aggregates = $this->calculateLanguageServicesRevenueAggregates($query, $dateStart, $dateEnd, $filters);

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_desc', 'true') === 'true' ? 'desc' : 'asc';
        
        // Ensure sorting on payment properties works
        if (in_array($sortField, ['id', 'amount_paid', 'status', 'created_at'])) {
            $query->orderBy('payments.' . $sortField, $sortDirection);
        } elseif ($sortField === 'currency') {
            $query->orderBy('quotations.currency', $sortDirection);
        } else {
            $query->orderBy('payments.created_at', $sortDirection);
        }

        $payments = $query->paginate(10)->withQueryString();

        // Dropdowns for filters
        $clients = User::where('primary_category', 'Client')->select('id', 'name', 'email')->orderBy('name')->get();
        $categoriesList = ['translation', 'editing', 'brailling', 'consultancy', 'sign_language'];
        $categories = array_map(fn($c) => ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))], $categoriesList);
        
        $paymentMethods = Payment::whereNotNull('bank_used')->distinct()->pluck('bank_used')->filter()->values()->toArray();

        return Inertia::render('Reports/LanguageServicesRevenue', [
            'payments' => $payments,
            'kpis' => $aggregates['kpis'],
            'charts' => $aggregates['charts'],
            'filters' => $filters,
            'filterOptions' => [
                'clients' => $clients,
                'categories' => $categories,
                'payment_methods' => $paymentMethods,
            ]
        ]);
    }

    public function exportLanguageServicesRevenue(Request $request)
    {
        $this->checkReportAccess();

        $format = $request->input('format', 'csv');

        [$query, $dateStart, $dateEnd, $filters] = $this->getLanguageServicesRevenueData($request);
        $payments = $query->get();

        $aggregates = $this->calculateLanguageServicesRevenueAggregates($query, $dateStart, $dateEnd, $filters);
        $kpis = $aggregates['kpis'];

        if ($format === 'pdf') {
            $data = [
                'title' => 'Language Services Revenue Report',
                'payments' => $payments,
                'kpis' => $kpis,
                'filters' => $filters,
                'generated_by' => Auth::user()->name,
                'generated_date' => now()->format('F d, Y h:i A'),
                'type' => 'language_services'
            ];

            $pdf = Pdf::loadView('reports.revenue_pdf', $data);
            return $pdf->download('language_services_revenue_report_' . time() . '.pdf');
        } else {
            // Excel/CSV
            $filename = 'language_services_revenue_' . time() . '.' . ($format === 'excel' ? 'xlsx' : 'csv');
            $contentType = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';
            
            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($payments, $kpis) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['MSU National Language Institute - Language Services Revenue Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);
                
                fputcsv($file, ['KPI Summary by Currency']);
                fputcsv($file, ['Currency', 'Total Revenue', 'Total Transactions', 'Average Revenue', 'Revenue Growth (%)', 'Outstanding Payments']);
                foreach ($kpis['total_revenue'] as $curr => $total) {
                    fputcsv($file, [
                        $curr,
                        number_format($total, 2),
                        $kpis['total_transactions'][$curr] ?? 0,
                        number_format($kpis['average_revenue'][$curr] ?? 0.0, 2),
                        number_format($kpis['revenue_growth'][$curr] ?? 0.0, 2) . '%',
                        number_format($kpis['outstanding_payments'][$curr] ?? 0.0, 2)
                    ]);
                }
                fputcsv($file, []);

                fputcsv($file, ['Transaction/Invoice Number', 'Customer Name', 'Service', 'Payment Method', 'Currency', 'Amount Paid', 'Outstanding Balance', 'Payment Status', 'Date Paid', 'Recorded By']);

                foreach ($payments as $p) {
                    $customerName = $p->serviceRequest && $p->serviceRequest->client 
                        ? ($p->serviceRequest->client->organization ?? $p->serviceRequest->client->contact_person) 
                        : ($p->client ? $p->client->name : 'N/A');
                    $serviceLabel = $p->serviceRequest ? $p->serviceRequest->service_category : 'N/A';
                    
                    // Outstanding computation for row
                    $outstanding = $p->quotation 
                        ? max(0.0, (float)($p->quotation->amount - ($p->quotation->payments()->where('status', 'verified')->sum('amount_paid') ?? 0))) 
                        : 0.0;

                    fputcsv($file, [
                        $p->quotation ? $p->quotation->reference_number : ('PAY-' . $p->id),
                        $customerName,
                        ucwords(str_replace('_', ' ', $serviceLabel)),
                        $p->bank_used ?? 'N/A',
                        $p->quotation_currency ?? 'N/A',
                        $p->amount_paid,
                        $outstanding,
                        ucfirst($p->status),
                        $p->created_at->format('Y-m-d H:i'),
                        $p->verifiedBy ? $p->verifiedBy->name : 'Pending'
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    private function getShortCoursesRevenueData(Request $request)
    {
        $filters = $request->only([
            'preset', 'date_start', 'date_end', 'course_id', 'student_id', 'payment_status', 'payment_method', 'search'
        ]);

        [$dateStart, $dateEnd] = $this->resolveDateRange(
            $filters['preset'] ?? null,
            $filters['date_start'] ?? null,
            $filters['date_end'] ?? null
        );

        // Build query
        $query = CourseEnrollment::with(['user', 'intake.course.department']);

        if ($dateStart) {
            $query->where('course_enrollments.created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('course_enrollments.created_at', '<=', $dateEnd);
        }
        if (!empty($filters['course_id'])) {
            $courseId = $filters['course_id'];
            $query->whereHas('intake', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }
        if (!empty($filters['student_id'])) {
            $query->where('user_id', $filters['student_id']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['payment_method'])) {
            if ($filters['payment_method'] === 'Bank Proof Upload') {
                $query->whereNotNull('payment_proof_path');
            } else {
                $query->whereNull('payment_proof_path');
            }
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('certificate_code', 'like', $search)
                  ->orWhere('course_enrollments.id', 'like', $search)
                  ->orWhereHas('user', function($sq) use ($search) {
                      $sq->where('name', 'like', $search);
                  })
                  ->orWhereHas('intake.course', function($sq) use ($search) {
                      $sq->where('title', 'like', $search);
                  });
            });
        }

        return [$query, $dateStart, $dateEnd, $filters];
    }

    private function calculateShortCoursesRevenueAggregates($query, $dateStart, $dateEnd, $filters)
    {
        $cacheKey = 'sc_rev_agg_' . md5(serialize([$filters, $dateStart ? $dateStart->toDateTimeString() : null, $dateEnd ? $dateEnd->toDateTimeString() : null]));

        return Cache::remember($cacheKey, 60, function() use ($query, $dateStart, $dateEnd, $filters) {
            $totalRevenue = (float) (clone $query)->where('payment_status', 'verified')->sum('amount_paid');
            $totalTransactions = (clone $query)->count();
            $verifiedCount = (clone $query)->where('payment_status', 'verified')->count();
            $avgRevenue = $verifiedCount > 0 ? (float) ($totalRevenue / $verifiedCount) : 0.0;

            // Growth calculation
            $diffDays = 30;
            if ($dateStart && $dateEnd) {
                $diffDays = max(1, $dateStart->diffInDays($dateEnd));
                $prevStart = $dateStart->copy()->subDays($diffDays + 1)->startOfDay();
                $prevEnd = $dateStart->copy()->subDay()->endOfDay();
            } else {
                $prevStart = Carbon::now()->subDays(60)->startOfDay();
                $prevEnd = Carbon::now()->subDays(31)->endOfDay();
                $dateStart = Carbon::now()->subDays(30)->startOfDay();
                $dateEnd = Carbon::now()->endOfDay();
            }

            $growthQuery = CourseEnrollment::query();
            if (!empty($filters['course_id'])) {
                $courseId = $filters['course_id'];
                $growthQuery->whereHas('intake', function($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            }
            if (!empty($filters['student_id'])) {
                $growthQuery->where('user_id', $filters['student_id']);
            }
            if (!empty($filters['payment_status'])) {
                $growthQuery->where('payment_status', $filters['payment_status']);
            }

            $currentRevenue = (float) (clone $growthQuery)
                ->where('payment_status', 'verified')
                ->where('created_at', '>=', $dateStart)
                ->where('created_at', '<=', $dateEnd)
                ->sum('amount_paid');

            $prevRevenue = (float) (clone $growthQuery)
                ->where('payment_status', 'verified')
                ->where('created_at', '>=', $prevStart)
                ->where('created_at', '<=', $prevEnd)
                ->sum('amount_paid');

            $revenueGrowth = 0.0;
            if ($prevRevenue > 0) {
                $revenueGrowth = (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
            } elseif ($currentRevenue > 0) {
                $revenueGrowth = 100.0;
            }

            // Outstanding Payments sum(courses.price - course_enrollments.amount_paid)
            $outstandingPayments = (float) (clone $query)
                ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                ->join('courses', 'course_intakes.course_id', '=', 'courses.id')
                ->selectRaw('SUM(CASE WHEN courses.price > course_enrollments.amount_paid THEN courses.price - course_enrollments.amount_paid ELSE 0 END) as total')
                ->value('total') ?? 0.0;

            // Visualizations
            $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
            $dateFormat = $isSqlite ? "strftime('%Y-%m-%d', course_enrollments.created_at)" : "DATE_FORMAT(course_enrollments.created_at, '%Y-%m-%d')";
            $monthFormat = $isSqlite ? "strftime('%Y-%m', course_enrollments.created_at)" : "DATE_FORMAT(course_enrollments.created_at, '%Y-%m')";

            // 1. Revenue Trend (Line Chart)
            $trend = (clone $query)->where('payment_status', 'verified')
                ->selectRaw("{$dateFormat} as date_group, SUM(amount_paid) as total")
                ->groupBy('date_group')
                ->orderBy('date_group')
                ->get()
                ->map(fn($r) => ['date' => $r->date_group, 'total' => (float)$r->total])
                ->toArray();

            // 2. Revenue by Month (Bar Chart)
            $byMonth = (clone $query)->where('payment_status', 'verified')
                ->selectRaw("{$monthFormat} as month_group, SUM(amount_paid) as total")
                ->groupBy('month_group')
                ->orderBy('month_group')
                ->get()
                ->map(fn($r) => ['month' => Carbon::parse($r->month_group . '-01')->format('M Y'), 'total' => (float)$r->total])
                ->toArray();

            // 3. Revenue by Course Category (Bar Chart)
            $byCourseCategory = (clone $query)->where('payment_status', 'verified')
                ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                ->join('courses', 'course_intakes.course_id', '=', 'courses.id')
                ->selectRaw('courses.category, SUM(course_enrollments.amount_paid) as total')
                ->groupBy('courses.category')
                ->get()
                ->map(fn($r) => [
                    'category' => $r->category,
                    'total' => (float)$r->total
                ])
                ->toArray();

            // 4. Payment Method Distribution (Pie Chart)
            $byMethod = (clone $query)->where('payment_status', 'verified')
                ->selectRaw("CASE WHEN payment_proof_path IS NOT NULL THEN 'Bank Proof Upload' ELSE 'Manual Admin Entry' END as method, SUM(amount_paid) as total, COUNT(*) as count")
                ->groupBy('method')
                ->get()
                ->map(fn($r) => [
                    'method' => $r->method,
                    'total' => (float)$r->total,
                    'count' => $r->count
                ])
                ->toArray();

            // 5. Revenue by Status (Pie Chart)
            $byStatus = (clone $query)
                ->selectRaw('payment_status as status, SUM(amount_paid) as total, COUNT(*) as count')
                ->groupBy('payment_status')
                ->get()
                ->map(fn($r) => [
                    'status' => ucwords($r->status),
                    'total' => (float)$r->total,
                    'count' => $r->count
                ])
                ->toArray();

            // 6. Top Courses (Horizontal Bar Chart)
            $topCourses = (clone $query)->where('payment_status', 'verified')
                ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                ->join('courses', 'course_intakes.course_id', '=', 'courses.id')
                ->selectRaw('courses.title, SUM(course_enrollments.amount_paid) as total')
                ->groupBy('courses.title')
                ->orderBy('total', 'desc')
                ->take(5)
                ->get()
                ->map(fn($r) => [
                    'title' => $r->title,
                    'total' => (float)$r->total
                ])
                ->toArray();

            return [
                'kpis' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'average_revenue' => $avgRevenue,
                    'revenue_growth' => $revenueGrowth,
                    'outstanding_payments' => $outstandingPayments,
                ],
                'charts' => [
                    'trend' => $trend,
                    'by_month' => $byMonth,
                    'by_service' => $byCourseCategory,
                    'by_method' => $byMethod,
                    'by_status' => $byStatus,
                    'top_services' => $topCourses,
                ]
            ];
        });
    }

    public function shortCoursesRevenue(Request $request)
    {
        $this->checkReportAccess();

        [$query, $dateStart, $dateEnd, $filters] = $this->getShortCoursesRevenueData($request);

        $aggregates = $this->calculateShortCoursesRevenueAggregates($query, $dateStart, $dateEnd, $filters);

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_desc', 'true') === 'true' ? 'desc' : 'asc';

        if (in_array($sortField, ['id', 'amount_paid', 'payment_status', 'created_at'])) {
            $query->orderBy('course_enrollments.' . $sortField, $sortDirection);
        } else {
            $query->orderBy('course_enrollments.created_at', 'desc');
        }

        $enrollments = $query->paginate(10)->withQueryString();

        // Dropdowns for filters
        $courses = Course::select('id', 'title', 'code')->orderBy('title')->get();
        $students = User::where('primary_category', 'Student')->select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Reports/ShortCoursesRevenue', [
            'enrollments' => $enrollments,
            'kpis' => $aggregates['kpis'],
            'charts' => $aggregates['charts'],
            'filters' => $filters,
            'filterOptions' => [
                'courses' => $courses,
                'students' => $students,
                'payment_methods' => ['Bank Proof Upload', 'Manual Admin Entry']
            ]
        ]);
    }

    public function exportShortCoursesRevenue(Request $request)
    {
        $this->checkReportAccess();

        $format = $request->input('format', 'csv');

        [$query, $dateStart, $dateEnd, $filters] = $this->getShortCoursesRevenueData($request);
        $enrollments = $query->get();

        $aggregates = $this->calculateShortCoursesRevenueAggregates($query, $dateStart, $dateEnd, $filters);
        $kpis = $aggregates['kpis'];

        if ($format === 'pdf') {
            $data = [
                'title' => 'Short Courses Revenue Report',
                'enrollments' => $enrollments,
                'kpis' => $kpis,
                'filters' => $filters,
                'generated_by' => Auth::user()->name,
                'generated_date' => now()->format('F d, Y h:i A'),
                'type' => 'short_courses'
            ];

            $pdf = Pdf::loadView('reports.revenue_pdf', $data);
            return $pdf->download('short_courses_revenue_report_' . time() . '.pdf');
        } else {
            // Excel/CSV
            $filename = 'short_courses_revenue_' . time() . '.' . ($format === 'excel' ? 'xlsx' : 'csv');
            $contentType = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';
            
            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($enrollments, $kpis) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['MSU National Language Institute - Short Courses Revenue Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);

                fputcsv($file, ['KPI Summary']);
                fputcsv($file, ['Total Revenue', $kpis['total_revenue']]);
                fputcsv($file, ['Total Transactions', $kpis['total_transactions']]);
                fputcsv($file, ['Average Revenue per Transaction', $kpis['average_revenue']]);
                fputcsv($file, ['Revenue Growth (%)', $kpis['revenue_growth'] . '%']);
                fputcsv($file, ['Outstanding Payments', $kpis['outstanding_payments']]);
                fputcsv($file, []);

                fputcsv($file, ['Transaction/Invoice Number', 'Student Name', 'Course', 'Payment Method', 'Amount Paid', 'Outstanding Balance', 'Payment Status', 'Date Paid', 'Recorded By']);

                foreach ($enrollments as $e) {
                    $method = $e->payment_proof_path ? 'Bank Proof Upload' : 'Manual Admin Entry';
                    $courseTitle = $e->intake && $e->intake->course ? $e->intake->course->title : 'N/A';
                    $coursePrice = $e->intake && $e->intake->course ? $e->intake->course->price : 0;
                    
                    $outstanding = max(0.0, (float)($coursePrice - $e->amount_paid));

                    fputcsv($file, [
                        'ENR-' . $e->id,
                        $e->user ? $e->user->name : 'N/A',
                        $courseTitle,
                        $method,
                        $e->amount_paid,
                        $outstanding,
                        ucfirst($e->payment_status),
                        $e->created_at->format('Y-m-d H:i'),
                        $e->payment_status === 'verified' ? 'Admin Assistant' : 'Pending'
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    /**
     * Helper to query and aggregate metrics for exported documents with multi-type support.
     */
    private function aggregateReportData($filters = [], $reportType = 'client_services')
    {
        $user = Auth::user();
        $isManagement = $user->hasAnyRole(['executive_director', 'deputy_director', 'admin_assistant', 'secretary', 'ict_administrator']);

        $query = ServiceRequest::with(['client', 'assignedTo']);
        $taskQuery = Task::query();

        // Enforce RBAC Scopes
        if (!$isManagement) {
            $query->where('assigned_to', $user->id);
            $taskQuery->whereHas('assignment', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        } else {
            if (!empty($filters['department_id'])) {
                $deptId = $filters['department_id'];
                $query->where('department_id', $deptId);
                $taskQuery->whereHas('assignment.serviceRequest', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
            if (!empty($filters['assigned_to'])) {
                $staffId = $filters['assigned_to'];
                $query->where('assigned_to', $staffId);
                $taskQuery->whereHas('assignment', function ($q) use ($staffId) {
                    $q->where('assigned_to', $staffId);
                });
            }
        }

        // Apply time thresholds
        if (!empty($filters['date_start'])) {
            $query->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            $taskQuery->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
        }
        if (!empty($filters['date_end'])) {
            $query->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            $taskQuery->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
        }
        if (!empty($filters['service_category'])) {
            $cat = $filters['service_category'];
            $query->where('service_category', $cat);
            $taskQuery->whereHas('assignment.serviceRequest', function ($q) use ($cat) {
                $q->where('service_category', $cat);
            });
        }
        // Apply Service Request Status Filter directly to the main query
        if (!empty($filters['task_status'])) {
            $statusFilter = $filters['task_status'];
            $now = Carbon::now()->toDateString();
            
            if ($statusFilter === 'completed') {
                $query->where('status', 'completed');
            } elseif ($statusFilter === 'in_progress') {
                $query->whereIn('status', ['in_progress', 'review'])
                      ->where(function($q) use ($now) {
                          $q->whereNull('deadline')->orWhere('deadline', '>=', $now);
                      });
            } elseif ($statusFilter === 'pending') {
                $query->whereIn('status', ['pending', 'quoted', 'approved'])
                      ->where(function($q) use ($now) {
                          $q->whereNull('deadline')->orWhere('deadline', '>=', $now);
                      });
            } elseif ($statusFilter === 'overdue') {
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      ->whereNotNull('deadline')
                      ->where('deadline', '<', $now);
            }
        }

        // Totals
        $totalRequests = (clone $query)->count();
        $completedServices = (clone $query)->where('status', 'completed')->count();
        
        $activeAssignments = Assignment::whereNotIn('status', ['completed'])
            ->whereHas('serviceRequest', function ($q) use ($filters, $isManagement, $user) {
                if (!$isManagement) {
                    $q->where('assigned_to', $user->id);
                } else {
                    if (!empty($filters['assigned_to'])) {
                        $q->where('assigned_to', $filters['assigned_to']);
                    }
                }
                if (!empty($filters['service_category'])) {
                    $q->where('service_category', $filters['service_category']);
                }
            })->count();
            
        if ($isManagement) {
            $pendingApprovals = Approval::where('status', 'pending')->count();
        } else {
            $pendingApprovals = Approval::where('status', 'pending')
                ->where('approver_id', $user->id)->count();
        }

        $completionRate = ($totalRequests > 0) ? round(($completedServices / $totalRequests) * 100, 1) : 100.0;
        $kpiPercentage = $completionRate;

        // Turnaround Time
        $completedRequests = (clone $query)->where('status', 'completed')->whereNotNull('completed_at')->get();
        $turnaroundDays = [];
        foreach ($completedRequests as $r) {
            $turnaroundDays[] = Carbon::parse($r->created_at)->diffInDays(Carbon::parse($r->completed_at));
        }
        $avgTurnaroundTime = count($turnaroundDays) > 0 ? round(array_sum($turnaroundDays) / count($turnaroundDays), 1) : 0.0;

        // Satisfaction Score
        $avgSatisfaction = ServiceRequest::whereNotNull('rating');
        if (!$isManagement) {
            $avgSatisfaction->where('assigned_to', $user->id);
        }
        $avgSatisfactionScore = $avgSatisfaction->avg('rating');
        $avgSatisfactionScore = ($avgSatisfactionScore && $avgSatisfactionScore > 0) ? round($avgSatisfactionScore * 20, 1) : 94.0;

        // --- Core Multi-Type Data Fetch Pipelines ---
        $requests = [];
        $quotations = [];
        $tasks = [];
        $staffWorkload = [];
        $kpis = [];
        $studentEnrollments = [];
        $enrollmentStats = [
            'total' => 0,
            'active' => 0,
            'pending' => 0,
            'completed' => 0,
            'dropped' => 0,
        ];

        // 1. Client Service reports always fetch matching requests
        if ($reportType === 'client_services' || $reportType === 'administrative') {
            $requests = $query->orderBy('created_at', 'desc')->take(50)->get();
        }

        // 2. Quotation reports fetch quotation ledger details with client parameters
        if ($reportType === 'quotations' || $reportType === 'administrative') {
            $quoteQuery = Quotation::with(['serviceRequest.client', 'preparedBy']);
            
            // Scope role-based quotation visibility
            if (!$isManagement) {
                $quoteQuery->where(function($q) use ($user) {
                    $q->where('prepared_by', $user->id)
                      ->orWhereHas('serviceRequest', function($sq) use ($user) {
                          $sq->where('assigned_to', $user->id);
                      });
                });
            }

            // Apply active date range queries
            if (!empty($filters['date_start'])) {
                $quoteQuery->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            }
            if (!empty($filters['date_end'])) {
                $quoteQuery->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            }
            $quotations = $quoteQuery->orderBy('created_at', 'desc')->take(50)->get();
        }

        // 3. Workflow Progress Report queries direct Task structures
        if ($reportType === 'workflow_progress' || $reportType === 'administrative') {
            $tasksListQuery = Task::with(['assignment.assignedTo', 'assignment.serviceRequest.client']);
            if (!$isManagement) {
                $tasksListQuery->whereHas('assignment', function ($q) use ($user) {
                    $q->where('assigned_to', $user->id);
                });
            }
            if (!empty($filters['date_start'])) {
                $tasksListQuery->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            }
            if (!empty($filters['date_end'])) {
                $tasksListQuery->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            }
            if (!empty($filters['task_status'])) {
                $tasksListQuery->where('status', $filters['task_status']);
            }
            $tasks = $tasksListQuery->orderBy('due_date', 'asc')->take(50)->get();
        }

        // 4. Staff Workload Report calculates user allocations and capacity scores
        if ($reportType === 'staff_workload' || $reportType === 'administrative') {
            $staffUsers = User::where('primary_category', 'Staff')->get();
            
            foreach ($staffUsers as $s) {
                if (!$isManagement && $s->id !== $user->id) {
                    continue;
                }

                $tasksDone = Task::where('status', 'completed')
                    ->whereHas('assignment', function($q) use ($s) {
                        $q->where('assigned_to', $s->id);
                    })->count();
                
                $reqsDone = ServiceRequest::where('status', 'completed')
                    ->where('assigned_to', $s->id)->count();

                if ($tasksDone > 0 || $reqsDone > 0) {
                    $staffWorkload[] = [
                        'id' => $s->id,
                        'name' => $s->name,
                        'department' => $s->department ? $s->department->name : 'General',
                        'tasks_completed' => $tasksDone,
                        'requests_completed' => $reqsDone,
                        'score' => ($tasksDone * 10) + ($reqsDone * 25),
                    ];
                }
            }
            usort($staffWorkload, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        // 5. KPI Performance metrics queries satisfaction feedback logs
        if ($reportType === 'kpi_performance' || $reportType === 'administrative') {
            $kpiQuery = KpiRecord::with('user');
            if (!$isManagement) {
                $kpiQuery->where('user_id', $user->id);
            }
            if (!empty($filters['date_start'])) {
                $kpiQuery->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            }
            if (!empty($filters['date_end'])) {
                $kpiQuery->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            }
            $kpis = $kpiQuery->orderBy('created_at', 'desc')->take(50)->get();
        }

        // 6. Student Enrollment Report type pipeline
        if ($reportType === 'student_enrollment') {
            $seQuery = \App\Models\CourseEnrollment::whereHas('user', function($q) {
                $q->where('primary_category', 'Student');
            })->with(['user', 'intake.course.department']);
            if (!empty($filters['student_course_id'])) {
                $seQuery->whereHas('intake.course', function($q) use ($filters) {
                    $q->where('id', $filters['student_course_id']);
                });
            }
            if (!empty($filters['student_unit_id'])) {
                $seQuery->whereHas('intake.course.department', function($q) use ($filters) {
                    $q->where('id', $filters['student_unit_id']);
                });
            }
            if (!empty($filters['student_enrollment_status'])) {
                $seQuery->where('enrollment_status', $filters['student_enrollment_status']);
            }
            if (!empty($filters['date_start'])) {
                $seQuery->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            }
            if (!empty($filters['date_end'])) {
                $seQuery->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            }
            $studentEnrollments = $seQuery->orderBy('created_at', 'desc')->get();

            // Calculate stats for metrics
            $seBase = \App\Models\CourseEnrollment::whereHas('user', function($q) {
                $q->where('primary_category', 'Student');
            });
            if (!empty($filters['student_course_id'])) {
                $seBase->whereHas('intake.course', function($q) use ($filters) {
                    $q->where('id', $filters['student_course_id']);
                });
            }
            if (!empty($filters['student_unit_id'])) {
                $seBase->whereHas('intake.course.department', function($q) use ($filters) {
                    $q->where('id', $filters['student_unit_id']);
                });
            }
            if (!empty($filters['date_start'])) {
                $seBase->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
            }
            if (!empty($filters['date_end'])) {
                $seBase->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
            }

            $enrollmentStats = [
                'total' => (clone $seBase)->count(),
                'active' => (clone $seBase)->where('enrollment_status', 'active')->count(),
                'pending' => (clone $seBase)->where('enrollment_status', 'pending')->count(),
                'completed' => (clone $seBase)->where('enrollment_status', 'completed')->count(),
                'dropped' => (clone $seBase)->where('enrollment_status', 'dropped')->count(),
            ];
        }

        return [
            'requests' => $requests,
            'quotations' => $quotations,
            'tasks' => $tasks,
            'staff_workload' => $staffWorkload,
            'kpis' => $kpis,
            'student_enrollments' => $studentEnrollments,
            'enrollment_stats' => $enrollmentStats,
            'totals' => [
                'total_requests' => $totalRequests,
                'active_assignments' => $activeAssignments,
                'completed_services' => $completedServices,
                'pending_approvals' => $pendingApprovals,
                'kpi_performance' => $kpiPercentage,
                'avg_turnaround' => $avgTurnaroundTime,
                'client_satisfaction' => $avgSatisfactionScore,
            ],
            'filters' => $filters,
        ];
    }
}
