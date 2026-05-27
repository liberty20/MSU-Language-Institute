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
            
            if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
                // Staff scoped stats
                $stats['active_requests'] = ServiceRequest::whereNotIn('status', ['completed'])
                    ->whereHas('assignments', fn($q) => $q->where('assigned_to', $user->id))->count();
                $stats['pending_tasks']   = Task::whereIn('status', ['todo', 'in_progress'])
                    ->whereHas('assignment', fn($q) => $q->where('assigned_to', $user->id))->count();
                $stats['total_revenue']   = 0;
                $stats['assigned_tasks']  = \App\Models\Assignment::whereNotIn('status', ['completed'])->count();
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

        return Inertia::render('Dashboard', [
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
        ]);
    }
}
