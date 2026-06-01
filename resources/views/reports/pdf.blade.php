<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a202c;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            line-height: 1.4;
        }
        .clear {
            clear: both;
        }
        .report-title-bar {
            margin-top: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #f5c242;
            padding-left: 12px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: 800;
            color: #0a1f44;
            text-transform: uppercase;
            margin: 0;
        }
        .report-desc {
            font-size: 8.5pt;
            color: #718096;
            margin: 2px 0 0 0;
            font-weight: 500;
        }
        /* Grid widgets */
        .widget-row {
            margin-bottom: 25px;
            width: 100%;
        }
        .widget {
            float: left;
            width: 18%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            margin-right: 1.5%;
            text-align: center;
        }
        .widget:last-child {
            margin-right: 0;
        }
        .widget-title {
            font-size: 7pt;
            font-weight: bold;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .widget-value {
            font-size: 12.5pt;
            font-weight: 800;
            color: #0a1f44;
        }
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 8.5pt;
        }
        th {
            background-color: #0a1f44;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 7px 9px;
            border: 1px solid #0a1f44;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.3px;
        }
        td {
            padding: 7px 9px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-pending {
            background-color: #ebf8ff;
            color: #2b6cb0;
            border: 1px solid #bee3f8;
        }
        .badge-completed {
            background-color: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        .badge-in-progress {
            background-color: #fffaf0;
            color: #dd6b20;
            border: 1px solid #feebc8;
        }
        .badge-urgent {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5pt;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .section-header {
            font-size: 11pt;
            font-weight: 800;
            color: #0a1f44;
            margin-top: 25px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <table style="width: 100%; border-bottom: 3px solid #0a1f44; padding-bottom: 12px; margin-bottom: 20px; border-collapse: collapse; border: none; background: transparent;">
        <tr style="border: none; background: transparent;">
            <!-- Column 1: Logo Image -->
            <td style="width: 50px; border: none; padding: 0; vertical-align: middle; background: transparent; text-align: left;">
                <img src="{{ public_path('msu-logo-2.png') }}" style="width: 45px; height: 45px; border: none; display: block;" />
            </td>
            <!-- Column 2: MSUNLI Title & Subtitle -->
            <td style="border: none; padding: 0 0 0 10px; vertical-align: middle; background: transparent; text-align: left;">
                <h1 style="font-size: 20pt; font-weight: 800; color: #0a1f44; margin: 0; line-height: 1.1; letter-spacing: 0.5px;">MSUNLI</h1>
                <p style="font-size: 8.5pt; color: #4a5568; margin: 2px 0 0 0; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Midlands State University National Language Institute</p>
            </td>
            <!-- Column 3: Generated Metadata -->
            <td style="width: 35%; border: none; padding: 0; text-align: right; vertical-align: middle; font-size: 8.5pt; color: #718096; background: transparent; line-height: 1.5;">
                <p style="margin: 2px 0;"><strong>Generated By:</strong> {{ $generated_by }}</p>
                <p style="margin: 2px 0;"><strong>Date Compiled:</strong> {{ $generated_date }}</p>
                <p style="margin: 2px 0;"><strong>Status:</strong> Official Management Printout</p>
            </td>
        </tr>
    </table>

    <div class="report-title-bar">
        <h2 class="report-title">{{ $title }}</h2>
        <p class="report-desc">Category Scope: {{ ucwords(str_replace('_', ' ', $report_type)) }} Performance Ledger</p>
    </div>

    <!-- 6 KPI widgets display -->
    <div class="widget-row">
        @if ($report_type === 'student_enrollment')
            <div class="widget" style="width: 18%;">
                <div class="widget-title">Total Enrolled</div>
                <div class="widget-value">{{ $enrollment_stats['total'] }}</div>
            </div>
            <div class="widget" style="width: 18%;">
                <div class="widget-title font-black" style="color:#2b6cb0;">Active</div>
                <div class="widget-value">{{ $enrollment_stats['active'] }}</div>
            </div>
            <div class="widget" style="width: 18%;">
                <div class="widget-title font-black" style="color:#dd6b20;">Pending</div>
                <div class="widget-value">{{ $enrollment_stats['pending'] }}</div>
            </div>
            <div class="widget" style="width: 18%;">
                <div class="widget-title font-black" style="color:#22543d;">Completed</div>
                <div class="widget-value">{{ $enrollment_stats['completed'] }}</div>
            </div>
            <div class="widget" style="width: 18%;">
                <div class="widget-title font-black" style="color:#e53e3e;">Dropped</div>
                <div class="widget-value">{{ $enrollment_stats['dropped'] }}</div>
            </div>
        @else
            <div class="widget">
                <div class="widget-title">Total Volume</div>
                <div class="widget-value">{{ $totals['total_requests'] }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Active Tasks</div>
                <div class="widget-value">{{ $totals['active_assignments'] }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Completed</div>
                <div class="widget-value">{{ $totals['completed_services'] }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Avg Speed</div>
                <div class="widget-value">{{ $totals['avg_turnaround'] }}d</div>
            </div>
            <div class="widget">
                <div class="widget-title font-black" style="color:#d4a017;">Satisfaction</div>
                <div class="widget-value">{{ $totals['client_satisfaction'] }}/5.0</div>
            </div>
        @endif
        <div class="clear"></div>
    </div>

    <!-- Dynamic specialized content injection -->
    @if ($report_type === 'quotations')
        <h3 class="section-header">Financial Quotations & Billing Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Prepared By</th>
                    <th>Billing Description</th>
                    <th>Currency</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotations as $quote)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $quote->reference_number }}</td>
                        <td>{{ $quote->preparedBy ? $quote->preparedBy->name : 'System Generated' }}</td>
                        <td>{{ str_limit($quote->description, 35) }}</td>
                        <td>{{ $quote->currency }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($quote->amount, 2) }}</td>
                        <td>{{ $quote->valid_until ? $quote->valid_until->format('Y-m-d') : 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $quote->status === 'approved' ? 'badge-completed' : ($quote->status === 'pending' ? 'badge-in-progress' : 'badge-pending') }}">
                                {{ $quote->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #a0aec0; padding: 15px;">No quotation logs recorded inside this time scope.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'workflow_progress')
        <h3 class="section-header">Workflow Assignment & Tasks Status Registry</h3>
        <table>
            <thead>
                <tr>
                    <th>Task Description</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Specialist Assigned</th>
                    <th>Status</th>
                    <th>Completed At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $task->title }}</td>
                        <td>
                            <span class="badge {{ $task->priority === 'high' || $task->priority === 'urgent' ? 'badge-urgent' : 'badge-pending' }}">
                                {{ $task->priority }}
                            </span>
                        </td>
                        <td>{{ $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ ($task->assignment && $task->assignment->assignedTo) ? $task->assignment->assignedTo->name : 'Unassigned' }}</td>
                        <td>
                            <span class="badge {{ $task->status === 'completed' ? 'badge-completed' : ($task->status === 'in_progress' ? 'badge-in-progress' : 'badge-pending') }}">
                                {{ str_replace('_', ' ', $task->status) }}
                            </span>
                        </td>
                        <td>{{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : 'Pending' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #a0aec0; padding: 15px;">No tasks match this operational threshold.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'staff_workload')
        <h3 class="section-header">Specialist Capacity & Workload Balance</h3>
        <table>
            <thead>
                <tr>
                    <th>Specialist Name</th>
                    <th>Department Location</th>
                    <th style="text-align: center;">Tasks Done</th>
                    <th style="text-align: center;">Requests Handled</th>
                    <th style="text-align: right;">Capacity Score</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff_workload as $sw)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $sw['name'] }}</td>
                        <td>{{ $sw['department'] }}</td>
                        <td style="text-align: center;">{{ $sw['tasks_completed'] }}</td>
                        <td style="text-align: center;">{{ $sw['requests_completed'] }}</td>
                        <td style="text-align: right; font-weight: bold; color: #d4a017;">{{ $sw['score'] }} pts</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #a0aec0; padding: 15px;">No workload records gathered for this filter set.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'kpi_performance')
        <h3 class="section-header">KPI Performance Scorecard Indices</h3>
        <table>
            <thead>
                <tr>
                    <th>Metric Type</th>
                    <th>Evaluated Specialist</th>
                    <th>Metric Index Value</th>
                    <th>Audit Recorded Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kpis as $kpi)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ ucwords(str_replace('_', ' ', $kpi->metric_type)) }}</td>
                        <td>{{ $kpi->user ? $kpi->user->name : 'MSULI General Specialist' }}</td>
                        <td style="font-weight: bold; color: #2b6cb0;">{{ $kpi->metric_value }}</td>
                        <td>{{ $kpi->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #a0aec0; padding: 15px;">No specific KPI feedback scores logged inside filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'administrative')
        <h3 class="section-header">Executive Administrative Services Overview</h3>
        <table>
            <thead>
                <tr>
                    <th>Ref Number</th>
                    <th>Client Organization</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $req->reference_number }}</td>
                        <td>{{ $req->client ? ($req->client->organization ?? $req->client->contact_person) : 'N/A' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $req->service_category)) }}</td>
                        <td>
                            <span class="badge {{ $req->priority === 'urgent' || $req->priority === 'high' ? 'badge-urgent' : 'badge-pending' }}">
                                {{ $req->priority }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $req->status === 'completed' ? 'badge-completed' : ($req->status === 'in_progress' ? 'badge-in-progress' : 'badge-pending') }}">
                                {{ str_replace('_', ' ', $req->status) }}
                            </span>
                        </td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #a0aec0; padding: 15px;">No active service requests matching database.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'student_enrollment')
        <h3 class="section-header">Short Courses Student Enrollment Ledger</h3>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email Address</th>
                    <th>Enrolled Course</th>
                    <th>MSUNLI Unit</th>
                    <th style="text-align: right;">Tuition Paid</th>
                    <th>Payment Status</th>
                    <th>Enrollment Status</th>
                    <th>Enrolled Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($student_enrollments as $se)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $se->user ? $se->user->name : 'N/A' }}</td>
                        <td>{{ $se->user ? $se->user->email : 'N/A' }}</td>
                        <td>{{ $se->intake && $se->intake->course ? $se->intake->course->title : 'N/A' }}</td>
                        <td>{{ ($se->intake && $se->intake->course && $se->intake->course->department) ? $se->intake->course->department->code : 'N/A' }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($se->amount_paid, 2) }}</td>
                        <td>
                            <span class="badge {{ $se->payment_status === 'verified' ? 'badge-completed' : ($se->payment_status === 'pending' ? 'badge-in-progress' : 'badge-pending') }}">
                                {{ $se->payment_status }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $se->enrollment_status === 'active' ? 'badge-completed' : ($se->enrollment_status === 'completed' ? 'badge-completed' : ($se->enrollment_status === 'pending' ? 'badge-in-progress' : 'badge-pending')) }}">
                                {{ $se->enrollment_status }}
                            </span>
                        </td>
                        <td>{{ $se->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #a0aec0; padding: 15px;">No student enrollments match these filters inside this time scope.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @else
        <h3 class="section-header">Institutional Client Language Services Log</h3>
        <table>
            <thead>
                <tr>
                    <th>Ref Number</th>
                    <th>Client Organization</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $req->reference_number }}</td>
                        <td>{{ $req->client ? ($req->client->organization ?? $req->client->contact_person) : 'N/A' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $req->service_category)) }}</td>
                        <td>
                            <span class="badge {{ $req->priority === 'urgent' || $req->priority === 'high' ? 'badge-urgent' : 'badge-pending' }}">
                                {{ $req->priority }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $req->status === 'completed' ? 'badge-completed' : ($req->status === 'in_progress' ? 'badge-in-progress' : 'badge-pending') }}">
                                {{ str_replace('_', ' ', $req->status) }}
                            </span>
                        </td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #a0aec0; padding: 15px;">No active service requests match current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        MSUNLI Management & Reporting Audit System &bull; Midlands State University &bull; Confidential
    </div>
</body>
</html>
