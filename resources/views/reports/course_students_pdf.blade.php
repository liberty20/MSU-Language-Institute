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
        .course-info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 8.5pt;
            border-collapse: collapse;
        }
        .course-info-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e0;
        }
        .course-info-table td.label {
            font-weight: bold;
            background-color: #edf2f7;
            color: #0a1f44;
            width: 20%;
        }
        table.students-table {
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
        .badge-verified {
            background-color: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
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
        <p class="report-desc">Scope: Course Enrollment Registry</p>
    </div>

    <table class="course-info-table">
        <tr>
            <td class="label">Course Name</td>
            <td>{{ $intake->course->title }} ({{ $intake->course->code }})</td>
            <td class="label">Intake Batch</td>
            <td>{{ $intake->name }}</td>
        </tr>
        <tr>
            <td class="label">Instructor</td>
            <td>{{ $intake->instructor ? $intake->instructor->name : 'Unassigned' }}</td>
            <td class="label">Timeline</td>
            <td>{{ $intake->start_date ? $intake->start_date->format('Y-m-d') : 'N/A' }} to {{ $intake->end_date ? $intake->end_date->format('Y-m-d') : 'N/A' }}</td>
        </tr>
    </table>

    <table class="students-table">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Email Address</th>
                <th>Phone Number</th>
                <th>Registration Number</th>
                <th>Enrolled Date</th>
                <th>Payment Status</th>
                <th>Enrollment Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($enrollments as $e)
                <tr>
                    <td style="font-weight: bold;">{{ $e->user->name }}</td>
                    <td>{{ $e->user->email }}</td>
                    <td>{{ $e->user->phone ?? 'N/A' }}</td>
                    <td><code>REG-{{ $e->user->created_at ? $e->user->created_at->format('Y') : date('Y') }}-{{ str_pad($e->user->id, 4, '0', STR_PAD_LEFT) }}</code></td>
                    <td>{{ $e->created_at->format('Y-m-d') }}</td>
                    <td>
                        <span class="badge badge-{{ $e->payment_status === 'verified' ? 'verified' : 'pending' }}">
                            {{ $e->payment_status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $e->enrollment_status }}">
                            {{ $e->enrollment_status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #a0aec0;">No student enrollments found in this course intake.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        MSUNLI Management & Reporting Audit System &bull; Midlands State University &bull; Confidential
    </div>
</body>
</html>
