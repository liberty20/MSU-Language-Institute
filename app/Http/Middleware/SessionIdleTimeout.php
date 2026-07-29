<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionIdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        // Only applies to authenticated users
        if (! Auth::check()) {
            return $next($request);
        }

        // Skip the ping route and the logout route (avoid logging loops)
        if ($request->routeIs('session.ping', 'logout', 'password.confirm*')) {
            $request->session()->put('last_activity', time());
            return $next($request);
        }

        $timeoutMinutes = (int) (SystemSetting::get('session_idle_timeout_minutes', 20));
        $lastActivity   = $request->session()->get('last_activity');

        if ($lastActivity !== null) {
            $idleSeconds = time() - (int) $lastActivity;

            if ($idleSeconds > ($timeoutMinutes * 60)) {
                $userId = Auth::id();
                $user   = Auth::user();

                // Log the timeout event before invalidating
                ActivityLog::log(
                    'session_timeout',
                    "Session timed out for user {$user->name} ({$user->email}) after {$idleSeconds}s of inactivity.",
                    $user,
                    [
                        'idle_seconds'    => $idleSeconds,
                        'timeout_minutes' => $timeoutMinutes,
                    ],
                    $userId
                );

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'session_timeout', 'message' => 'Your session has expired due to inactivity.'], 401);
                }

                return redirect()->route('login', ['reason' => 'timeout']);
            }
        }

        // Update last activity timestamp on every request
        $request->session()->put('last_activity', time());

        return $next($request);
    }
}
