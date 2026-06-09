<?php

namespace App\Http\Controllers\Deputy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SystemSetting;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !$user->hasRole('deputy_director')) {
                abort(403, 'Unauthorized. Access restricted to Deputy Director.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Get mail delivery status logs
        $query = EmailLog::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('sender_email', 'like', "%{$search}%");
            });
        }

        $emailLogs = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Compute mail statistics
        $stats = [
            'total'     => EmailLog::count(),
            'sent'      => EmailLog::where('status', 'sent')->count(),
            'delivered' => EmailLog::where('status', 'delivered')->count(),
            'pending'   => EmailLog::where('status', 'pending')->count(),
            'bounced'   => EmailLog::where('status', 'bounced')->count(),
            'failed'    => EmailLog::where('status', 'failed')->count(),
        ];

        // System configuration settings
        $config = SystemSetting::get('deputy_system_config', [
            'site_name'           => 'MSU Language Institute Portal',
            'admin_email'         => 'language.institute@msu.ac.zw',
            'support_phone'       => '+263 54 2260331',
            'max_upload_size'     => 10, // MB
            'maintenance_mode'    => false,
            'allow_registrations' => true,
        ]);

        return Inertia::render('Deputy/Settings', [
            'emailLogs' => $emailLogs,
            'stats'     => $stats,
            'config'    => $config,
            'filters'   => $request->only(['status', 'search']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'site_name'           => 'required|string|max:255',
            'admin_email'         => 'required|email|max:255',
            'support_phone'       => 'required|string|max:50',
            'max_upload_size'     => 'required|integer|min:1|max:100',
            'maintenance_mode'    => 'required|boolean',
            'allow_registrations' => 'required|boolean',
        ]);

        $oldConfig = SystemSetting::get('deputy_system_config', []);
        
        if ($oldConfig === $validated) {
            return redirect()->back()->with('error', 'No changes detected. Configuration remains unchanged.');
        }

        SystemSetting::set('deputy_system_config', $validated);

        ActivityLog::log('update_settings', 'Deputy Director updated system configuration settings.', null, [
            'previous' => $oldConfig,
            'new'      => $validated,
        ]);

        return redirect()->back()->with('success', 'System configuration settings updated successfully.');
    }

    public function resetData()
    {
        Schema::disableForeignKeyConstraints();

        // Delete all client, service request, quotations, assignments, and tasks
        DB::table('tasks')->truncate();
        DB::table('assignments')->truncate();
        DB::table('quotations')->truncate();
        DB::table('payments')->truncate();
        DB::table('approvals')->truncate();
        DB::table('comments')->truncate();
        DB::table('service_requests')->truncate();
        DB::table('clients')->truncate();
        DB::table('procurement_requests')->truncate();

        // Safely delete client user accounts from users table
        $clientUsers = User::role('client')->get();
        foreach ($clientUsers as $cu) {
            $cu->delete();
        }

        Schema::enableForeignKeyConstraints();

        ActivityLog::log('system_reset', 'Deputy Director triggered a complete system data reset, wiping all clients, service requests, quotations, assignments, and tasks.', null, [
            'performed_by' => auth()->user()->name,
            'ip'           => request()->ip(),
            'timestamp'    => now()->toIso8601String(),
        ]);

        return redirect()->back()->with('success', 'System data reset completed successfully. All client requests, quotations, and assignments have been permanently removed.');
    }
}
