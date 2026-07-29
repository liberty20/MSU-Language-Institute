<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRecentAuth
{
    /**
     * How long (in seconds) a password confirmation remains valid.
     */
    protected int $validFor = 900; // 15 minutes

    public function handle(Request $request, Closure $next)
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);

        if ((time() - $confirmedAt) > $this->validFor) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Password confirmation required.'], 423);
            }

            // Store intended URL and redirect to confirm-password
            $request->session()->put('url.intended', $request->url());

            return redirect()->route('password.confirm');
        }

        return $next($request);
    }
}
