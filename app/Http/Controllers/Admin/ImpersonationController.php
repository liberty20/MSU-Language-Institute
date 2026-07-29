<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImpersonationController extends Controller
{
    private $impersonation;

    public function __construct(ImpersonationService $impersonation)
    {
        $this->impersonation = $impersonation;
    }

    /**
     * Show the user picker for impersonation.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $users = User::with('roles')
            ->where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($search) {
                if ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_administrator'))
            ->orderBy('name')
            ->paginate(20)
            ->appends(request()->query());

        return Inertia::render('Admin/Impersonate', [
            'users'   => $users,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Start impersonating the given user.
     */
    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        // Guards
        if ($user->id === $admin->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        if ($user->isSuperAdministrator()) {
            return back()->with('error', 'You cannot impersonate another Super Administrator.');
        }

        if ($this->impersonation->isImpersonating($request)) {
            return back()->with('error', 'You are already in an active impersonation session. Please stop it first.');
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $this->impersonation->start($request, $admin, $user, $request->reason);

        return redirect()->route('dashboard')
            ->with('success', "You are now impersonating {$user->name}. Click the banner to return to your account.");
    }

    /**
     * Stop the current impersonation session.
     */
    public function stop(Request $request)
    {
        if (! $this->impersonation->isImpersonating($request)) {
            return redirect()->route('dashboard');
        }

        $this->impersonation->stop($request);

        return redirect()->route('dashboard')
            ->with('success', 'You have returned to your own account.');
    }

    /**
     * Show the impersonation audit logs.
     */
    public function logs(Request $request)
    {
        $logs = ImpersonationLog::with(['impersonator', 'impersonated'])
            ->orderBy('started_at', 'desc')
            ->paginate(25)
            ->appends(request()->query());

        return Inertia::render('Admin/ImpersonationLogs', [
            'logs' => $logs,
        ]);
    }
}
