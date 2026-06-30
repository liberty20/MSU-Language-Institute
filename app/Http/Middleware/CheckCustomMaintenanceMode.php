<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckCustomMaintenanceMode
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
        // 1. Get configuration
        $config = SystemSetting::get('deputy_system_config', []);
        $isMaintenance = isset($config['maintenance_mode']) && ($config['maintenance_mode'] === true || $config['maintenance_mode'] === 1 || $config['maintenance_mode'] === '1');

        if ($isMaintenance) {
            // 2. Allow if user is administrator/director/deputy
            $user = Auth::user();
            if ($user && $user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director'])) {
                return $next($request);
            }

            // 3. Bypass routes
            if (
                $request->is('login') || 
                $request->is('logout') || 
                $request->is('admin/settings*') || 
                $request->is('short-courses/live-config')
            ) {
                return $next($request);
            }

            // 4. Render maintenance page
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return Inertia::render('Errors/Maintenance', [
                    'contactInfo' => SystemSetting::get('short_courses_contact_info', [
                        'email' => 'language.institute@msu.ac.zw',
                        'phone' => '+263 54 2260331',
                    ])
                ])->toResponse($request)->setStatusCode(503);
            }

            return response()->view('errors.maintenance', [
                'contactInfo' => SystemSetting::get('short_courses_contact_info', [
                    'email' => 'language.institute@msu.ac.zw',
                    'phone' => '+263 54 2260331',
                ])
            ], 503);
        }

        return $next($request);
    }
}
