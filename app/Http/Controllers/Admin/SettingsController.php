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
            if (!$user || !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director', 'secretary', 'admin_assistant', 'coordinator'])) {
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
        $announcements = []; // Obsolete: managed via Notices now.
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

        $emailLogs = $query->orderBy('created_at', 'desc')->paginate(10)->appends(request()->query());

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
            'deliverable_direct_routing' => false,
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

        $documentaries = SystemSetting::get('short_courses_documentaries', []);

        return Inertia::render('Admin/Settings/Index', [
            'units' => $units,
            'sections' => $sections,
            'roles' => $roles,
            'spatieRoles' => $spatieRoles,
            'faqs' => $faqs,
            'testimonials' => $activeTestimonials,
            'pendingTestimonials' => $pendingTestimonials,
            'announcements' => [],
            'contactInfo' => $contactInfo,
            'bankingDetails' => $bankingDetails,
            'emailLogs' => $emailLogs,
            'stats'     => $stats,
            'config'    => $config,
            'filters'   => $request->only(['status', 'search']),
            'documentaries' => $documentaries,
        ]);
    }

    public function updateShortCoursesPortal(Request $request)
    {
        $validated = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.question' => 'required|string|max:255',
            'faqs.*.answer' => 'required|string',
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
        $oldContactInfo = SystemSetting::get('short_courses_contact_info', []);
        $oldBankingDetails = SystemSetting::get('short_courses_banking_details', []);

        if (
            $oldFaqs === $validated['faqs'] &&
            $oldContactInfo === $validated['contactInfo'] &&
            $oldBankingDetails === $validated['bankingDetails']
        ) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        try {
            DB::transaction(function() use ($validated, $oldFaqs, $oldContactInfo, $oldBankingDetails) {
                SystemSetting::set('short_courses_faqs', $validated['faqs']);
                SystemSetting::set('short_courses_contact_info', $validated['contactInfo']);
                SystemSetting::set('short_courses_banking_details', $validated['bankingDetails']);

                // Log settings changes in the audit trail
                ActivityLog::log('update_short_courses_settings', 'Short Courses Public Information Portal settings updated.', null, [
                    'previous' => [
                        'faqs' => $oldFaqs,
                        'contactInfo' => $oldContactInfo,
                        'bankingDetails' => $oldBankingDetails,
                    ],
                    'new' => $validated,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save settings: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Short Courses Public Information Portal settings updated successfully.');
    }

    private function checkRestrictedAccess()
    {
        $user = auth()->user();
        if ($user->hasRole('secretary') || $user->hasRole('admin_assistant') || $user->hasRole('coordinator')) {
            abort(403, 'Unauthorized. Access restricted to Administrators and Directors.');
        }
    }

    public function storeUnit(Request $request)
    {
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
        $section = MsunliSection::findOrFail($id);
        if ($section->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete Section. Active users are currently assigned to it.');
        }

        $section->delete();
        return redirect()->back()->with('success', 'Department/Section deleted.');
    }

    public function storeRole(Request $request)
    {
        $this->checkRestrictedAccess();
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
        $this->checkRestrictedAccess();
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

                $pt = $all[$foundIndex];
                array_splice($all, $foundIndex, 1);

                SystemSetting::set('short_courses_testimonials', $all);

                // Audit Trail
                ActivityLog::log(
                    'reject_testimonial',
                    'Administrator ' . \Auth::user()->name . ' rejected and deleted testimonial from student ' . ($pt['name'] ?? 'Unknown') . ' for course ' . ($pt['course'] ?? 'Unknown')
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
            'deliverable_direct_routing' => 'required|boolean',
        ]);

        $oldConfig = SystemSetting::get('deputy_system_config', []);
        
        if ($oldConfig === $validated) {
            return redirect()->back()->with('error', 'No changes detected. Configuration remains unchanged.');
        }

        try {
            DB::transaction(function() use ($validated, $oldConfig) {
                SystemSetting::set('deputy_system_config', $validated);

                ActivityLog::log('update_settings', 'System configuration settings updated.', null, [
                    'previous' => $oldConfig,
                    'new'      => $validated,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update system configuration: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'System configuration settings updated successfully.');
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

    /**
     * Store a new documentary.
     */
    public function storeDocumentary(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'duration' => 'nullable|string|max:50',
            'thumbnail' => 'required|image|max:10240',
            'video' => 'required|file|max:102400',
        ]);

        try {
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $path = $file->store('documentaries', 'public');
                $thumbnailPath = '/storage/' . $path;
            }

            $videoPath = null;
            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $path = $file->store('documentaries', 'public');
                $videoPath = '/storage/' . $path;
            }

            \DB::transaction(function() use ($validated, $thumbnailPath, $videoPath) {
                $docs = SystemSetting::get('short_courses_documentaries', []);
                $docs[] = [
                    'id' => uniqid(),
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'duration' => $validated['duration'] ?? null,
                    'thumbnail_path' => $thumbnailPath,
                    'video_path' => $videoPath,
                    'is_published' => true,
                    'created_at' => now()->toDateTimeString(),
                ];

                SystemSetting::set('short_courses_documentaries', $docs);

                ActivityLog::log(
                    'create_documentary',
                    'Administrator ' . \Auth::user()->name . ' created documentary: ' . $validated['title']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Documentary uploaded successfully.');
    }

    /**
     * Update an existing documentary.
     */
    public function updateDocumentary(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'duration' => 'nullable|string|max:50',
            'thumbnail' => 'nullable|image|max:10240',
            'video' => 'nullable|file|max:102400',
            'is_published' => 'nullable',
        ]);

        try {
            \DB::transaction(function() use ($validated, $request, $id) {
                $docs = SystemSetting::get('short_courses_documentaries', []);
                $foundIndex = -1;
                foreach ($docs as $idx => $d) {
                    if (isset($d['id']) && $d['id'] === $id) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Documentary not found.');
                }

                $doc = $docs[$foundIndex];

                if ($request->hasFile('thumbnail')) {
                    // Delete old thumbnail if it exists and was uploaded
                    if ($doc['thumbnail_path'] && str_starts_with($doc['thumbnail_path'], '/storage/')) {
                        $oldPath = str_replace('/storage/', '', $doc['thumbnail_path']);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                    }
                    $file = $request->file('thumbnail');
                    $path = $file->store('documentaries', 'public');
                    $doc['thumbnail_path'] = '/storage/' . $path;
                }

                if ($request->hasFile('video')) {
                    // Delete old video if it exists and was uploaded
                    if ($doc['video_path'] && str_starts_with($doc['video_path'], '/storage/')) {
                        $oldPath = str_replace('/storage/', '', $doc['video_path']);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                    }
                    $file = $request->file('video');
                    $path = $file->store('documentaries', 'public');
                    $doc['video_path'] = '/storage/' . $path;
                }

                $doc['title'] = $validated['title'];
                $doc['description'] = $validated['description'];
                $doc['duration'] = $validated['duration'];
                if (isset($request->is_published)) {
                    $doc['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);
                }

                $docs[$foundIndex] = $doc;
                SystemSetting::set('short_courses_documentaries', $docs);

                ActivityLog::log(
                    'update_documentary',
                    'Administrator ' . \Auth::user()->name . ' updated documentary: ' . $validated['title']
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Documentary updated successfully.');
    }

    /**
     * Delete a documentary.
     */
    public function destroyDocumentary($id)
    {
        try {
            \DB::transaction(function() use ($id) {
                $docs = SystemSetting::get('short_courses_documentaries', []);
                $foundIndex = -1;
                foreach ($docs as $idx => $d) {
                    if (isset($d['id']) && $d['id'] === $id) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new \Exception('Documentary not found.');
                }

                $doc = $docs[$foundIndex];

                // Clean up files
                if ($doc['thumbnail_path'] && str_starts_with($doc['thumbnail_path'], '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $doc['thumbnail_path']);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }

                if ($doc['video_path'] && str_starts_with($doc['video_path'], '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $doc['video_path']);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }

                array_splice($docs, $foundIndex, 1);
                SystemSetting::set('short_courses_documentaries', $docs);

                ActivityLog::log(
                    'delete_documentary',
                    'Administrator ' . \Auth::user()->name . ' deleted documentary: ' . ($doc['title'] ?? 'Unknown')
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Documentary deleted successfully.');
    }
}

