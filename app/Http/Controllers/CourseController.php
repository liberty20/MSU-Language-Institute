<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\CourseApplication;
use App\Models\CourseApplicationLog;
use App\Models\Department;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\CourseTimetable;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\CourseCaMark;
use App\Models\SystemSetting;
use App\Notifications\SystemNotification;
use App\Mail\StudentWelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display course management for admins and instructors.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
            abort(403, 'Unauthorized.');
        }

        $courses = Course::with('department')->orderBy('title')->get();
        $intakes = CourseIntake::with(['course', 'instructor'])->orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $instructors = User::where('is_active', true)
            ->whereHas('department', function ($q) {
                $q->where('code', '!=', 'AOS');
            })
            ->with(['department', 'msunliRole'])
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role_name' => $u->msunliRole ? $u->msunliRole->name : 'Staff',
                    'unit_code' => $u->department ? $u->department->code : 'General',
                ];
            });

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'intakes' => $intakes,
            'departments' => $departments,
            'instructors' => $instructors,
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'duration_weeks' => 'required|integer|min:1',
            'is_published' => 'boolean',
        ]);

        Course::create($validated);

        return redirect()->back()->with('success', 'Course created successfully.');
    }

    /**
     * Update an existing course.
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'duration_weeks' => 'required|integer|min:1',
            'is_published' => 'boolean',
        ]);

        $course->update($validated);

        return redirect()->back()->with('success', 'Course updated successfully.');
    }

    /**
     * Schedule a new intake (session) for a course.
     */
    public function storeIntake(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:draft,open,closed,completed',
            'instructor_id' => 'nullable|exists:users,id',
        ]);

        if (!empty($validated['instructor_id'])) {
            $instructor = User::with('department')->find($validated['instructor_id']);
            if (!$instructor || !$instructor->is_active) {
                return redirect()->back()->withErrors(['instructor_id' => 'Selected instructor is inactive, suspended, or archived.']);
            }
            if ($instructor->department && $instructor->department->code === 'AOS') {
                return redirect()->back()->withErrors(['instructor_id' => 'Staff members from Administration and Operations Support cannot be assigned as instructors.']);
            }
        }

        $intake = CourseIntake::create($validated);

        if (!empty($intake->instructor_id)) {
            $instructor = User::find($intake->instructor_id);
            if ($instructor) {
                $instructor->notify(new SystemNotification(
                    'course_assignment',
                    'Course Assignment',
                    'You have been assigned to teach the course "' . $intake->course->title . '" for intake "' . $intake->name . '".',
                    route('instructor.enrollments')
                ));

                ActivityLog::log(
                    'assign_instructor',
                    'Assigned instructor ' . $instructor->name . ' to course ' . $intake->course->title,
                    $intake,
                    [
                        'course_name' => $intake->course->title,
                        'assigned_instructor' => $instructor->name,
                        'assigned_by' => Auth::user()->name,
                        'date_time' => now()->toDateTimeString(),
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Course intake scheduled successfully.');
    }

    /**
     * Update an intake schedule.
     */
    public function updateIntake(Request $request, CourseIntake $intake)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:draft,open,closed,completed',
            'instructor_id' => 'nullable|exists:users,id',
        ]);

        if (!empty($validated['instructor_id'])) {
            $instructor = User::with('department')->find($validated['instructor_id']);
            if (!$instructor || !$instructor->is_active) {
                return redirect()->back()->withErrors(['instructor_id' => 'Selected instructor is inactive, suspended, or archived.']);
            }
            if ($instructor->department && $instructor->department->code === 'AOS') {
                return redirect()->back()->withErrors(['instructor_id' => 'Staff members from Administration and Operations Support cannot be assigned as instructors.']);
            }
        }

        $oldInstructorId = $intake->instructor_id;
        $intake->update($validated);

        if (!empty($intake->instructor_id) && $intake->instructor_id != $oldInstructorId) {
            $instructor = User::find($intake->instructor_id);
            if ($instructor) {
                $instructor->notify(new SystemNotification(
                    'course_assignment',
                    'Course Assignment',
                    'You have been assigned to teach the course "' . $intake->course->title . '" for intake "' . $intake->name . '".',
                    route('instructor.enrollments')
                ));

                ActivityLog::log(
                    'assign_instructor',
                    'Assigned instructor ' . $instructor->name . ' to course ' . $intake->course->title,
                    $intake,
                    [
                        'course_name' => $intake->course->title,
                        'assigned_instructor' => $instructor->name,
                        'assigned_by' => Auth::user()->name,
                        'date_time' => now()->toDateTimeString(),
                    ]
                );
            }
        } elseif (empty($intake->instructor_id) && !empty($oldInstructorId)) {
            $oldInstructor = User::find($oldInstructorId);
            ActivityLog::log(
                'unassign_instructor',
                'Removed instructor ' . ($oldInstructor ? $oldInstructor->name : 'N/A') . ' from course ' . $intake->course->title,
                $intake,
                [
                    'course_name' => $intake->course->title,
                    'removed_instructor' => $oldInstructor ? $oldInstructor->name : 'N/A',
                    'unassigned_by' => Auth::user()->name,
                    'date_time' => now()->toDateTimeString(),
                ]
            );
        }

        return redirect()->back()->with('success', 'Course intake updated successfully.');
    }

    /**
     * List student enrollments for payment verification and certificate issuance.
     */
    public function enrollments()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
            abort(403, 'Unauthorized.');
        }

        $enrollments = CourseEnrollment::with(['user', 'intake.course', 'intake.instructor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Courses/Enrollments', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Verify payment and activate student enrollment.
     */
    public function verifyPayment(Request $request, CourseEnrollment $enrollment)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $enrollment->update([
            'payment_status' => 'verified',
            'amount_paid' => $validated['amount_paid'],
            'enrollment_status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Payment verified and enrollment activated successfully!');
    }

    /**
     * Issue course completion certificate with custom tracking code.
     */
    public function issueCertificate(Request $request, CourseEnrollment $enrollment)
    {
        if ($enrollment->enrollment_status !== 'active') {
            return redirect()->back()->with('error', 'Student must have an active enrollment status to receive a certificate.');
        }

        $certCode = 'CERT-' . strtoupper(uniqid());

        $enrollment->update([
            'enrollment_status' => 'completed',
            'certificate_code' => $certCode,
            'certificate_issued_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Certificate issued successfully! Code: ' . $certCode);
    }

    /**
     * Public page/listing of all published courses for community members.
     */
    public function publicCatalog()
    {
        $courses = Course::with('department')
            ->where('is_published', true)
            ->get();

        $intakes = CourseIntake::with(['course', 'instructor'])
            ->where('status', 'open')
            ->get();

        return Inertia::render('Courses/Catalog', [
            'courses' => $courses,
            'intakes' => $intakes,
            'canLogin' => route('login'),
        ]);
    }

    /**
     * Public page of the Short Courses Information Portal.
     */
    public function publicPortal()
    {
        $courses = Course::with('department')
            ->where('is_published', true)
            ->get();

        $intakes = CourseIntake::with(['course', 'instructor', 'enrollments'])
            ->where('status', '!=', 'draft')
            ->get();

        $faqs = SystemSetting::get('short_courses_faqs', []);
        $testimonials = SystemSetting::get('short_courses_testimonials', []);
        $announcements = SystemSetting::get('short_courses_announcements', []);
        $contactInfo = SystemSetting::get('short_courses_contact_info', [
            'email' => 'language.institute@msu.ac.zw',
            'phone' => '+263 54 2260331',
            'mobile' => '+263 772 123 456',
            'location' => 'MSU Gweru Main Campus, Gweru, Zimbabwe',
            'hours' => 'Monday - Friday: 8:00 AM - 4:30 PM'
        ]);
        $bankingDetails = SystemSetting::get('short_courses_banking_details', [
            'account_name' => 'Midlands State University National Language Institute',
            'bank' => 'CBZ Bank',
            'branch' => 'Gweru Branch',
            'account_number' => '01223456789012',
            'nostro_number' => '01223456789099',
            'type' => 'Current Account',
            'currency_accepted' => 'USD, ZiG'
        ]);

        return Inertia::render('Courses/PublicPortal', [
            'courses' => $courses,
            'intakes' => $intakes,
            'faqs' => $faqs,
            'testimonials' => $testimonials,
            'pendingTestimonials' => SystemSetting::get('short_courses_pending_testimonials', []),
            'announcements' => $announcements,
            'contactInfo' => $contactInfo,
            'bankingDetails' => $bankingDetails,
            'canLogin' => \Route::has('login'),
        ]);
    }

    /**
     * Handle enrollment submission from a client/student.
     */
    public function enroll(Request $request)
    {
        $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'payment_proof' => 'required|file|max:10240', // max 10MB
        ]);

        $user = Auth::user();
        if (!$user) {
            abort(401, 'Please log in to enroll.');
        }

        $intake = CourseIntake::findOrFail($request->course_intake_id);
        
        // Check capacity
        $currentEnrollmentsCount = CourseEnrollment::where('course_intake_id', $intake->id)->count();
        if ($currentEnrollmentsCount >= $intake->capacity) {
            return redirect()->back()->with('error', 'Sorry, this intake has reached its maximum enrollment capacity.');
        }

        // Store proof of payment
        $filePath = null;
        if ($request->hasFile('payment_proof')) {
            $filePath = $request->file('payment_proof')->store('payments/proofs', 'public');
        }

        CourseEnrollment::create([
            'course_intake_id' => $intake->id,
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'payment_proof_path' => $filePath,
            'amount_paid' => 0.00,
            'enrollment_status' => 'pending',
        ]);

        return redirect()->route('student.courses')->with('success', 'Your enrollment has been submitted successfully! Please wait for admin verification of your payment.');
    }

    /**
     * Student course catalog dashboard.
     */
    public function studentDashboard()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthorized.');
        }

        // Enforce that clients cannot access student dashboard
        if ($user->hasRole('client')) {
            abort(403, 'Unauthorized. Clients cannot access student modules.');
        }

        $enrollments = CourseEnrollment::with(['intake.course', 'intake.instructor'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Courses/StudentDashboard', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Submit registration application for a short course.
     */
    public function submitApplication(Request $request)
    {
        $validated = $request->validate([
            'course_intake_id'      => 'required|exists:course_intakes,id',
            'full_name'             => 'required|string|max:255',
            'national_id_number'    => 'required|string|max:50',
            'national_id_copy'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'email'                 => 'required|email|max:255',
            'phone'                 => 'required|string|max:30',
            'physical_address'      => 'required|string',
            'payment_proof'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Check if an application already exists for this intake and email
        $exists = CourseApplication::where('course_intake_id', $validated['course_intake_id'])
            ->where('email', $validated['email'])
            ->whereIn('status', ['pending', 'verified', 'recommended', 'approved'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'An application has already been submitted with this email for this intake.');
        }

        // Check if user account with this email already exists and is not a student
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && !$existingUser->hasRole('student')) {
            return redirect()->back()->with('error', 'A user account with this email already exists and is registered under a different role.');
        }

        // Check intake capacity
        $intake = CourseIntake::findOrFail($validated['course_intake_id']);
        $currentApplications = CourseApplication::where('course_intake_id', $intake->id)
            ->where('status', 'approved')
            ->count();
        if ($currentApplications >= $intake->capacity) {
            return redirect()->back()->with('error', 'Sorry, this intake has reached its maximum enrollment capacity.');
        }

        // Store secure files
        $idCopyPath = $request->file('national_id_copy')->store('course_applications/id_copies', 'public');
        $paymentProofPath = $request->file('payment_proof')->store('course_applications/payment_proofs', 'public');

        $application = CourseApplication::create([
            'course_intake_id'      => $validated['course_intake_id'],
            'full_name'             => $validated['full_name'],
            'national_id_number'    => $validated['national_id_number'],
            'national_id_copy_path' => $idCopyPath,
            'email'                 => $validated['email'],
            'phone'                 => $validated['phone'],
            'physical_address'      => $validated['physical_address'],
            'payment_proof_path'    => $paymentProofPath,
            'status'                => 'pending',
        ]);

        // Log the activity
        $systemUser = User::whereIn('email', ['admin@msunli.edu', 'executive.director@msunli.edu'])->first();
        CourseApplicationLog::create([
            'course_application_id' => $application->id,
            'user_id'               => $systemUser ? $systemUser->id : 1,
            'action'                => 'submitted',
            'comment'               => 'Registration application submitted successfully.',
        ]);

        return redirect()->back()->with('success', 'Your application has been submitted successfully! It is now being reviewed by the Administrative Assistant and ICT Administrator.');
    }

    /**
     * View applications list (Admins & Reviewers)
     */
    public function applicationsList(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant'])) {
            abort(403, 'Unauthorized.');
        }

        $query = CourseApplication::with(['intake.course']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Courses/Applications', [
            'applications' => $applications,
            'filters'      => [
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * View details of an application (Admins & Reviewers)
     */
    public function applicationDetails($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant'])) {
            abort(403, 'Unauthorized.');
        }

        $application = CourseApplication::with(['intake.course', 'logs.user'])->findOrFail($id);

        return Inertia::render('Courses/ApplicationDetails', [
            'application' => $application,
        ]);
    }

    /**
     * Step 1: Verification (Administrative Assistant & ICT Administrator)
     */
    public function verifyApplication(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['ict_administrator', 'admin_assistant'])) {
            return redirect()->back()->with('error', 'Unauthorized. Only Administrative Assistant or ICT Administrator can verify applications.');
        }

        $request->validate([
            'temporary_password' => 'required|string|min:6',
            'comment'            => 'nullable|string',
        ]);

        try {
            $application = CourseApplication::findOrFail($id);
            if ($application->status !== 'pending') {
                return redirect()->back()->with('error', 'This application cannot be verified as it is currently in status: ' . $application->status);
            }

            $application->update([
                'status'              => 'verified',
                'verification_status' => 'verified',
                'temporary_password'  => $request->temporary_password,
                'verified_by'         => $user->id,
                'verified_at'         => now(),
            ]);

            CourseApplicationLog::create([
                'course_application_id' => $application->id,
                'user_id'               => $user->id,
                'action'                => 'verified',
                'comment'               => $request->comment ?? 'Application verified and temporary password assigned.',
            ]);

            // Add activity log
            \App\Models\ActivityLog::create([
                'user_id'       => $user->id,
                'action'        => 'verified_application',
                'subject_type'  => CourseApplication::class,
                'subject_id'    => $application->id,
                'description'   => 'Verified application for ' . $application->full_name . ' and forwarded to Deputy Director.',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent()
            ]);

            return redirect()->route('course-applications.index')->with('success', 'Application verified successfully and forwarded to the Deputy Director.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Course application not found with ID: ' . $id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to verify application: ' . $e->getMessage());
        }
    }

    /**
     * Step 2: Recommendation (Deputy Director)
     */
    public function recommendApplication(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('deputy_director')) {
            abort(403, 'Unauthorized. Only the Deputy Director can recommend applications.');
        }

        $request->validate([
            'comment' => 'nullable|string',
        ]);

        $application = CourseApplication::findOrFail($id);
        if ($application->status !== 'verified') {
            return redirect()->back()->with('error', 'This application cannot be recommended as it is currently in status: ' . $application->status);
        }

        $application->update([
            'status'                => 'recommended',
            'recommendation_status' => 'recommended',
            'recommended_by'        => $user->id,
            'recommended_at'        => now(),
        ]);

        CourseApplicationLog::create([
            'course_application_id' => $application->id,
            'user_id'               => $user->id,
            'action'                => 'recommended',
            'comment'               => $request->comment ?? 'Application recommended for approval.',
        ]);

        ActivityLog::log(
            'recommended_application',
            'Recommended application for ' . $application->full_name . ' to Director.',
            $application,
            [
                'old_value' => 'verified',
                'new_value' => 'recommended',
                'comment'   => $request->comment ?? ''
            ]
        );

        return redirect()->back()->with('success', 'Application recommended successfully! Forwarded to the Director.');
    }

    /**
     * Step 3: Final Approval (Director / Executive Director)
     */
    public function approveApplication(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('executive_director')) {
            abort(403, 'Unauthorized. Only the Director can approve applications.');
        }

        $request->validate([
            'comment' => 'nullable|string',
        ]);

        $application = CourseApplication::with('intake.course')->findOrFail($id);

        // Idempotency: If already approved or enrolled, complete successfully without throwing validation errors.
        if ($application->status === 'approved' || $application->status === 'enrolled') {
            return redirect()->back()->with('success', 'This enrollment application has already been successfully approved and activated.');
        }

        if ($application->status !== 'recommended') {
            return redirect()->back()->with('error', 'This application cannot be approved as it is currently in status: ' . $application->status);
        }

        try {
            // Ensure all workflow transitions are transaction-safe
            \DB::transaction(function () use ($application, $user, $request) {
                // 1. Update application status to 'approved'
                $application->update([
                    'status'          => 'approved',
                    'approval_status' => 'approved',
                    'approved_by'     => $user->id,
                    'approved_at'     => now(),
                ]);

                // Create audit log for approval transition
                CourseApplicationLog::create([
                    'course_application_id' => $application->id,
                    'user_id'               => $user->id,
                    'action'                => 'approved',
                    'comment'               => $request->comment ?? 'Application approved by Director.',
                ]);

                ActivityLog::log(
                    'approved_application',
                    'Approved application for ' . $application->full_name . ' and enrolled student.',
                    $application,
                    [
                        'old_value' => 'recommended',
                        'new_value' => 'approved',
                        'comment'   => $request->comment ?? ''
                    ]
                );

                // 2. Create or fetch student User account
                $studentUser = User::where('email', $application->email)->first();
                if (!$studentUser) {
                    $studentUser = User::create([
                        'name'      => $application->full_name,
                        'email'     => $application->email,
                        'phone'     => $application->phone,
                        'password'  => Hash::make($application->temporary_password),
                        'is_active' => true,
                    ]);
                    $studentUser->assignRole('student');
                } else {
                    if (!$studentUser->hasRole('student')) {
                        $studentUser->assignRole('student');
                    }
                }

                // 3. Create the CourseEnrollment record
                CourseEnrollment::updateOrCreate(
                    [
                        'course_intake_id' => $application->course_intake_id,
                        'user_id'          => $studentUser->id,
                    ],
                    [
                        'payment_status'     => 'verified',
                        'payment_proof_path' => $application->payment_proof_path,
                        'amount_paid'        => $application->intake->course->price,
                        'enrollment_status'  => 'active',
                    ]
                );

                // 4. Update application status to 'enrolled' to complete the lifecycle
                $application->update([
                    'status' => 'enrolled',
                ]);

                // Create audit log for enrollment completion transition
                CourseApplicationLog::create([
                    'course_application_id' => $application->id,
                    'user_id'               => $user->id,
                    'action'                => 'enrolled',
                    'comment'               => 'Student account activated and enrolled into course intake.',
                ]);
            });

            // 5. Send automated email notification (outside database transaction to prevent mail-server lag from locking database tables)
            $emailSentAlready = CourseApplicationLog::where('course_application_id', $application->id)
                ->where('action', 'email_dispatched')
                ->exists();

            if (!$emailSentAlready) {
                try {
                    // SMTP/Mail Configuration Validation
                    $smtpHost = config('mail.mailers.smtp.host') ?: config('mail.host');
                    if (empty($smtpHost) && config('mail.default') === 'smtp') {
                        throw new \Exception("Mail SMTP host configuration is not defined.");
                    }

                    Mail::to($application->email)->send(new StudentWelcomeMail(
                        $application,
                        $application->intake->course->title,
                        $application->temporary_password
                    ));

                    // Log outgoing email activity for audit/troubleshooting
                    \Log::info("Acceptance welcome email successfully sent to student: " . $application->email);

                    CourseApplicationLog::create([
                        'course_application_id' => $application->id,
                        'user_id'               => $user->id,
                        'action'                => 'email_dispatched',
                        'comment'               => 'Automated student welcome and login credentials email dispatched to ' . $application->email,
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Failed to send welcome email to ' . $application->email . ': ' . $e->getMessage());

                    CourseApplicationLog::create([
                        'course_application_id' => $application->id,
                        'user_id'               => $user->id,
                        'action'                => 'email_failed',
                        'comment'               => 'Failed to dispatch acceptance email: ' . $e->getMessage(),
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Application approved successfully! Student account activated and enrolled.');

        } catch (\Exception $transactionEx) {
            \Log::error('Database transaction failed during application approval workflow for ID ' . $id . ': ' . $transactionEx->getMessage());
            return redirect()->back()->with('error', 'Workflow transaction error: Failed to approve application. ' . $transactionEx->getMessage());
        }
    }

    /**
     * Reject Application
     */
    public function rejectApplication(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant'])) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $application = CourseApplication::findOrFail($id);
        if (in_array($application->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'This application cannot be rejected as it is already ' . $application->status);
        }

        $oldStatus = $application->status;

        $application->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        CourseApplicationLog::create([
            'course_application_id' => $application->id,
            'user_id'               => $user->id,
            'action'                => 'rejected',
            'comment'               => 'Application rejected: ' . $request->rejection_reason,
        ]);

        ActivityLog::log(
            'rejected_application',
            'Rejected application for ' . $application->full_name . ' due to: ' . $request->rejection_reason,
            $application,
            [
                'old_value' => $oldStatus,
                'new_value' => 'rejected',
                'comment'   => $request->rejection_reason
            ]
        );

        return redirect()->back()->with('success', 'Application rejected successfully.');
    }
}
