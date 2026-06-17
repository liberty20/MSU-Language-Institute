<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Allow access if the user has specific spatie roles OR is assigned to at least one intake as instructor OR belongs to a non-AOS unit
        if (
            $user->primary_category === 'Staff' &&
            ($user->hasAnyRole(['language_expert', 'part_time_staff']) || 
             $user->instructedIntakes()->exists() ||
             ($user->department && $user->department->code !== 'AOS'))
        ) {
            return $next($request);
        }

        abort(403, 'Unauthorized. This area is reserved for course instructors.');
    }
}
