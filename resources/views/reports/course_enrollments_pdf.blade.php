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
        .report-header {
            width: 100%;
            border-bottom: 3px solid #0a1f44;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .logo-cell {
            width: 50px;
            vertical-align: middle;
        }
        .logo-cell img {
            width: 45px;
            height: 45px;
            display: block;
        }
        .title-cell {
            vertical-align: middle;
            padding-left: 10px;
        }
        .title-cell h1 {
            font-size: 20pt;
            font-weight: 800;
            color: #0a1f44;
            margin: 0;
            line-height: 1.1;
        }
        .title-cell p {
            font-size: 8.5pt;
            color: #4a5568;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            font-weight: 600;
        }
        .metadata-cell {
            width: 35%;
            text-align: right;
            vertical-align: middle;
            font-size: 8.5pt;
            color: #718096;
            line-height: 1.5;
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
        }
        td {
            padding: 7px 9px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
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
        .badge-active {
            background-color: #e6fffa;
            color: #319795;
            border: 1px solid #b2f5ea;
        }
        .badge-completed {
            background-color: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
        }
        .badge-pending {
            background-color: #fffaf0;
            color: #dd6b20;
            border: 1px solid #feebc8;
        }
        .badge-dropped {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }
        .rating-excellent {
            color: #38a169;
            font-weight: bold;
        }
        .rating-good {
            color: #3182ce;
            font-weight: bold;
        }
        .rating-fair {
            color: #dd6b20;
            font-weight: bold;
        }
        .rating-needs-attention {
            color: #e53e3e;
            font-weight: bold;
        }
        .progress-bar-container {
            width: 100px;
            height: 10px;
            background-color: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 5px;
        }
        .progress-bar {
            height: 100%;
        }
        .progress-excellent { background-color: #48bb78; }
        .progress-good { background-color: #4299e1; }
        .progress-fair { background-color: #ed8936; }
        .progress-needs-attention { background-color: #f56565; }
        
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
        .course-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .course-header-table {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .course-header-table td {
            background-color: #edf2f7 !important;
            font-size: 9.5pt;
            font-weight: bold;
            color: #0a1f44;
            border: 1px solid #cbd5e0;
        }
    </style>
</head>
<body>
    <table class="report-header" style="width: 100%; border: none; background: transparent;">
        <tr style="border: none; background: transparent;">
            <td class="logo-cell" style="border: none; background: transparent;">
                <img src="{{ public_path('msu-logo-2.png') }}" />
            </td>
            <td class="title-cell" style="border: none; background: transparent;">
                <h1>MSUNLI</h1>
                <p>Midlands State University National Language Institute</p>
            </td>
            <td class="metadata-cell" style="border: none; background: transparent;">
                <p style="margin: 2px 0;"><strong>Generated By:</strong> {{ $generated_by }}</p>
                <p style="margin: 2px 0;"><strong>Date Compiled:</strong> {{ $generated_date }}</p>
                <p style="margin: 2px 0;"><strong>Format:</strong> PDF Report</p>
            </td>
        </tr>
    </table>

    <div class="report-title-bar">
        <h2 class="report-title">{{ $title }}</h2>
        <p class="report-desc">Scope: Short Course Academic Performance Ledger</p>
    </div>

    @if ($report_type === 'enrollment')
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Intake</th>
                    <th>Student Name</th>
                    <th>Reg Number</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($intakes as $intake)
                    @forelse ($intake['students'] as $student)
                        <tr>
                            <td>{{ $intake['course_code'] }}</td>
                            <td style="font-weight: bold; color: #0a1f44;">{{ $intake['course_name'] }}</td>
                            <td>{{ $intake['intake_name'] }}</td>
                            <td style="font-weight: bold;">{{ $student['name'] }}</td>
                            <td><code>{{ $student['reg_number'] }}</code></td>
                            <td>{{ $student['enrollment_date'] }}</td>
                            <td>
                                <span class="badge badge-{{ $student['status'] }}">
                                    {{ $student['status'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $student['payment_status'] === 'verified' ? 'active' : 'pending' }}">
                                    {{ $student['payment_status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <!-- No students inside this intake -->
                    @endforelse
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px; color: #a0aec0;">No enrollment data matches the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'instructor_allocation')
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Intake</th>
                    <th>Instructor Name</th>
                    <th>Role</th>
                    <th>Department / Unit</th>
                    <th>Schedule Range</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($intakes as $intake)
                    <tr>
                        <td>{{ $intake['course_code'] }}</td>
                        <td style="font-weight: bold; color: #0a1f44;">{{ $intake['course_name'] }}</td>
                        <td>{{ $intake['intake_name'] }}</td>
                        <td style="font-weight: bold;">{{ $intake['instructor_info']['name'] ?? 'Unassigned' }}</td>
                        <td>{{ $intake['instructor_info']['role'] ?? 'N/A' }}</td>
                        <td>{{ $intake['instructor_info']['unit'] ?? 'N/A' }}</td>
                        <td>{{ $intake['start_date'] }} to {{ $intake['end_date'] }}</td>
                        <td>
                            <span class="badge badge-{{ $intake['course_status'] === 'Completed' ? 'completed' : ($intake['course_status'] === 'Ongoing' ? 'active' : 'pending') }}">
                                {{ $intake['course_status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px; color: #a0aec0;">No instructor allocations match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'course_performance')
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name / Intake</th>
                    <th>Instructor</th>
                    <th style="text-align: center;">Enrolled</th>
                    <th style="text-align: center;">Active</th>
                    <th style="text-align: center;">Assessed</th>
                    <th style="text-align: center;">Passed</th>
                    <th style="text-align: center;">Failed</th>
                    <th style="text-align: right;">Pass Rate</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($intakes as $intake)
                    <tr>
                        <td>{{ $intake['course_code'] }}</td>
                        <td>
                            <div style="font-weight: bold; color: #0a1f44;">{{ $intake['course_name'] }}</div>
                            <div style="font-size: 7.5pt; color: #718096; margin-top: 1px;">Intake: {{ $intake['intake_name'] }}</div>
                        </td>
                        <td>{{ $intake['assigned_instructor'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $intake['total_enrolled'] }}</td>
                        <td style="text-align: center;">{{ $intake['active_students'] }}</td>
                        <td style="text-align: center;">{{ $intake['students_assessed'] }}</td>
                        <td style="text-align: center; color: #38a169;">{{ $intake['students_passed'] }}</td>
                        <td style="text-align: center; color: #e53e3e;">{{ $intake['students_failed'] }}</td>
                        <td style="text-align: right; font-weight: bold; color: #0a1f44;">{{ number_format($intake['pass_rate'], 2) }}%</td>
                        <td>
                            <span class="rating-{{ str_replace(' ', '-', strtolower($intake['performance_rating'])) }}">
                                {{ $intake['performance_rating'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px; color: #a0aec0;">No course performance data matches the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($report_type === 'pass_rate_analysis')
        @forelse ($intakes as $intake)
            <div class="course-section">
                <table class="course-header-table">
                    <tr>
                        <td>
                            Course: {{ $intake['course_name'] }} ({{ $intake['course_code'] }}) &bull; Intake: {{ $intake['intake_name'] }}
                        </td>
                    </tr>
                </table>

                <table style="margin-top: 0; margin-bottom: 10px; font-size: 8pt;">
                    <tr style="background-color: #f7fafc;">
                        <td style="width: 25%; font-weight: bold;">Assigned Instructor:</td>
                        <td style="width: 25%;">{{ $intake['assigned_instructor'] }}</td>
                        <td style="width: 25%; font-weight: bold;">Students Assessed:</td>
                        <td style="width: 25%;">{{ $intake['students_assessed'] }}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="font-weight: bold;">Passed Students:</td>
                        <td style="color: #38a169; font-weight: bold;">{{ $intake['students_passed'] }}</td>
                        <td style="font-weight: bold;">Failed Students:</td>
                        <td style="color: #e53e3e; font-weight: bold;">{{ $intake['students_failed'] }}</td>
                    </tr>
                    <tr style="background-color: #f7fafc;">
                        <td style="font-weight: bold;">Pass Rate:</td>
                        <td style="font-weight: bold; color: #0a1f44;">{{ number_format($intake['pass_rate'], 2) }}%</td>
                        <td style="font-weight: bold;">Performance Rating:</td>
                        <td>
                            <div class="progress-bar-container">
                                <div class="progress-bar progress-{{ str_replace(' ', '-', strtolower($intake['performance_rating'])) }}" style="width: {{ $intake['pass_rate'] }}%;"></div>
                            </div>
                            <span class="rating-{{ str_replace(' ', '-', strtolower($intake['performance_rating'])) }}" style="font-size: 8pt;">
                                {{ $intake['performance_rating'] }}
                            </span>
                        </td>
                    </tr>
                </table>

                <h4 style="margin: 10px 0 5px 0; font-size: 8.5pt; color: #4a5568; text-transform: uppercase;">Enrolled Students Registry</h4>
                <table style="margin-top: 0;">
                    <thead>
                        <tr style="background-color: #718096; color: white;">
                            <th style="background-color: #718096; border: 1px solid #718096; width: 30%;">Student Name</th>
                            <th style="background-color: #718096; border: 1px solid #718096; width: 20%;">Reg Number</th>
                            <th style="background-color: #718096; border: 1px solid #718096; width: 15%;">Enrolled Date</th>
                            <th style="background-color: #718096; border: 1px solid #718096; width: 15%;">Status</th>
                            <th style="background-color: #718096; border: 1px solid #718096; width: 10%; text-align: right;">Average Mark</th>
                            <th style="background-color: #718096; border: 1px solid #718096; width: 10%;">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($intake['students'] as $student)
                            <tr>
                                <td style="font-weight: bold;">{{ $student['name'] }}</td>
                                <td><code>{{ $student['reg_number'] }}</code></td>
                                <td>{{ $student['enrollment_date'] }}</td>
                                <td>{{ ucfirst($student['status']) }}</td>
                                <td style="text-align: right; font-weight: bold;">{{ $student['average_mark'] !== null ? number_format($student['average_mark'], 1) . '%' : 'N/A' }}</td>
                                <td style="font-weight: bold; color: {{ $student['pass_fail_status'] === 'Pass' ? '#38a169' : ($student['pass_fail_status'] === 'Fail' ? '#e53e3e' : '#718096') }};">
                                    {{ $student['pass_fail_status'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 10px; color: #a0aec0; font-style: italic;">No students are currently enrolled in this course intake.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @empty
            <div style="text-align: center; padding: 40px; color: #a0aec0;">No course intakes match the active performance analytical filters.</div>
        @endforelse
    @endif

    <div class="footer">
        MSUNLI Management & Reporting Audit System &bull; Midlands State University &bull; Confidential
    </div>
</body>
</html>
