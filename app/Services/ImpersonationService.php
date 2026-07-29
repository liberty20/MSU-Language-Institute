<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationService
{
    public const SESSION_IMPERSONATING_ID  = 'impersonating_user_id';
    public const SESSION_IMPERSONATOR_ID   = 'impersonator_id';
    public const SESSION_IMPERSONATOR_NAME = 'impersonator_name';
    public const SESSION_LOG_ID            = 'impersonation_log_id';
    public const SESSION_STARTED_AT        = 'impersonation_started_at';

    /**
     * Start impersonating the given user.
     */
    public function start(Request $request, User $admin, User $target, string $reason): ImpersonationLog
    {
        $log = ImpersonationLog::create([
            'impersonator_id' => $admin->id,
            'impersonated_id' => $target->id,
            'reason'          => $reason,
            'started_at'      => now(),
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'session_id'      => $request->session()->getId(),
        ]);

        // Store impersonation context in session
        $request->session()->put(self::SESSION_IMPERSONATING_ID,  $target->id);
        $request->session()->put(self::SESSION_IMPERSONATOR_ID,   $admin->id);
        $request->session()->put(self::SESSION_IMPERSONATOR_NAME, $admin->name);
        $request->session()->put(self::SESSION_LOG_ID,            $log->id);
        $request->session()->put(self::SESSION_STARTED_AT,        now()->toIso8601String());

        ActivityLog::log(
            'impersonation_started',
            "Super Administrator {$admin->name} ({$admin->email}) started impersonating {$target->name} ({$target->email}). Reason: {$reason}",
            $target,
            [
                'impersonator_id'   => $admin->id,
                'impersonator_name' => $admin->name,
                'impersonated_id'   => $target->id,
                'impersonated_name' => $target->name,
                'reason'            => $reason,
                'impersonation_log_id' => $log->id,
            ],
            $admin->id
        );

        return $log;
    }

    /**
     * Stop the current impersonation session and restore the admin.
     */
    public function stop(Request $request): ?ImpersonationLog
    {
        $logId        = $request->session()->get(self::SESSION_LOG_ID);
        $adminId      = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $targetId     = $request->session()->get(self::SESSION_IMPERSONATING_ID);

        // Remove all impersonation session keys
        $request->session()->forget([
            self::SESSION_IMPERSONATING_ID,
            self::SESSION_IMPERSONATOR_ID,
            self::SESSION_IMPERSONATOR_NAME,
            self::SESSION_LOG_ID,
            self::SESSION_STARTED_AT,
        ]);

        $logModel = $logId ? ImpersonationLog::find($logId) : null;
        $log   = $logModel ? $logModel->end() : null;
        $admin  = $adminId ? User::find($adminId) : null;
        $target = $targetId ? User::find($targetId) : null;

        if ($admin && $target) {
            ActivityLog::log(
                'impersonation_ended',
                "Super Administrator {$admin->name} ({$admin->email}) ended impersonation of {$target->name} ({$target->email}). Duration: " . ($log ? $log->duration_seconds : 0) . "s",
                $target,
                [
                    'impersonator_id'     => $admin->id,
                    'impersonated_id'     => $target->id,
                    'duration_seconds'    => $log ? $log->duration_seconds : null,
                    'impersonation_log_id' => $logId,
                ],
                $admin->id
            );
        }

        return $log;
    }

    /**
     * Check whether the current session is an impersonation session.
     */
    public function isImpersonating(Request $request): bool
    {
        return $request->session()->has(self::SESSION_IMPERSONATING_ID);
    }
}
