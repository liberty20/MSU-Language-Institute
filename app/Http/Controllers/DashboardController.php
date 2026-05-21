<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_clients'   => Client::count(),
            'active_requests' => ServiceRequest::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'pending_tasks'   => Task::whereIn('status', ['todo', 'in_progress'])->count(),
            'total_revenue'   => Quotation::where('status', 'approved')->sum('amount'),
        ];

        $recentRequests = ServiceRequest::with('client')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(fn($r) => [
                'id'        => $r->id,
                'reference' => $r->reference_number,
                'client'    => $r->client ? ($r->client->organization ?? $r->client->contact_person) : 'N/A',
                'service'   => ucwords(str_replace('_', ' ', $r->service_category)),
                'priority'  => $r->priority,
                'status'    => $r->status,
                'date'      => $r->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Dashboard', [
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
        ]);
    }
}
