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
use Barryvdh\DomPDF\Facade\Pdf;

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
            ->where(function ($query) {
                $query->whereNull('department_id')
                      ->orWhereHas('department', function ($q) {
                          $q->where('code', '!=', 'AOS');
                      });
            })
            ->where(function ($q) {
                $q->whereDoesntHave('roles')
                  ->orWhereHas('roles', function ($roleQuery) {
                      $roleQuery->whereNotIn('name', [
                          'client',
                          'student',
                          'executive_director',
                          'deputy_director',
                          'ict_administrator',
                          'admin_assistant',
                          'secretary',
                          'receptionist'
                      ]);
                  });
            })
            ->with(['department', 'msunliRole'])
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role_name' => $u->msunliRole ? $u->msunliRole->name : ucwords(str_replace('_', ' ', $u->role_name ?? 'Staff')),
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

        $course->fill($validated);
        if (!$course->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $course->save();

        return redirect()->back()->with('success', 'Course updated successfully.');
    }

    /**
     * Delete an existing course offering.
     */
    public function destroy(Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator'])) {
            abort(403, 'Unauthorized. Only directors and administrators can delete courses.');
        }

        // Validation: check if the course has any intakes
        if ($course->intakes()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete course. It has scheduled intakes.');
        }

        $course->delete();

        ActivityLog::log(
            'course_deleted',
            'Deleted course ' . $course->title . ' (' . $course->code . ')',
            null,
            [
                'course_title' => $course->title,
                'course_code' => $course->code,
                'deleted_by' => $user->name,
            ]
        );

        return redirect()->back()->with('success', 'Course deleted successfully.');
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

        $exists = CourseIntake::where('course_id', $validated['course_id'])
            ->where('name', $validated['name'])
            ->whereDate('start_date', $validated['start_date'])
            ->whereDate('end_date', $validated['end_date'])
            ->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'A course intake with the same course, name, start date, and end date already exists.'])->withInput();
        }

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

        $exists = CourseIntake::where('course_id', $intake->course_id)
            ->where('name', $validated['name'])
            ->whereDate('start_date', $validated['start_date'])
            ->whereDate('end_date', $validated['end_date'])
            ->where('id', '!=', $intake->id)
            ->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'A course intake with the same course, name, start date, and end date already exists.'])->withInput();
        }

        $intake->fill($validated);
        if (!$intake->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        if (!empty($validated['instructor_id'])) {
            $instructor = User::with('department')->find($validated['instructor_id']);
            if (!$instructor || !$instructor->is_active) {
                return redirect()->back()->withErrors(['instructor_id' => 'Selected instructor is inactive, suspended, or archived.']);
            }
            if ($instructor->department && $instructor->department->code === 'AOS') {
                return redirect()->back()->withErrors(['instructor_id' => 'Staff members from Administration and Operations Support cannot be assigned as instructors.']);
            }
        }

        $oldInstructorId = $intake->getOriginal('instructor_id');
        $intake->save();

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
     * List student enrollments for payment verification and certificate issuance with advanced analytics.
     */
    public function enrollments(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
            abort(403, 'Unauthorized.');
        }

        $filteredIntakes = $this->getProcessedIntakesData($request);

        // Calculate summary cards
        $totalCoursesCount = $filteredIntakes->pluck('course_code')->unique()->count();
        $totalEnrolledCount = $filteredIntakes->sum('total_enrolled');
        $totalInstructorsCount = $filteredIntakes->where('assigned_instructor', '!=', 'N/A')->pluck('assigned_instructor')->unique()->count();
        $totalAssessedOverall = $filteredIntakes->sum('students_assessed');
        $totalPassedOverall = $filteredIntakes->sum('students_passed');
        $overallPassRate = $totalAssessedOverall > 0 ? round(($totalPassedOverall / $totalAssessedOverall) * 100, 2) : 0.00;
        $coursesInProgressCount = $filteredIntakes->where('course_status', 'Ongoing')->count();
        $completedCoursesCount = $filteredIntakes->where('course_status', 'Completed')->count();

        $summaryCards = [
            'total_courses' => $totalCoursesCount,
            'total_enrolled_students' => $totalEnrolledCount,
            'total_assigned_instructors' => $totalInstructorsCount,
            'overall_pass_rate' => $overallPassRate,
            'courses_in_progress' => $coursesInProgressCount,
            'completed_courses' => $completedCoursesCount,
        ];

        $students = User::where('primary_category', 'Student')->orderBy('name')->get(['id', 'name', 'email']);
        $activeIntakes = CourseIntake::with('course')->whereIn('status', ['open', 'ongoing'])->get();

        return Inertia::render('Courses/Enrollments', [
            'intakes' => $filteredIntakes,
            'summaryCards' => $summaryCards,
            'filters' => $request->all(),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'studentUsers' => $students,
            'activeIntakes' => $activeIntakes,
        ]);
    }

    /**
     * Manually enroll a student (new or existing) into a course intake.
     */
    public function manualEnroll(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->hasAnyRole(['ict_administrator', 'admin_assistant'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required_without:user_id|nullable|string|max:255',
            'email' => 'required_without:user_id|nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'payment_status' => 'nullable|string|in:pending,verified,failed',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $intake = CourseIntake::with('course')->findOrFail($validated['course_intake_id']);

        // Check capacity
        $currentEnrollmentsCount = CourseEnrollment::where('course_intake_id', $intake->id)->count();
        if ($currentEnrollmentsCount >= $intake->capacity) {
            return redirect()->back()->with('error', 'Sorry, this intake has reached its maximum enrollment capacity.');
        }

        $user = null;
        if (!empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        } elseif (!empty($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        }

        if (!$user) {
            $tempPassword = 'Password123!';
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($tempPassword),
                'is_active' => true,
            ]);
            $user->assignRole('student');
        } else {
            // Check for category conflict: only allow student category or users with no roles
            $roles = $user->roles->pluck('name')->toArray();
            if (!empty($roles) && !in_array('student', $roles)) {
                return redirect()->back()->with('error', 'Only users of the Student primary category can be enrolled in course intakes.');
            }
            if (!$user->hasRole('student')) {
                $user->assignRole('student');
            }
        }

        // Ensure user has Student category
        if ($user->primary_category !== 'Student') {
            $user->updatePrimaryCategory();
            if ($user->primary_category !== 'Student') {
                return redirect()->back()->with('error', 'Only users of the Student primary category can be enrolled in course intakes.');
            }
        }

        // Check if already enrolled
        $exists = CourseEnrollment::where('user_id', $user->id)
            ->where('course_intake_id', $intake->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Student is already enrolled in this course intake.');
        }

        $paymentStatus = $validated['payment_status'] ?? 'verified';
        $amountPaid = $validated['amount_paid'] ?? $intake->course->price;
        $enrollmentStatus = ($paymentStatus === 'verified') ? 'active' : 'pending';

        $enrollment = CourseEnrollment::create([
            'course_intake_id' => $intake->id,
            'user_id' => $user->id,
            'payment_status' => $paymentStatus,
            'amount_paid' => $amountPaid,
            'enrollment_status' => $enrollmentStatus,
        ]);

        // Audit Trail Log
        ActivityLog::log(
            'manual_enrollment',
            'Manually enrolled student ' . $user->name . ' into course intake ' . $intake->name . ' (' . $intake->course->title . ').',
            $enrollment,
            [
                'intake_id' => $intake->id,
                'student_id' => $user->id,
                'student_name' => $user->name,
                'enrolled_by' => $authUser->name,
                'date_time' => now()->toDateTimeString(),
            ]
        );

        return redirect()->back()->with('success', 'Student manually enrolled successfully.');
    }


    /**
     * Helper to retrieve, compute, and filter course intakes and performance metrics.
     */
    private function getProcessedIntakesData(Request $request)
    {
        $allIntakes = CourseIntake::with([
            'course.department',
            'instructor.department',
            'instructor.msunliRole',
            'enrollments.user',
            'enrollments.collectedBy',
            'assignments.submissions',
            'caMarks',
        ])->get();

        $today = now()->toDateString();

        $processed = $allIntakes->map(function ($intake) use ($today) {
            // enrollment stats
            $totalEnrolled = $intake->enrollments->count();
            $activeCount = $intake->enrollments->where('enrollment_status', 'active')->count();
            $completedCount = $intake->enrollments->where('enrollment_status', 'completed')->count();
            $droppedCount = $intake->enrollments->where('enrollment_status', 'dropped')->count();

            // instructor details
            $instructor = $intake->instructor;
            $instructorDetails = $instructor ? [
                'name' => $instructor->name,
                'email' => $instructor->email,
                'role' => $instructor->msunliRole ? $instructor->msunliRole->name : 'Instructor',
                'unit' => $instructor->department ? $instructor->department->name : 'N/A',
                'unit_code' => $instructor->department ? $instructor->department->code : '',
            ] : null;

            // marks and pass rate
            $studentsList = [];
            $totalAssessed = 0;
            $totalPassed = 0;
            $totalFailed = 0;

            $assignments = $intake->assignments;
            $caMarks = $intake->caMarks->groupBy('user_id');

            foreach ($intake->enrollments as $enrollment) {
                $student = $enrollment->user;
                if (!$student) continue;

                $studentMarks = $caMarks->get($student->id, collect());
                
                $totalObtained = 0;
                $totalPossible = 0;

                // Add up assignment scores
                foreach ($assignments as $asg) {
                    $sub = $asg->submissions->firstWhere('user_id', $student->id);
                    if ($sub && $sub->status === 'graded') {
                        $totalObtained += $sub->marks_obtained;
                        $totalPossible += $asg->max_marks;
                    }
                }

                // Add up CA marks
                foreach ($studentMarks as $mark) {
                    $totalObtained += $mark->marks_obtained;
                    $totalPossible += $mark->max_marks;
                }

                $averageMark = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : null;
                
                $isAssessed = ($totalPossible > 0);
                $passFailStatus = 'N/A';
                if ($isAssessed) {
                    $totalAssessed++;
                    if ($averageMark >= 50) {
                        $totalPassed++;
                        $passFailStatus = 'Pass';
                    } else {
                        $totalFailed++;
                        $passFailStatus = 'Fail';
                    }
                }

                $regNumber = 'REG-' . ($student->created_at ? $student->created_at->format('Y') : date('Y')) . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT);

                $studentsList[] = [
                    'id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'reg_number' => $regNumber,
                    'enrollment_date' => $enrollment->created_at->format('Y-m-d'),
                    'status' => $enrollment->enrollment_status,
                    'payment_status' => $enrollment->payment_status,
                    'payment_proof_path' => $enrollment->payment_proof_path,
                    'amount_paid' => $enrollment->amount_paid,
                    'certificate_code' => $enrollment->certificate_code,
                    'certificate_issued_at' => $enrollment->certificate_issued_at ? $enrollment->certificate_issued_at->format('Y-m-d') : null,
                    'certificate_collected_at' => $enrollment->certificate_collected_at ? $enrollment->certificate_collected_at->format('Y-m-d H:i:s') : null,
                    'collected_by_name' => $enrollment->collectedBy ? $enrollment->collectedBy->name : null,
                    'average_mark' => $averageMark,
                    'pass_fail_status' => $passFailStatus,
                ];
            }

            $passRate = $totalAssessed > 0 ? round(($totalPassed / $totalAssessed) * 100, 2) : 0.00;

            $rating = 'Fair';
            if ($passRate >= 90) {
                $rating = 'Excellent';
            } elseif ($passRate >= 75) {
                $rating = 'Good';
            } elseif ($passRate >= 50) {
                $rating = 'Fair';
            } else {
                $rating = 'Needs Attention';
            }

            // Display status
            $displayStatus = 'Closed';
            if ($intake->status === 'completed') {
                $displayStatus = 'Completed';
            } elseif ($intake->status === 'draft') {
                $displayStatus = 'Draft';
            } elseif ($intake->status === 'open') {
                if ($intake->start_date && $intake->start_date->toDateString() > $today) {
                    $displayStatus = 'Open';
                } else {
                    $displayStatus = 'Ongoing';
                }
            } elseif ($intake->status === 'closed') {
                if ($intake->end_date && $intake->end_date->toDateString() < $today) {
                    $displayStatus = 'Completed';
                } else {
                    $displayStatus = 'Ongoing';
                }
            }

            return [
                'id' => $intake->id,
                'course_name' => $intake->course->title,
                'course_code' => $intake->course->code,
                'assigned_instructor' => $instructor ? $instructor->name : 'N/A',
                'intake_name' => $intake->name,
                'start_date' => $intake->start_date ? $intake->start_date->format('Y-m-d') : 'N/A',
                'end_date' => $intake->end_date ? $intake->end_date->format('Y-m-d') : 'N/A',
                'course_status' => $displayStatus,
                
                // Enrollment Statistics
                'total_enrolled' => $totalEnrolled,
                'active_students' => $activeCount,
                'completed_students' => $completedCount,
                'withdrawn_students' => $droppedCount,

                // Instructor Details
                'instructor_info' => $instructorDetails,

                // Pass Rate Analytics
                'students_assessed' => $totalAssessed,
                'students_passed' => $totalPassed,
                'students_failed' => $totalFailed,
                'pass_rate' => $passRate,
                'performance_rating' => $rating,

                // Student List
                'students' => $studentsList,
            ];
        });

        // Apply filters
        $filtered = $processed;

        if ($request->filled('course')) {
            $search = strtolower($request->course);
            $filtered = $filtered->filter(function ($item) use ($search) {
                return strpos(strtolower($item['course_name']), $search) !== false || 
                       strpos(strtolower($item['course_code']), $search) !== false;
            });
        }

        if ($request->filled('instructor')) {
            $search = strtolower($request->instructor);
            $filtered = $filtered->filter(function ($item) use ($search) {
                return strpos(strtolower($item['assigned_instructor']), $search) !== false;
            });
        }

        if ($request->filled('unit')) {
            $search = strtolower($request->unit);
            $filtered = $filtered->filter(function ($item) use ($search) {
                return $item['instructor_info'] && (
                    strpos(strtolower($item['instructor_info']['unit']), $search) !== false ||
                    strpos(strtolower($item['instructor_info']['unit_code']), $search) !== false
                );
            });
        }

        if ($request->filled('intake')) {
            $search = strtolower($request->intake);
            $filtered = $filtered->filter(function ($item) use ($search) {
                return strpos(strtolower($item['intake_name']), $search) !== false;
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $filtered = $filtered->filter(function ($item) use ($status) {
                return strtolower($item['course_status']) === $status;
            });
        }

        if ($request->filled('min_pass_rate')) {
            $minRate = (float) $request->min_pass_rate;
            $filtered = $filtered->filter(function ($item) use ($minRate) {
                return $item['pass_rate'] >= $minRate;
            });
        }

        if ($request->filled('max_pass_rate')) {
            $maxRate = (float) $request->max_pass_rate;
            $filtered = $filtered->filter(function ($item) use ($maxRate) {
                return $item['pass_rate'] <= $maxRate;
            });
        }

        if ($request->filled('start_date')) {
            $startDate = $request->start_date;
            $filtered = $filtered->filter(function ($item) use ($startDate) {
                return $item['start_date'] !== 'N/A' && $item['start_date'] >= $startDate;
            });
        }

        if ($request->filled('end_date')) {
            $endDate = $request->end_date;
            $filtered = $filtered->filter(function ($item) use ($endDate) {
                return $item['end_date'] !== 'N/A' && $item['end_date'] <= $endDate;
            });
        }

        return $filtered->values();
    }

    /**
     * Export dynamic reports.
     */
    public function exportReport(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'report_type' => 'required|in:enrollment,instructor_allocation,course_performance,pass_rate_analysis',
            'format' => 'required|in:pdf,excel,csv',
        ]);

        $reportType = $request->report_type;
        $format = $request->format;

        $filteredIntakes = $this->getProcessedIntakesData($request);

        if ($format === 'pdf') {
            return $this->generatePdfReport($reportType, $filteredIntakes);
        } else {
            return $this->generateCsvReport($reportType, $filteredIntakes, $format);
        }
    }

    /**
     * Export student enrollment list for a specific course intake.
     */
    public function exportCourseStudents($intakeId, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'])) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $format = $request->format;
        $intake = CourseIntake::with(['course', 'instructor'])->findOrFail($intakeId);

        // Fetch students enrolled in this intake
        $enrollments = CourseEnrollment::has('user')
            ->with('user')
            ->where('course_intake_id', $intake->id)
            ->get();

        if ($format === 'pdf') {
            $data = [
                'title' => 'Enrollment List - ' . $intake->course->title,
                'intake' => $intake,
                'enrollments' => $enrollments,
                'generated_by' => $user->name,
                'generated_date' => now()->format('F d, Y h:i A'),
            ];

            $pdf = Pdf::loadView('reports.course_students_pdf', $data);
            $filename = 'students_list_' . strtolower(str_replace(' ', '_', $intake->course->code)) . '_' . time() . '.pdf';
            return $pdf->download($filename);
        } else {
            // CSV / Excel
            $filename = 'students_list_' . strtolower(str_replace(' ', '_', $intake->course->code)) . '_' . time() . '.' . ($format === 'excel' ? 'xlsx' : 'csv');
            
            // For Excel / CSV, we will output CSV format (since Excel format option also streams text/csv)
            $contentType = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';
            
            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($enrollments, $intake) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['MSU National Language Institute - Class Enrollment List']);
                fputcsv($file, ['Course', $intake->course->title . ' (' . $intake->course->code . ')']);
                fputcsv($file, ['Intake Batch', $intake->name]);
                fputcsv($file, ['Total Enrolled', $enrollments->count()]);
                fputcsv($file, []); // Blank line

                fputcsv($file, ['Student Name', 'Email Address', 'Phone Number', 'Registration Number', 'Enrolled Date', 'Payment Status', 'Enrollment Status']);

                foreach ($enrollments as $e) {
                    if (!$e->user) continue;
                    $regNumber = 'REG-' . ($e->user->created_at ? $e->user->created_at->format('Y') : date('Y')) . '-' . str_pad($e->user->id, 4, '0', STR_PAD_LEFT);
                    fputcsv($file, [
                        $e->user->name,
                        $e->user->email,
                        $e->user->phone ?? 'N/A',
                        $regNumber,
                        $e->created_at->format('Y-m-d'),
                        ucfirst($e->payment_status),
                        ucfirst($e->enrollment_status),
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    /**
     * Helper to generate PDF using dompdf
     */
    private function generatePdfReport($reportType, $intakes)
    {
        $title = 'Short Course ' . ucwords(str_replace('_', ' ', $reportType)) . ' Report';
        $data = [
            'title' => $title,
            'report_type' => $reportType,
            'intakes' => $intakes->toArray(),
            'generated_by' => Auth::user()->name,
            'generated_date' => now()->format('F d, Y h:i A'),
        ];

        $pdf = Pdf::loadView('reports.course_enrollments_pdf', $data);
        $filename = 'course_' . $reportType . '_report_' . time() . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Helper to generate CSV/Excel streamed reports
     */
    private function generateCsvReport($reportType, $intakes, $format)
    {
        $filename = 'course_' . $reportType . '_report_' . time() . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reportType, $intakes) {
            $file = fopen('php://output', 'w');

            if ($reportType === 'enrollment') {
                fputcsv($file, ['MSU National Language Institute - Course Enrollment Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);
                fputcsv($file, ['Course Name', 'Course Code', 'Intake Name', 'Student Name', 'Reg Number', 'Email', 'Enrollment Date', 'Enrollment Status', 'Payment Status', 'Amount Paid']);

                foreach ($intakes as $intake) {
                    foreach ($intake['students'] as $student) {
                        fputcsv($file, [
                            $intake['course_name'],
                            $intake['course_code'],
                            $intake['intake_name'],
                            $student['name'],
                            $student['reg_number'],
                            $student['email'],
                            $student['enrollment_date'],
                            ucfirst($student['status']),
                            ucfirst($student['payment_status']),
                            $student['amount_paid']
                        ]);
                    }
                }
            } elseif ($reportType === 'instructor_allocation') {
                fputcsv($file, ['MSU National Language Institute - Instructor Allocation Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);
                fputcsv($file, ['Course Name', 'Course Code', 'Intake Name', 'Instructor Name', 'Instructor Email', 'Role', 'Unit', 'Start Date', 'End Date', 'Status']);

                foreach ($intakes as $intake) {
                    $inst = $intake['instructor_info'];
                    fputcsv($file, [
                        $intake['course_name'],
                        $intake['course_code'],
                        $intake['intake_name'],
                        $inst ? $inst['name'] : 'N/A',
                        $inst ? $inst['email'] : 'N/A',
                        $inst ? $inst['role'] : 'N/A',
                        $inst ? $inst['unit'] : 'N/A',
                        $intake['start_date'],
                        $intake['end_date'],
                        $intake['course_status']
                    ]);
                }
            } elseif ($reportType === 'course_performance') {
                fputcsv($file, ['MSU National Language Institute - Course Performance Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);
                fputcsv($file, ['Course Name', 'Course Code', 'Intake Name', 'Instructor Name', 'Total Enrolled', 'Active', 'Completed', 'Assessed Students', 'Passed Students', 'Failed Students', 'Pass Rate (%)', 'Rating']);

                foreach ($intakes as $intake) {
                    fputcsv($file, [
                        $intake['course_name'],
                        $intake['course_code'],
                        $intake['intake_name'],
                        $intake['assigned_instructor'],
                        $intake['total_enrolled'],
                        $intake['active_students'],
                        $intake['completed_students'],
                        $intake['students_assessed'],
                        $intake['students_passed'],
                        $intake['students_failed'],
                        $intake['pass_rate'] . '%',
                        $intake['performance_rating']
                    ]);
                }
            } elseif ($reportType === 'pass_rate_analysis') {
                fputcsv($file, ['MSU National Language Institute - Pass Rate Analysis Report']);
                fputcsv($file, ['Generated Date:', now()->toDateTimeString()]);
                fputcsv($file, []);
                fputcsv($file, ['Course Name', 'Course Code', 'Intake Name', 'Pass Rate (%)', 'Performance Rating', 'Students Assessed', 'Students Passed', 'Students Failed']);

                foreach ($intakes as $intake) {
                    fputcsv($file, [
                        $intake['course_name'],
                        $intake['course_code'],
                        $intake['intake_name'],
                        $intake['pass_rate'] . '%',
                        $intake['performance_rating'],
                        $intake['students_assessed'],
                        $intake['students_passed'],
                        $intake['students_failed']
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
     * Mark certificate as collected and complete enrollment.
     */
    public function issueCertificate(Request $request, CourseEnrollment $enrollment)
    {
        if ($enrollment->enrollment_status !== 'active') {
            return redirect()->back()->with('error', 'Student must have an active enrollment status to collect their certificate.');
        }

        $certCode = $enrollment->certificate_code ?: 'CERT-' . strtoupper(uniqid());

        $enrollment->update([
            'enrollment_status' => 'completed',
            'certificate_code' => $certCode,
            'certificate_issued_at' => now(),
            'certificate_collected_at' => now(),
            'certificate_collected_by' => \Auth::id(),
        ]);

        // Notify student
        SystemNotification::sendUnique(
            $enrollment->user,
            'certificate',
            'Certificate Collected',
            'Your certificate for ' . $enrollment->intake->course->title . ' has been collected and your course status has been marked as Completed.',
            route('student.courses')
        );

        // Audit Trail
        ActivityLog::log(
            'certificate_collected',
            'Administrator ' . \Auth::user()->name . ' marked certificate as collected for student ' . $enrollment->user->name . ' in ' . $enrollment->intake->course->title,
            $enrollment
        );

        return redirect()->back()->with('success', 'Certificate marked as collected successfully! Code: ' . $certCode);
    }

    /**
     * Bulk mark certificates as collected.
     */
    public function bulkIssueCertificate(Request $request)
    {
        $validated = $request->validate([
            'enrollment_ids' => 'required|array',
            'enrollment_ids.*' => 'exists:course_enrollments,id',
        ]);

        $enrollments = CourseEnrollment::whereIn('id', $validated['enrollment_ids'])->get();
        $updatedCount = 0;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->enrollment_status === 'active') {
                $certCode = $enrollment->certificate_code ?: 'CERT-' . strtoupper(uniqid());
                
                $enrollment->update([
                    'enrollment_status' => 'completed',
                    'certificate_code' => $certCode,
                    'certificate_issued_at' => now(),
                    'certificate_collected_at' => now(),
                    'certificate_collected_by' => \Auth::id(),
                ]);

                // Notify student
                SystemNotification::sendUnique(
                    $enrollment->user,
                    'certificate',
                    'Certificate Collected',
                    'Your certificate for ' . $enrollment->intake->course->title . ' has been collected and your course status has been marked as Completed.',
                    route('student.courses')
                );

                // Audit Trail
                ActivityLog::log(
                    'certificate_collected',
                    'Administrator ' . \Auth::user()->name . ' marked certificate as collected for student ' . $enrollment->user->name . ' in ' . $enrollment->intake->course->title,
                    $enrollment
                );

                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            return redirect()->back()->with('success', $updatedCount . ' certificates marked as collected successfully.');
        }

        return redirect()->back()->with('error', 'No active enrollments were found among the selected records.');
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
        $testimonials = array_values(array_filter(
            SystemSetting::get('short_courses_testimonials', []),
            fn($t) => (isset($t['status']) && $t['status'] === 'approved') || !isset($t['status'])
        ));
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
        
        $today = now()->toDateString();
        if ($intake->status !== 'open' || ($intake->end_date && $intake->end_date->toDateString() < $today)) {
            return redirect()->back()->with('error', 'Enrollments are disabled for this intake as it is not open or has already ended.');
        }
        
        // Check capacity
        $currentEnrollmentsCount = CourseEnrollment::where('course_intake_id', $intake->id)->count();
        if ($currentEnrollmentsCount >= $intake->capacity) {
            return redirect()->back()->with('error', 'Sorry, this intake has reached its maximum enrollment capacity.');
        }

        // Store proof of payment
        $filePath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filePath = $file->storeAs('payments/proofs/' . time() . '_' . uniqid(), $file->getClientOriginalName(), 'public');
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

        // Enforce that only students can access student dashboard
        if ($user->primary_category !== 'Student') {
            abort(403, 'Unauthorized. Only students can access student modules.');
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

        $intake = CourseIntake::findOrFail($validated['course_intake_id']);
        
        $today = now()->toDateString();
        if ($intake->status !== 'open' || ($intake->end_date && $intake->end_date->toDateString() < $today)) {
            return redirect()->back()->with('error', 'Applications are disabled for this intake as it is not open or has already ended.');
        }

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

        $idCopyFile = $request->file('national_id_copy');
        $idCopyPath = $idCopyFile->storeAs('course_applications/id_copies/' . time() . '_' . uniqid(), $idCopyFile->getClientOriginalName(), 'public');
        $paymentProofFile = $request->file('payment_proof');
        $paymentProofPath = $paymentProofFile->storeAs('course_applications/payment_proofs/' . time() . '_' . uniqid(), $paymentProofFile->getClientOriginalName(), 'public');

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

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('intake', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('instructor_id')) {
            $query->whereHas('intake', function ($q) use ($request) {
                $q->where('instructor_id', $request->instructor_id);
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $courses = Course::orderBy('title')->get(['id', 'title', 'code']);

        $instructors = User::whereHas('instructedIntakes')
            ->orWhereHas('roles', function ($q) {
                $q->whereIn('name', ['language_expert', 'part_time_staff', 'instructor']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Courses/Applications', [
            'applications' => $applications,
            'courses'      => $courses,
            'instructors'  => $instructors,
            'filters'      => [
                'status'        => $request->status ?? 'all',
                'course_id'     => $request->course_id,
                'instructor_id' => $request->instructor_id,
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
        $emailLogs = \App\Models\EmailLog::where('recipient_email', $application->email)->latest()->get();

        return Inertia::render('Courses/ApplicationDetails', [
            'application' => $application,
            'emailLogs'   => $emailLogs,
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

                    \App\Models\EmailLog::create([
                        'recipient_email' => $application->email,
                        'sender_email'    => config('mail.from.address') ?: 'no-reply@msunli.edu',
                        'subject'         => 'Course Acceptance & Login Credentials',
                        'message_type'    => 'welcome_email',
                        'status'          => 'sent',
                        'sent_at'         => now(),
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Failed to send welcome email to ' . $application->email . ': ' . $e->getMessage());

                    CourseApplicationLog::create([
                        'course_application_id' => $application->id,
                        'user_id'               => $user->id,
                        'action'                => 'email_failed',
                        'comment'               => 'Failed to dispatch acceptance email: ' . $e->getMessage(),
                    ]);

                    \App\Models\EmailLog::create([
                        'recipient_email' => $application->email,
                        'sender_email'    => config('mail.from.address') ?: 'no-reply@msunli.edu',
                        'subject'         => 'Course Acceptance & Login Credentials',
                        'message_type'    => 'welcome_email',
                        'status'          => 'failed',
                        'error_message'   => $e->getMessage(),
                        'sent_at'         => null,
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

    /**
     * Download application files (National ID or Payment Proof) securely with role-based checks.
     */
    public function downloadApplicationFile($id, $type)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $application = CourseApplication::findOrFail($id);

        // Authorize: Reviewers or the student owner themselves
        $isOwner = ($user->email === $application->email);
        $isReviewer = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);

        if (!$isOwner && !$isReviewer) {
            abort(403, 'Unauthorized.');
        }

        $path = null;
        if ($type === 'national_id') {
            $path = $application->national_id_copy_path;
        } elseif ($type === 'payment_proof') {
            $path = $application->payment_proof_path;
        } else {
            abort(400, 'Invalid file type.');
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        $absolutePath = Storage::disk('public')->path($path);
        
        $originalName = basename($path);
        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . $originalName . '"',
        ]);
    }
}
