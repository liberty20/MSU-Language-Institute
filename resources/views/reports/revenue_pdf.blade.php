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
            width: 35%;
            text-align: right;
            vertical-align: middle;
            font-size: 8pt;
            color: #718096;
            line-height: 1.5;
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
        
        /* KPI Cards Grid */
        .widget-row {
            margin-bottom: 15px;
            width: 100%;
        }
        .widget {
            float: left;
            width: 17%;
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
            font-size: 7.5px;
            font-weight: bold;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .widget-value {
            font-size: 10pt;
            font-weight: 800;
            color: #0a1f44;
        }
        
        /* Table styles */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 8pt;
        }
        table.data-table th {
            background-color: #0a1f44;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #0a1f44;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.3px;
        }
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
        }
        table.data-table tr:nth-child(even) {
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
        .badge-pending {
            background-color: #ebf8ff;
            color: #2b6cb0;
            border: 1px solid #bee3f8;
        }
        .badge-verified {
            background-color: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        .badge-rejected, .badge-failed {
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
        
        .filters-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        .filters-title {
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="report-header" style="border: none; width: 100%; margin-bottom: 10px;">
        <tr style="background: transparent;">
            <td class="logo-cell" style="border: none; padding: 0;">
                @if(file_exists(public_path('msu-logo-2.png')))
                    <img src="{{ public_path('msu-logo-2.png') }}" />
                @endif
            </td>
            <td class="title-cell" style="border: none; padding-left: 10px;">
                <h1>Midlands State University</h1>
                <p>National Language Institute &mdash; Financial Analytics</p>
            </td>
            <td class="metadata-cell" style="border: none; text-align: right;">
                <strong>Generated Date:</strong> {{ $generated_date }}<br>
                <strong>Generated By:</strong> {{ $generated_by }}
            </td>
        </tr>
    </table>

    <div class="report-title-bar">
        <h2 class="report-title">{{ $title }}</h2>
        <p class="report-desc">Comprehensive operational revenue ledger & transactional data tracking</p>
    </div>

    <!-- Active Filters Details -->
    <div class="filters-section">
        <div class="filters-title">Active Reporting Parameters</div>
        <div>
            @if(!empty($filters['preset']))
                <strong>Preset Range:</strong> {{ ucwords(str_replace('_', ' ', $filters['preset'])) }} |
            @endif
            @if(!empty($filters['date_start']))
                <strong>Start:</strong> {{ $filters['date_start'] }} |
            @endif
            @if(!empty($filters['date_end']))
                <strong>End:</strong> {{ $filters['date_end'] }} |
            @endif
            @if($type === 'language_services')
                @if(!empty($filters['service_category']))
                    <strong>Category:</strong> {{ ucwords($filters['service_category']) }} |
                @endif
                @if(!empty($filters['client_id']))
                    <strong>Customer Restricted</strong> |
                @endif
                @if(!empty($filters['currency']))
                    <strong>Currency:</strong> {{ strtoupper($filters['currency']) }} |
                @endif
            @else
                @if(!empty($filters['course_id']))
                    <strong>Course ID:</strong> {{ $filters['course_id'] }} |
                @endif
                @if(!empty($filters['student_id']))
                    <strong>Student Restricted</strong> |
                @endif
            @endif
            @if(!empty($filters['payment_status']))
                <strong>Status:</strong> {{ ucwords($filters['payment_status']) }} |
            @endif
            @if(!empty($filters['payment_method']))
                <strong>Method:</strong> {{ $filters['payment_method'] }}
            @endif
        </div>
    </div>

    <!-- KPI Widgets Cards grouped by currency for language services, single row for courses -->
    @if($type === 'language_services')
        @foreach($kpis['total_revenue'] as $curr => $total)
            @if($total > 0 || ($kpis['total_transactions'][$curr] ?? 0) > 0 || ($kpis['outstanding_payments'][$curr] ?? 0.0) > 0.0)
                <h4 style="margin: 10px 0 5px 0; color: #0a1f44; text-transform: uppercase; font-size: 8.5pt; letter-spacing: 0.5px;">Financial Summary ({{ $curr }})</h4>
                <div class="widget-row">
                    <div class="widget">
                        <div class="widget-title">Total Revenue</div>
                        <div class="widget-value">{{ $curr }} {{ number_format($total, 2) }}</div>
                    </div>
                    <div class="widget">
                        <div class="widget-title">Transactions</div>
                        <div class="widget-value">{{ $kpis['total_transactions'][$curr] ?? 0 }}</div>
                    </div>
                    <div class="widget">
                        <div class="widget-title">Avg per Tx</div>
                        <div class="widget-value">{{ $curr }} {{ number_format($kpis['average_revenue'][$curr] ?? 0.0, 2) }}</div>
                    </div>
                    <div class="widget">
                        <div class="widget-title">Revenue Growth</div>
                        <div class="widget-value">{{ number_format($kpis['revenue_growth'][$curr] ?? 0.0, 1) }}%</div>
                    </div>
                    <div class="widget">
                        <div class="widget-title">Outstanding</div>
                        <div class="widget-value">{{ $curr }} {{ number_format($kpis['outstanding_payments'][$curr] ?? 0.0, 2) }}</div>
                    </div>
                    <div class="clear"></div>
                </div>
            @endif
        @endforeach
    @else
        <div class="widget-row">
            <div class="widget">
                <div class="widget-title">Total Revenue</div>
                <div class="widget-value">USD {{ number_format($kpis['total_revenue'], 2) }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Transactions</div>
                <div class="widget-value">{{ $kpis['total_transactions'] }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Avg per Tx</div>
                <div class="widget-value">USD {{ number_format($kpis['average_revenue'], 2) }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Revenue Growth</div>
                <div class="widget-value">{{ number_format($kpis['revenue_growth'], 1) }}%</div>
            </div>
            <div class="widget">
                <div class="widget-title">Outstanding</div>
                <div class="widget-value">USD {{ number_format($kpis['outstanding_payments'], 2) }}</div>
            </div>
            <div class="clear"></div>
        </div>
    @endif

    <!-- Data Ledger Table -->
    @if($type === 'language_services')
        <table class="data-table">
            <thead>
                <tr>
                    <th>Quotation/Invoice</th>
                    <th>Customer Name</th>
                    <th>Service Category</th>
                    <th>Payment Method</th>
                    <th>Currency</th>
                    <th style="text-align: right;">Amount Paid</th>
                    <th style="text-align: right;">Outstanding</th>
                    <th>Status</th>
                    <th>Date Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                    @php
                        $customerName = $p->serviceRequest && $p->serviceRequest->client 
                            ? ($p->serviceRequest->client->organization ?? $p->serviceRequest->client->contact_person) 
                            : ($p->client ? $p->client->name : 'N/A');
                        $serviceLabel = $p->serviceRequest ? $p->serviceRequest->service_category : 'N/A';
                        $outstanding = $p->quotation 
                            ? max(0.0, (float)($p->quotation->amount - ($p->quotation->payments()->where('status', 'verified')->sum('amount_paid') ?? 0))) 
                            : 0.0;
                        $currCode = $p->quotation_currency ?? 'USD';
                    @endphp
                    <tr>
                        <td><strong>{{ $p->quotation ? $p->quotation->reference_number : ('PAY-' . $p->id) }}</strong></td>
                        <td>{{ $customerName }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $serviceLabel)) }}</td>
                        <td>{{ $p->bank_used ?? 'N/A' }}</td>
                        <td style="font-weight: bold; text-align: center;">{{ $currCode }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ $currCode }} {{ number_format($p->amount_paid, 2) }}</td>
                        <td style="text-align: right; color: #e53e3e;">{{ $currCode }} {{ number_format($outstanding, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $p->status }}">{{ $p->status }}</span>
                        </td>
                        <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                @if(count($payments) === 0)
                    <tr>
                        <td colspan="9" style="text-align: center; color: #a0aec0; padding: 20px;">No verified payment transaction records match filters.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Enrollment Ref</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Payment Method</th>
                    <th style="text-align: right;">Amount Paid</th>
                    <th style="text-align: right;">Outstanding</th>
                    <th>Payment Status</th>
                    <th>Date Enrolled</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollments as $e)
                    @php
                        $method = $e->payment_proof_path ? 'Bank Proof Upload' : 'Manual Admin Entry';
                        $courseTitle = $e->intake && $e->intake->course ? $e->intake->course->title : 'N/A';
                        $coursePrice = $e->intake && $e->intake->course ? $e->intake->course->price : 0;
                        $outstanding = max(0.0, (float)($coursePrice - $e->amount_paid));
                    @endphp
                    <tr>
                        <td><strong>ENR-{{ $e->id }}</strong></td>
                        <td>{{ $e->user ? $e->user->name : 'N/A' }}</td>
                        <td>{{ $courseTitle }}</td>
                        <td>{{ $method }}</td>
                        <td style="text-align: right; font-weight: bold;">USD {{ number_format($e->amount_paid, 2) }}</td>
                        <td style="text-align: right; color: #e53e3e;">USD {{ number_format($outstanding, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $e->payment_status }}">{{ $e->payment_status }}</span>
                        </td>
                        <td>{{ $e->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                @if(count($enrollments) === 0)
                    <tr>
                        <td colspan="8" style="text-align: center; color: #a0aec0; padding: 20px;">No verified enrollment transaction records match filters.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        Midlands State University &copy; {{ date('Y') }}. National Language Institute Operational Report. Confidential.
    </div>

</body>
</html>
