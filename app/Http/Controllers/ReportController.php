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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
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
                $query->whereHas('assignedTo', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
                $taskQuery->whereHas('assignment.assignedTo', function ($q) use ($deptId) {
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
        $staff = User::whereHas('roles', function($q) {
            $q->whereNotIn('name', ['client']);
        })->get();
        
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
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $yearMonth = $date->format('Y-m');
            $monthName = $date->format('M Y');
            
            $volQ = ServiceRequest::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$yearMonth]);
            $compQ = ServiceRequest::where('status', 'completed')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$yearMonth]);

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

        // Fetch supporting filter options
        $filterOptions = [
            'departments' => Department::select('id', 'name', 'code')->get(),
            'staff' => User::whereHas('roles', function($q) {
                $q->whereNotIn('name', ['client']);
            })->select('id', 'name', 'email')->get(),
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
            ],
            'staffProductivity' => $staffProductivity,
            'reports' => $reports,
            'filterOptions' => $filterOptions,
            'currentFilters' => $request->only(['date_start', 'date_end', 'department_id', 'service_category', 'assigned_to', 'task_status', 'search', 'archive_type']),
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

            fputcsv($csvStream, ['KEY PERFORMANCE METRICS']);
            fputcsv($csvStream, ['Total Volumetric requests', $data['totals']['total_requests']]);
            fputcsv($csvStream, ['Active Assignments', $data['totals']['active_assignments']]);
            fputcsv($csvStream, ['Completed Operations', $data['totals']['completed_services']]);
            fputcsv($csvStream, ['Avg Turnaround Speed (Days)', $data['totals']['avg_turnaround']]);
            fputcsv($csvStream, ['Customer Satisfaction Index', $data['totals']['client_satisfaction']]);
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
                        $kpi->user ? $kpi->user->name : 'General NLI',
                        $kpi->metric_value,
                        $kpi->created_at->format('Y-m-d H:i')
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
        $filters = $request->only(['date_start', 'date_end', 'department_id', 'service_category', 'assigned_to', 'task_status']);
        
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

                fputcsv($file, ['KPI METRIC SUMMARY']);
                fputcsv($file, ['Total Volumetric Requests', $data['totals']['total_requests']]);
                fputcsv($file, ['Active Assignments', $data['totals']['active_assignments']]);
                fputcsv($file, ['Completed Services', $data['totals']['completed_services']]);
                fputcsv($file, ['KPI Performance Score', $data['totals']['kpi_performance'] . '%']);
                fputcsv($file, ['Avg Turnaround Speed (Days)', $data['totals']['avg_turnaround']]);
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
                            $kpi->user ? $kpi->user->name : 'General NLI',
                            $kpi->metric_value,
                            $kpi->created_at->format('Y-m-d H:i')
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
                $query->whereHas('assignedTo', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
                $taskQuery->whereHas('assignment.assignedTo', function ($q) use ($deptId) {
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
            $staffUsers = User::whereHas('roles', function($q) {
                $q->whereNotIn('name', ['client']);
            })->get();
            
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

        return [
            'requests' => $requests,
            'quotations' => $quotations,
            'tasks' => $tasks,
            'staff_workload' => $staffWorkload,
            'kpis' => $kpis,
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
