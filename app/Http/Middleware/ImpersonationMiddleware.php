<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $impersonatedId = $request->session()->get(ImpersonationService::SESSION_IMPERSONATING_ID);

        if ($impersonatedId && Auth::check()) {
            // Swap the Auth user to the impersonated user for this request only.
            // Auth::onceUsingId does NOT persist to session — the session still
            // holds the real admin's auth state.
            Auth::onceUsingId($impersonatedId);
        }

        return $next($request);
    }
}
