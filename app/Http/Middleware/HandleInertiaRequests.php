<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user() ? $request->user()->getRoleNames() : [],
                'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name') : [],
                'is_instructor' => $request->user() ? (
                    $request->user()->hasAnyRole(['language_expert', 'part_time_staff']) || 
                    $request->user()->instructedIntakes()->exists() ||
                    ($request->user()->department && $request->user()->department->code !== 'AOS')
                ) : false,
                'is_staff_outside_aos' => $request->user() ? (
                    !$request->user()->hasRole('client') && 
                    !$request->user()->hasRole('student') && 
                    $request->user()->department && 
                    $request->user()->department->code !== 'AOS'
                ) : false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'unreadAnnouncementsCount' => $request->user() && $request->user()->primary_category === 'Student' ? (
                \App\Models\Announcement::whereIn('course_id', function ($query) use ($request) {
                    $query->select('course_intakes.course_id')
                        ->from('course_enrollments')
                        ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                        ->where('course_enrollments.user_id', $request->user()->id)
                        ->where('course_enrollments.enrollment_status', 'active');
                })
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->whereNotExists(function ($query) use ($request) {
                    $query->select(\DB::raw(1))
                        ->from('announcement_reads')
                        ->whereRaw('announcement_reads.announcement_id = announcements.id')
                        ->where('announcement_reads.student_id', $request->user()->id);
                })
                ->count()
            ) : 0,
            'ziggy' => function () {
                return (new Ziggy)->toArray();
            },
        ]);
    }
}
