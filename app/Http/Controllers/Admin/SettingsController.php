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

        $allTestimonials = SystemSetting::get('short_courses_testimonials', []);
        $dirty = false;
        
        // Normalize legacy and seeded ones
        foreach ($allTestimonials as &$t) {
            if (empty($t['id'])) {
                $t['id'] = uniqid();
                $dirty = true;
            }
            if (empty($t['status'])) {
                $t['status'] = 'approved';
                $dirty = true;
            }
        }

        // Migrate legacy pending testimonies if any
        $legacyPending = SystemSetting::get('short_courses_pending_testimonials', []);
        if (!empty($legacyPending)) {
            foreach ($legacyPending as $lp) {
                $allTestimonials[] = [
                    'id' => $lp['id'] ?? uniqid(),
                    'name' => $lp['name'],
                    'course' => $lp['course'],
                    'text' => $lp['text'],
                    'status' => 'pending',
                    'submitted_at' => $lp['submitted_at'] ?? now()->toDateTimeString(),
                ];
            }
            SystemSetting::set('short_courses_pending_testimonials', null);
            $dirty = true;
        }

        if ($dirty) {
            SystemSetting::set('short_courses_testimonials', $allTestimonials);
        }

        // Separate them by status
        $activeTestimonials = array_values(array_filter($allTestimonials, fn($t) => isset($t['status']) && $t['status'] === 'approved'));
        $pendingTestimonials = array_values(array_filter($allTestimonials, fn($t) => isset($t['status']) && $t['status'] === 'pending'));

        return Inertia::render('Admin/Settings/Index', [
            'units' => $units,
            'sections' => $sections,
            'roles' => $roles,
            'spatieRoles' => $spatieRoles,
            'faqs' => $faqs,
            'testimonials' => $activeTestimonials,
            'pendingTestimonials' => $pendingTestimonials,
            'announcements' => $announcements,
            'contactInfo' => $contactInfo,
            'bankingDetails' => $bankingDetails,
            'emailLogs' => $emailLogs,
            'stats'     => $stats,
            'config'    => $config,
            'filters'   => $request->only(['status', 'search']),
            'activeResetRequest' => SystemSetting::get('database_reset_request'),
            'resetHistory' => SystemSetting::get('database_reset_history', []),
        ]);
    }

    public function updateShortCoursesPortal(Request $request)
    {
        $validated = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.question' => 'required|string|max:255',
            'faqs.*.answer' => 'required|string',
            'announcements' => 'required|array',
            'announcements.*.date' => 'required|date',
            'announcements.*.title' => 'required|string|max:255',
            'announcements.*.text' => 'required|string',
            'contactInfo' => 'required|array',
            'contactInfo.email' => 'required|email|max:255',
            'contactInfo.phone' => 'required|string|max:100',
            'contactInfo.mobile' => 'required|string|max:100',
            'contactInfo.hours' => 'required|string|max:255',
            'contactInfo.location' => 'required|string|max:500',
            'bankingDetails' => 'required|array',
            'bankingDetails.account_name' => 'required|string|max:255',
            'bankingDetails.bank' => 'required|string|max:255',
            'bankingDetails.branch' => 'required|string|max:255',
            'bankingDetails.type' => 'required|string|max:100',
            'bankingDetails.account_number' => 'required|string|max:100',
            'bankingDetails.nostro_number' => 'required|string|max:100',
            'bankingDetails.currency_accepted' => 'required|string|max:255',
        ]);

        $oldFaqs = SystemSetting::get('short_courses_faqs', []);
        $oldAnnouncements = SystemSetting::get('short_courses_announcements', []);
        $oldContactInfo = SystemSetting::get('short_courses_contact_info', []);
        $oldBankingDetails = SystemSetting::get('short_courses_banking_details', []);

        if (
            $oldFaqs === $validated['faqs'] &&
            $oldAnnouncements === $validated['announcements'] &&
            $oldContactInfo === $validated['contactInfo'] &&
            $oldBankingDetails === $validated['bankingDetails']
        ) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        SystemSetting::set('short_courses_faqs', $validated['faqs']);
        SystemSetting::set('short_courses_announcements', $validated['announcements']);
        SystemSetting::set('short_courses_contact_info', $validated['contactInfo']);
        SystemSetting::set('short_courses_banking_details', $validated['bankingDetails']);

        // Log settings changes in the audit trail
        ActivityLog::log('update_short_courses_settings', 'Short Courses Public Information Portal settings updated.', null, [
            'previous' => [
                'faqs' => $oldFaqs,
                'announcements' => $oldAnnouncements,
                'contactInfo' => $oldContactInfo,
                'bankingDetails' => $oldBankingDetails,
            ],
            'new' => $validated,
        ]);

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
            'text' => 'nullable|string|max:1000',
        ]);

        try {
            \DB::transaction(function() use ($validated) {
                $all = SystemSetting::get('short_courses_testimonials', []);
                $foundIndex = -1;
                foreach ($all as $idx => $t) {
                    if (isset($t['id']) && $t['id'] === $validated['id']) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Pending testimonial not found.');
                }

                $all[$foundIndex]['status'] = 'approved';
                if (!empty($validated['text'])) {
                    $all[$foundIndex]['text'] = $validated['text'];
                }
                $all[$foundIndex]['moderated_by'] = \Auth::user()->name;
                $all[$foundIndex]['moderated_at'] = now()->toDateTimeString();

                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'approve_testimonial',
                    'Administrator ' . \Auth::user()->name . ' approved testimonial from student ' . $all[$foundIndex]['name'] . ' for course ' . $all[$foundIndex]['course']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

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

        try {
            \DB::transaction(function() use ($validated) {
                $all = SystemSetting::get('short_courses_testimonials', []);
                $foundIndex = -1;
                foreach ($all as $idx => $t) {
                    if (isset($t['id']) && $t['id'] === $validated['id']) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Pending testimonial not found.');
                }

                $all[$foundIndex]['status'] = 'rejected';
                $all[$foundIndex]['moderated_by'] = \Auth::user()->name;
                $all[$foundIndex]['moderated_at'] = now()->toDateTimeString();

                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'reject_testimonial',
                    'Administrator ' . \Auth::user()->name . ' rejected testimonial from student ' . $all[$foundIndex]['name'] . ' for course ' . $all[$foundIndex]['course']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Testimonial rejected successfully.');
    }

    /**
     * Moderate (edit pending) testimonial without approving it immediately.
     */
    public function moderateTestimonial(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'text' => 'required|string|max:1000',
        ]);

        try {
            \DB::transaction(function() use ($validated) {
                $all = SystemSetting::get('short_courses_testimonials', []);
                $foundIndex = -1;
                foreach ($all as $idx => $t) {
                    if (isset($t['id']) && $t['id'] === $validated['id']) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Testimonial not found.');
                }

                $oldText = $all[$foundIndex]['text'];
                $all[$foundIndex]['text'] = $validated['text'];
                $all[$foundIndex]['moderated_by'] = \Auth::user()->name;
                $all[$foundIndex]['moderated_at'] = now()->toDateTimeString();

                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'moderate_testimonial',
                    'Administrator ' . \Auth::user()->name . ' moderated testimonial from student ' . $all[$foundIndex]['name'],
                    null,
                    ['old_text' => $oldText, 'new_text' => $validated['text']]
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Testimonial moderated and saved successfully.');
    }

    /**
     * Delete an active testimonial by its unique ID.
     */
    public function destroyTestimonial($id)
    {
        try {
            \DB::transaction(function() use ($id) {
                $all = SystemSetting::get('short_courses_testimonials', []);
                $foundIndex = -1;
                foreach ($all as $idx => $t) {
                    if (isset($t['id']) && $t['id'] === $id) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Testimonial not found.');
                }

                $pt = $all[$foundIndex];

                array_splice($all, $foundIndex, 1);
                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'delete_testimonial',
                    'Administrator ' . \Auth::user()->name . ' deleted active testimonial from student ' . ($pt['name'] ?? 'Unknown')
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Testimonial deleted successfully.');
    }

    /**
     * Update/Edit an active testimonial.
     */
    public function updateActiveTestimonial(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'text' => 'required|string|max:1000',
        ]);

        try {
            \DB::transaction(function() use ($validated) {
                $all = SystemSetting::get('short_courses_testimonials', []);
                $foundIndex = -1;
                foreach ($all as $idx => $t) {
                    if (isset($t['id']) && $t['id'] === $validated['id']) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Testimonial not found.');
                }

                $all[$foundIndex]['name'] = $validated['name'];
                $all[$foundIndex]['course'] = $validated['course'];
                $all[$foundIndex]['text'] = $validated['text'];
                $all[$foundIndex]['moderated_by'] = \Auth::user()->name;
                $all[$foundIndex]['moderated_at'] = now()->toDateTimeString();

                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'edit_testimonial',
                    'Administrator ' . \Auth::user()->name . ' edited active testimonial for student ' . $validated['name']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Testimonial updated successfully.');
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

    public function initiateResetRequest(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('ict_administrator')) {
            abort(403, 'Unauthorized. Only ICT Administrators can initiate database reset requests.');
        }

        $activeRequest = SystemSetting::get('database_reset_request');
        if ($activeRequest && $activeRequest['status'] === 'pending') {
            return redirect()->back()->with('error', 'There is already a pending reset request awaiting approval.');
        }
        if ($activeRequest && $activeRequest['status'] === 'approved') {
            return redirect()->back()->with('error', 'A reset request has already been approved and is ready to be executed.');
        }

        $newRequest = [
            'status' => 'pending',
            'requester_id' => $user->id,
            'requester_name' => $user->name,
            'requested_at' => now()->toIso8601String(),
        ];

        SystemSetting::set('database_reset_request', $newRequest);

        // Add to history
        $history = SystemSetting::get('database_reset_history', []);
        $history[] = [
            'id' => uniqid(),
            'requester_name' => $user->name,
            'requested_at' => now()->toIso8601String(),
            'status' => 'pending',
            'approver_name' => null,
            'actioned_at' => null,
            'executed_at' => null,
        ];
        SystemSetting::set('database_reset_history', $history);

        ActivityLog::log('database_reset_requested', 'ICT Administrator initiated a database reset request.', null, [
            'requested_by' => $user->name,
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->back()->with('success', 'Database reset request submitted successfully. Awaiting Executive Director approval.');
    }

    public function actionResetRequest(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('executive_director')) {
            abort(403, 'Unauthorized. Only the Executive Director can review and action database reset requests.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $activeRequest = SystemSetting::get('database_reset_request');
        if (!$activeRequest || $activeRequest['status'] !== 'pending') {
            return redirect()->back()->with('error', 'No pending database reset request was found.');
        }

        $action = $validated['action'];
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

        $activeRequest['status'] = $newStatus;
        $activeRequest['approver_id'] = $user->id;
        $activeRequest['approver_name'] = $user->name;
        $activeRequest['actioned_at'] = now()->toIso8601String();

        if ($newStatus === 'rejected') {
            // If rejected, clear active request to allow initiating a new one
            SystemSetting::set('database_reset_request', null);
        } else {
            SystemSetting::set('database_reset_request', $activeRequest);
        }

        // Update history
        $history = SystemSetting::get('database_reset_history', []);
        if (!empty($history)) {
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if ($history[$i]['status'] === 'pending') {
                    $history[$i]['status'] = $newStatus;
                    $history[$i]['approver_name'] = $user->name;
                    $history[$i]['actioned_at'] = now()->toIso8601String();
                    break;
                }
            }
            SystemSetting::set('database_reset_history', $history);
        }

        ActivityLog::log('database_reset_request_' . $newStatus, 'Executive Director ' . $newStatus . 'd the database reset request.', null, [
            'actioned_by' => $user->name,
            'timestamp' => now()->toIso8601String(),
        ]);

        $msg = ($action === 'approve') 
            ? 'Database reset request has been approved. The ICT Administrator can now execute the reset.'
            : 'Database reset request has been rejected.';

        return redirect()->back()->with('success', $msg);
    }

    public function resetData()
    {
        $user = auth()->user();
        if (!$user->hasRole('ict_administrator')) {
            abort(403, 'Unauthorized. Access restricted to ICT Administrator.');
        }

        $activeRequest = SystemSetting::get('database_reset_request');
        if (!$activeRequest || $activeRequest['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Unauthorized. The database reset has not been approved by the Executive Director.');
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

        // Clear active request
        SystemSetting::set('database_reset_request', null);

        // Update history
        $history = SystemSetting::get('database_reset_history', []);
        if (!empty($history)) {
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if ($history[$i]['status'] === 'approved') {
                    $history[$i]['status'] = 'executed';
                    $history[$i]['executed_at'] = now()->toIso8601String();
                    break;
                }
            }
            SystemSetting::set('database_reset_history', $history);
        }

        ActivityLog::log('system_reset', 'ICT Administrator executed the approved database reset, wiping all clients, service requests, quotations, assignments, and tasks.', null, [
            'performed_by' => $user->name,
            'approved_by'  => $activeRequest['approver_name'] ?? 'Executive Director',
            'ip'           => request()->ip(),
            'timestamp'    => now()->toIso8601String(),
        ]);

        return redirect()->back()->with('success', 'System data reset executed successfully. All client requests, quotations, and assignments have been permanently removed.');
    }

    /**
     * Create/Add a new active testimonial manually from the settings panel.
     */
    public function storeTestimonial(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'text' => 'required|string|max:1000',
        ]);

        try {
            \DB::transaction(function() use ($validated) {
                $active = SystemSetting::get('short_courses_testimonials', []);
                $active[] = [
                    'id' => uniqid(),
                    'name' => $validated['name'],
                    'course' => $validated['course'],
                    'text' => $validated['text'],
                    'status' => 'approved',
                    'submitted_at' => now()->toDateTimeString(),
                ];

                SystemSetting::set('short_courses_testimonials', $active);

                // Audit Trail
                ActivityLog::log(
                    'create_testimonial',
                    'Administrator ' . \Auth::user()->name . ' created a new testimonial for ' . $validated['name']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Testimonial created successfully.');
    }
}

