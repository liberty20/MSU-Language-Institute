<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Client;
use App\Models\Task;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Gather aggregate metrics
        $stats = [
            'total_clients' => Client::count(),
            'active_requests' => ServiceRequest::whereNotIn('status', ['completed', 'cancelled', 'draft'])->count(),
            'pending_tasks' => Task::whereIn('status', ['pending', 'in_progress', 'overdue'])->count(),
            'total_revenue' => Quotation::where('status', 'approved')->sum('amount'),
        ];

        // Fetch the 5 most recent active service requests
        $recentRequests = ServiceRequest::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'reference' => $req->reference_number,
                    'client' => $req->client ? ($req->client->organization ?? $req->client->contact_person) : 'Unknown',
                    'service' => ucfirst(str_replace('_', ' ', $req->service_category)),
                    'status' => $req->status,
                    'date' => $req->created_at->format('M d, Y')
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentRequests' => $recentRequests
        ]);
    }
}
