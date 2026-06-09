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
use App\Models\User;
use App\Models\EmailLog;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director'])) {
                abort(403, 'Unauthorized. Access restricted to System Administrators, Directors, and Deputies.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
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

        // Email monitoring log logic
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

        // Statistics for mail monitoring
        $stats = [
            'total'     => EmailLog::count(),
            'sent'      => EmailLog::where('status', 'sent')->count(),
            'delivered' => EmailLog::where('status', 'delivered')->count(),
            'pending'   => EmailLog::where('status', 'pending')->count(),
            'bounced'   => EmailLog::where('status', 'bounced')->count(),
            'failed'    => EmailLog::where('status', 'failed')->count(),
        ];

        // System Configuration
        $config = SystemSetting::get('deputy_system_config', [
            'site_name'           => 'MSU Language Institute Portal',
            'admin_email'         => 'language.institute@msu.ac.zw',
            'support_phone'       => '+263 54 2260331',
            'max_upload_size'     => 10, // MB
            'maintenance_mode'    => false,
            'allow_registrations' => true,
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
            'emailLogs' => $emailLogs,
            'stats'     => $stats,
            'config'    => $config,
            'filters'   => $request->only(['status', 'search']),
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

        $oldFaqs = SystemSetting::get('short_courses_faqs', []);
        $oldTestimonials = SystemSetting::get('short_courses_testimonials', []);
        $oldAnnouncements = SystemSetting::get('short_courses_announcements', []);
        $oldContactInfo = SystemSetting::get('short_courses_contact_info', []);
        $oldBankingDetails = SystemSetting::get('short_courses_banking_details', []);

        if (
            $oldFaqs === $validated['faqs'] &&
            $oldTestimonials === $validated['testimonials'] &&
            $oldAnnouncements === $validated['announcements'] &&
            $oldContactInfo === $validated['contactInfo'] &&
            $oldBankingDetails === $validated['bankingDetails']
        ) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

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

        $unit->fill([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        if (!$unit->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $unit->save();

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

        $section->fill([
            'unit_id' => $validated['unit_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ? strtoupper($validated['code']) : $section->code,
        ]);

        if (!$section->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $section->save();

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

        ActivityLog::log('update_settings', 'System configuration settings updated.', null, [
            'previous' => $oldConfig,
            'new'      => $validated,
        ]);

        return redirect()->back()->with('success', 'System configuration settings updated successfully.');
    }

    public function resetData()
    {
        $user = auth()->user();
        if (!$user->hasRole('deputy_director')) {
            abort(403, 'Unauthorized. Access restricted to Deputy Director.');
        }

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
            'performed_by' => $user->name,
            'ip'           => request()->ip(),
            'timestamp'    => now()->toIso8601String(),
        ]);

        return redirect()->back()->with('success', 'System data reset completed successfully. All client requests, quotations, and assignments have been permanently removed.');
    }
}

