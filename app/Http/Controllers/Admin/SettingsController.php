<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Department;
use App\Models\MsunliSection;
use App\Models\MsunliRole;
use App\Models\SystemSetting;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !$user->hasAnyRole(['ict_administrator', 'executive_director'])) {
                abort(403, 'Unauthorized. Only ICT Administrator or Director can manage system settings.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $units = Department::orderBy('name')->get();
        $sections = MsunliSection::with('unit')->orderBy('name')->get();
        $roles = MsunliRole::with('section.unit', 'spatieRole')->orderBy('name')->get();
        $spatieRoles = Role::orderBy('name')->get(['id', 'name']);

        $faqs = SystemSetting::get('short_courses_faqs', []);
        $testimonials = SystemSetting::get('short_courses_testimonials', []);
        $announcements = SystemSetting::get('short_courses_announcements', []);
        $contactInfo = SystemSetting::get('short_courses_contact_info', [
            'email' => '', 'phone' => '', 'mobile' => '', 'location' => '', 'hours' => ''
        ]);
        $bankingDetails = SystemSetting::get('short_courses_banking_details', [
            'account_name' => '', 'bank' => '', 'branch' => '', 'account_number' => '', 'nostro_number' => '', 'type' => '', 'currency_accepted' => ''
        ]);

        return Inertia::render('Admin/Settings/Index', [
            'units' => $units,
            'sections' => $sections,
            'roles' => $roles,
            'spatieRoles' => $spatieRoles,
            'faqs' => $faqs,
            'testimonials' => $testimonials,
            'announcements' => $announcements,
            'contactInfo' => $contactInfo,
            'bankingDetails' => $bankingDetails,
        ]);
    }

    public function updateShortCoursesPortal(Request $request)
    {
        $validated = $request->validate([
            'faqs' => 'required|array',
            'testimonials' => 'required|array',
            'announcements' => 'required|array',
            'contactInfo' => 'required|array',
            'bankingDetails' => 'required|array',
        ]);

        $oldAnnouncements = SystemSetting::get('short_courses_announcements', []);

        SystemSetting::set('short_courses_faqs', $validated['faqs']);
        SystemSetting::set('short_courses_testimonials', $validated['testimonials']);
        SystemSetting::set('short_courses_announcements', $validated['announcements']);
        SystemSetting::set('short_courses_contact_info', $validated['contactInfo']);
        SystemSetting::set('short_courses_banking_details', $validated['bankingDetails']);

        // Check if a new announcement was added
        if (count($validated['announcements']) > count($oldAnnouncements)) {
            $latestAnn = last($validated['announcements']);
            $title = isset($latestAnn['title']) ? $latestAnn['title'] : 'New Announcement';
            $text = isset($latestAnn['text']) ? $latestAnn['text'] : 'Please check the portal for details.';
            
            // Notify all students
            $students = \App\Models\User::role('student')->get();
            foreach ($students as $student) {
                $student->notify(new \App\Notifications\SystemNotification('Courses', 'New Course Announcement: ' . $title, $text, route('student.courses')));
            }
            // Notify all instructors
            $instructors = \App\Models\User::role('language_expert')->get();
            foreach ($instructors as $instructor) {
                $instructor->notify(new \App\Notifications\SystemNotification('Courses', 'New Course Announcement: ' . $title, $text, route('instructor.enrollments')));
            }
        }

        return redirect()->back()->with('success', 'Short Courses Public Information Portal settings updated successfully.');
    }

    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:departments,code',
        ]);

        Department::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        return redirect()->back()->with('success', 'MSUNLI Unit created successfully.');
    }

    public function updateUnit(Request $request, $id)
    {
        $unit = Department::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:departments,code,' . $unit->id,
        ]);

        $unit->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        return redirect()->back()->with('success', 'MSUNLI Unit updated successfully.');
    }

    public function destroyUnit($id)
    {
        $unit = Department::findOrFail($id);
        
        // Prevent deleting core units with active users
        if ($unit->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete Unit. Active users are currently assigned to it.');
        }

        $unit->delete();
        return redirect()->back()->with('success', 'MSUNLI Unit deleted.');
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
        ]);

        MsunliSection::create([
            'unit_id' => $validated['unit_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ? strtoupper($validated['code']) : strtoupper(substr(str_replace(' ', '', $validated['name']), 0, 5)),
        ]);

        return redirect()->back()->with('success', 'Department/Section created successfully.');
    }

    public function updateSection(Request $request, $id)
    {
        $section = MsunliSection::findOrFail($id);
        $validated = $request->validate([
            'unit_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
        ]);

        $section->update([
            'unit_id' => $validated['unit_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ? strtoupper($validated['code']) : $section->code,
        ]);

        return redirect()->back()->with('success', 'Department/Section updated successfully.');
    }

    public function destroySection($id)
    {
        $section = MsunliSection::findOrFail($id);
        if ($section->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete Section. Active users are currently assigned to it.');
        }

        $section->delete();
        return redirect()->back()->with('success', 'Department/Section deleted.');
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:msunli_sections,id',
            'name' => 'required|string|max:255',
        ]);

        // Automatically create a corresponding Spatie role slug in Spatie table
        $spatieRoleSlug = strtolower(str_replace(' ', '_', $validated['name']));
        $spatieRole = Role::firstOrCreate([
            'name' => $spatieRoleSlug,
            'guard_name' => 'web'
        ]);

        MsunliRole::create([
            'section_id' => $validated['section_id'],
            'role_id' => $spatieRole->id,
            'name' => $validated['name']
        ]);

        return redirect()->back()->with('success', 'Institutional Role created and synced successfully.');
    }

    public function destroyRole($id)
    {
        $role = MsunliRole::findOrFail($id);
        if ($role->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete Role. Active users are currently assigned to it.');
        }

        // Keep Spatie role if it is a default core role, but delete institutional mapping
        $role->delete();
        return redirect()->back()->with('success', 'Institutional Role deleted.');
    }

    /**
     * Approve and post a pending student testimonial online.
     */
    public function approveTestimonial(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);

        $pending = SystemSetting::get('short_courses_pending_testimonials', []);
        $foundIndex = null;
        foreach ($pending as $idx => $pt) {
            if ($pt['id'] === $validated['id']) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            return redirect()->back()->with('error', 'Pending testimonial not found.');
        }

        $pt = $pending[$foundIndex];

        // Move to active testimonials
        $active = SystemSetting::get('short_courses_testimonials', []);
        $active[] = [
            'name' => $pt['name'],
            'course' => $pt['course'],
            'text' => $pt['text'],
        ];

        // Remove from pending list
        array_splice($pending, $foundIndex, 1);

        SystemSetting::set('short_courses_testimonials', $active);
        SystemSetting::set('short_courses_pending_testimonials', $pending);

        return redirect()->back()->with('success', 'Testimonial approved and posted successfully!');
    }

    /**
     * Reject a pending student testimonial.
     */
    public function rejectTestimonial(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);

        $pending = SystemSetting::get('short_courses_pending_testimonials', []);
        $foundIndex = null;
        foreach ($pending as $idx => $pt) {
            if ($pt['id'] === $validated['id']) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            return redirect()->back()->with('error', 'Pending testimonial not found.');
        }

        // Remove from pending list
        array_splice($pending, $foundIndex, 1);
        SystemSetting::set('short_courses_pending_testimonials', $pending);

        return redirect()->back()->with('success', 'Testimonial rejected successfully.');
    }

    /**
     * Delete an active testimonial (both dynamic and static).
     */
    public function destroyTestimonial($idx)
    {
        $active = SystemSetting::get('short_courses_testimonials', []);

        if (!isset($active[$idx])) {
            return redirect()->back()->with('error', 'Testimonial not found.');
        }

        array_splice($active, $idx, 1);
        SystemSetting::set('short_courses_testimonials', $active);

        return redirect()->back()->with('success', 'Testimonial deleted successfully.');
    }
}

