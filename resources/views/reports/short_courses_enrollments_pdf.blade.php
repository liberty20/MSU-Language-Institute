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
            font-size: 9pt;
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
            font-size: 18pt;
            font-weight: 800;
            color: #0a1f44;
            margin: 0;
            line-height: 1.1;
        }
        .title-cell p {
            font-size: 8pt;
            color: #4a5568;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            font-weight: 600;
        }
        .metadata-cell {
            width: 40%;
            text-align: right;
            vertical-align: middle;
            font-size: 8pt;
            color: #718096;
            line-height: 1.4;
        }
        .report-title-bar {
            margin-top: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #f5c242;
            padding-left: 12px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: 800;
            color: #0a1f44;
            text-transform: uppercase;
            margin: 0;
        }
        .report-desc {
            font-size: 8pt;
            color: #718096;
            margin: 2px 0 0 0;
            font-weight: 500;
        }
        .filter-section {
            background-color: #edf2f7;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 7.5pt;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        .filter-title {
            font-weight: bold;
            color: #0a1f44;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }
        th {
            background-color: #0a1f44;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #0a1f44;
            text-transform: uppercase;
            font-size: 7pt;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6.5pt;
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
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <table style="width: 100%; border: none; background: transparent; margin-bottom: 10px;">
        <tr style="border: none; background: transparent;">
            <td style="width: 50px; border: none; background: transparent; vertical-align: middle;">
                <img src="{{ public_path('msu-logo-2.png') }}" style="width: 45px; height: 45px; display: block;" />
            </td>
            <td class="title-cell" style="border: none; background: transparent; vertical-align: middle; padding-left: 10px;">
                <h1>MSUNLI</h1>
                <p>Midlands State University National Language Institute</p>
            </td>
            <td class="metadata-cell" style="border: none; background: transparent; vertical-align: middle; text-align: right;">
                <p style="margin: 1px 0;"><strong>Generated By:</strong> {{ $generated_by }}</p>
                <p style="margin: 1px 0;"><strong>Date Compiled:</strong> {{ $generated_date }}</p>
                <p style="margin: 1px 0;"><strong>Format:</strong> PDF Report</p>
            </td>
        </tr>
    </table>

    <div class="report-title-bar">
        <h2 class="report-title">{{ $title }}</h2>
        <p class="report-desc">Student Enrollment Registration & Academic Inflow Ledger</p>
    </div>

    @if(!empty($filters['course_id']) || !empty($filters['instructor_id']) || !empty($filters['search']) || (isset($filters['status']) && $filters['status'] !== 'all'))
        <div class="filter-section">
            <div class="filter-title">Active Analytical Query Filters:</div>
            @if(!empty($filters['search'])) Search Term: "{{ $filters['search'] }}" &bull; @endif
            @if(isset($filters['status']) && $filters['status'] !== 'all') Status: {{ ucfirst($filters['status']) }} &bull; @endif
            Filtered datasets compiled dynamically from live course registrations database.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Enrollment Ref</th>
                <th>Student Name</th>
                <th>Email Address</th>
                <th>Course Details</th>
                <th>Intake Batch</th>
                <th>Instructor</th>
                <th style="text-align: right;">Amount Paid</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date Enrolled</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($enrollments as $e)
                <tr>
                    <td style="font-weight: bold; color: #0a1f44;">ENR-{{ $e->id }}</td>
                    <td style="font-weight: bold;">{{ $e->user ? $e->user->name : 'N/A' }}</td>
                    <td>{{ $e->user ? $e->user->email : 'N/A' }}</td>
                    <td>{{ $e->intake && $e->intake->course ? $e->intake->course->title : 'N/A' }}</td>
                    <td>{{ $e->intake ? $e->intake->name : 'N/A' }}</td>
                    <td>{{ $e->intake && $e->intake->instructor ? $e->intake->instructor->name : 'Unassigned' }}</td>
                    <td style="text-align: right; font-weight: bold;">${{ number_format($e->amount_paid, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $e->payment_status === 'verified' ? 'active' : 'pending' }}">
                            {{ $e->payment_status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $e->enrollment_status }}">
                            {{ $e->enrollment_status }}
                        </span>
                    </td>
                    <td>{{ $e->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; color: #a0aec0;">No student enrollment records match active filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        MSUNLI Management & Reporting Audit System &bull; Midlands State University &bull; Confidential &copy; {{ date('Y') }}
    </div>
</body>
</html>
