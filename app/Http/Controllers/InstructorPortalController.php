<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\CourseTimetable;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\CourseCaMark;
use App\Models\User;
use App\Models\ActivityLog;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstructorPortalController extends Controller
{
    /**
     * View Course Enrollments for Instructor.
     */
    public function enrollments(Request $request)
    {
        $user = Auth::user();

        // Get instructor assigned intakes
        $intakes = CourseIntake::where('instructor_id', $user->id)
            ->with(['course'])
            ->get();

        $intakeIds = $intakes->pluck('id');

        // Query Enrollments
        $query = CourseEnrollment::has('user')
            ->with(['user', 'intake.course'])
            ->whereIn('course_intake_id', $intakeIds)
            ->where('payment_status', 'verified');

        if ($request->filled('intake_id')) {
            $query->where('course_intake_id', $request->intake_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allEnrollments = $query->get();

        // Separate students by intake/course (no combined list)
        $groupedEnrollments = [];
        $stats = [
            'total_students' => 0,
            'total_courses' => $intakes->count(),
        ];

        foreach ($intakes as $intake) {
            $students = $allEnrollments->filter(fn($e) => $e->course_intake_id === $intake->id && $e->user)->map(fn($e) => [
                'enrollment_id' => $e->id,
                'student_id' => $e->user ? $e->user->id : null,
                'name' => $e->user ? $e->user->name : 'N/A',
                'email' => $e->user ? $e->user->email : 'N/A',
                'phone' => $e->user ? $e->user->phone : null,
                'enrolled_at' => $e->created_at ? $e->created_at->format('Y-m-d') : 'N/A',
                'status' => $e->enrollment_status,
            ])->values();

            $groupedEnrollments[] = [
                'intake_id' => $intake->id,
                'intake_name' => $intake->name,
                'course_title' => $intake->course->title,
                'course_code' => $intake->course->code,
                'students' => $students,
                'student_count' => $students->count(),
            ];

            $stats['total_students'] += $students->count();
        }

        return Inertia::render('Courses/InstructorEnrollments', [
            'groupedEnrollments' => $groupedEnrollments,
            'intakes' => $intakes,
            'stats' => $stats,
            'filters' => $request->only(['search', 'intake_id']),
        ]);
    }

    /**
     * Export Enrollment List as CSV.
     */
    public function exportEnrollments($intakeId)
    {
        $user = Auth::user();
        $intake = CourseIntake::where('instructor_id', $user->id)
            ->with(['course'])
            ->findOrFail($intakeId);

        $enrollments = CourseEnrollment::has('user')
            ->with('user')
            ->where('course_intake_id', $intake->id)
            ->where('payment_status', 'verified')
            ->get();

        $callback = function() use ($enrollments, $intake) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['MSU National Language Institute - Class Enrollment List']);
            fputcsv($file, ['Course', $intake->course->title . ' (' . $intake->course->code . ')']);
            fputcsv($file, ['Intake Batch', $intake->name]);
            fputcsv($file, ['Total Enrolled', $enrollments->count()]);
            fputcsv($file, []); // Blank line

            fputcsv($file, ['Student Name', 'Email Address', 'Phone Number', 'Enrolled Date', 'Status']);

            foreach ($enrollments as $e) {
                if (!$e->user) continue;
                fputcsv($file, [
                    $e->user->name,
                    $e->user->email,
                    $e->user->phone ?? 'N/A',
                    $e->created_at ? $e->created_at->format('Y-m-d') : 'N/A',
                    $e->enrollment_status,
                ]);
            }
            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="class_list_' . strtolower(str_replace(' ', '_', $intake->course->code)) . '.csv"',
        ];

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Timetables Manager.
     */
    public function timetableIndex()
    {
        $user = Auth::user();

        $intakes = CourseIntake::where('instructor_id', $user->id)
            ->with('course')
            ->get();

        $timetables = CourseTimetable::with(['intake.course'])
            ->whereIn('course_intake_id', $intakes->pluck('id'))
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return Inertia::render('Courses/InstructorTimetable', [
            'timetables' => $timetables,
            'intakes' => $intakes,
        ]);
    }

    private function hasConflict($intakeId, $date, $startTime, $endTime, $venue, $excludeId = null)
    {
        $intake = CourseIntake::findOrFail($intakeId);
        $instructorId = $intake->instructor_id;

        $query = CourseTimetable::whereDate('date', $date)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlappingSessions = $query->get();

        foreach ($overlappingSessions as $session) {
            // 1. Same intake batch conflict
            if ((int)$session->course_intake_id === (int)$intakeId) {
                return 'This intake batch already has a scheduled class during this time.';
            }

            // 2. Instructor conflict (same instructor assigned to another intake)
            $sessionIntake = $session->intake;
            if ($sessionIntake && $instructorId && (int)$sessionIntake->instructor_id === (int)$instructorId) {
                return 'You have another scheduled class during this time.';
            }

            // 3. Venue conflict (same venue, except online links)
            $isOnline = strpos($venue, 'http://') === 0 || strpos($venue, 'https://') === 0;
            $sessionOnline = strpos($session->venue, 'http://') === 0 || strpos($session->venue, 'https://') === 0;
            
            if (!$isOnline && !$sessionOnline && strcasecmp($session->venue, $venue) === 0) {
                return 'The selected venue is already booked for another class during this time.';
            }
        }

        return null;
    }

    public function timetableStore(Request $request)
    {
        $rules = [
            'course_intake_id' => 'required|exists:course_intakes,id',
            'schedule_type' => 'nullable|string|in:daily,weekly',
            'venue' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'session_type' => 'nullable|string',
        ];

        if ($request->input('schedule_type', 'daily') === 'daily') {
            $rules['start_time'] = 'required';
            $rules['end_time'] = 'required|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
            $rules['start_date'] = 'required|date|after_or_equal:today';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
            $rules['days_of_week'] = 'required|array';
            $rules['days_of_week.*'] = 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';
            $rules['day_schedules'] = 'required|array';
        }

        $request->validate($rules);

        $scheduleType = $request->input('schedule_type', 'daily');
        $dates = [];

        if ($scheduleType === 'daily') {
            $request->validate([
                'dates' => 'nullable|array',
                'dates.*' => 'required|date|after_or_equal:today',
                'date' => 'required_without:dates|nullable|date|after_or_equal:today',
            ]);
            
            if ($request->filled('dates') && is_array($request->dates)) {
                $dates = array_unique($request->dates);
            } else {
                $dates = [$request->date];
            }
        } else if ($scheduleType === 'weekly') {
            foreach ($request->days_of_week as $day) {
                if (!isset($request->day_schedules[$day]['start_time']) || !isset($request->day_schedules[$day]['end_time'])) {
                    return redirect()->back()->withErrors(['conflict' => "Please specify both start and end times for {$day}."])->withInput();
                }
                $start = $request->day_schedules[$day]['start_time'];
                $end = $request->day_schedules[$day]['end_time'];
                if (strtotime($end) <= strtotime($start)) {
                    return redirect()->back()->withErrors(['conflict' => "End time must be after start time for {$day}."])->withInput();
                }
            }

            // Expand range of dates
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
            $daysOfWeek = $request->days_of_week;

            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dayName = $currentDate->format('l');
                if (in_array($dayName, $daysOfWeek)) {
                    $dates[] = $currentDate->toDateString();
                }
                $currentDate->addDay();
            }

            if (empty($dates)) {
                return redirect()->back()->withErrors(['conflict' => 'No dates in the selected range match the selected days of the week.'])->withInput();
            }
        }

        $user = Auth::user();
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($request->course_intake_id);

        try {
            $createdTimetables = \DB::transaction(function () use ($intake, $dates, $request, $user, $scheduleType) {
                $created = [];
                foreach ($dates as $date) {
                    $dayName = \Carbon\Carbon::parse($date)->format('l');
                    if ($scheduleType === 'weekly') {
                        $startTime = $request->day_schedules[$dayName]['start_time'];
                        $endTime = $request->day_schedules[$dayName]['end_time'];
                    } else {
                        $startTime = $request->start_time;
                        $endTime = $request->end_time;
                    }

                    $conflictError = $this->hasConflict(
                        $request->course_intake_id,
                        $date,
                        $startTime,
                        $endTime,
                        $request->venue
                    );

                    if ($conflictError) {
                        $dateFormatted = date('Y-m-d', strtotime($date));
                        throw new \Exception("Scheduling conflict on {$dateFormatted}: {$conflictError}");
                    }

                    $timetable = CourseTimetable::create([
                        'course_intake_id' => $request->course_intake_id,
                        'date' => $date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'venue' => $request->venue,
                        'notes' => $request->notes,
                        'session_type' => $request->session_type,
                        'created_by' => $user->id,
                    ]);
                    $created[] = $timetable;
                }
                return $created;
            });
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['conflict' => $e->getMessage()])->withInput();
        }

        // Notify enrolled students for all created timetables
        $enrollments = CourseEnrollment::has('user')->where('course_intake_id', $intake->id)->where('enrollment_status', 'active')->get();
        foreach ($createdTimetables as $timetable) {
            foreach ($enrollments as $e) {
                if ($e->user) {
                    $e->user->notify(new SystemNotification(
                        'timetable_update',
                        'New Class Scheduled',
                        'A new session has been scheduled for your course "' . $intake->course->title . '" on ' . $timetable->date->format('M d, Y') . ' at ' . $timetable->start_time,
                        route('student.timetable')
                    ));
                }
            }
            ActivityLog::log('create_timetable', 'Created new timetable session for ' . $intake->course->title, $timetable);
        }

        return redirect()->back()->with('success', 'Timetable class scheduled successfully!');
    }

    public function timetableUpdate(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'session_type' => 'nullable|string',
        ]);

        $user = Auth::user();
        $timetable = CourseTimetable::findOrFail($id);
        $intake = CourseIntake::findOrFail($timetable->course_intake_id);

        if ($intake->instructor_id !== $user->id && !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director'])) {
            abort(404, 'Unauthorized.');
        }

        $conflictError = $this->hasConflict(
            $timetable->course_intake_id,
            $request->date,
            $request->start_time,
            $request->end_time,
            $request->venue,
            $id
        );

        if ($conflictError) {
            return redirect()->back()->withErrors(['conflict' => $conflictError])->withInput();
        }

        $timetable->update($request->all());

        // Notify enrolled students
        $enrollments = CourseEnrollment::has('user')->where('course_intake_id', $intake->id)->where('enrollment_status', 'active')->get();
        foreach ($enrollments as $e) {
            if ($e->user) {
                $e->user->notify(new SystemNotification(
                    'timetable_update',
                    'Class Schedule Updated',
                    'The session for "' . $intake->course->title . '" on ' . $timetable->date->format('M d, Y') . ' has been updated.',
                    route('student.timetable')
                ));
            }
        }

        ActivityLog::log('update_timetable', 'Updated timetable session for ' . $intake->course->title, $timetable);

        return redirect()->back()->with('success', 'Timetable entry updated successfully!');
    }

    public function timetableDestroy($id)
    {
        $user = Auth::user();
        $timetable = CourseTimetable::findOrFail($id);
        $intake = CourseIntake::findOrFail($timetable->course_intake_id);

        if ($intake->instructor_id !== $user->id && !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director'])) {
            abort(404, 'Unauthorized.');
        }

        ActivityLog::log('delete_timetable', 'Deleted timetable session for ' . $intake->course->title, $timetable);
        
        $timetable->delete();

        return redirect()->back()->with('success', 'Timetable entry deleted successfully!');
    }

    public function timetableCopy(Request $request)
    {
        $request->validate([
            'source_week' => 'required|date',
            'target_week' => 'required|date|different:source_week',
        ]);

        $user = Auth::user();
        $intakeIds = CourseIntake::where('instructor_id', $user->id)->pluck('id');

        $sourceStart = \Carbon\Carbon::parse($request->source_week)->startOfDay();
        $sourceEnd = $sourceStart->copy()->addDays(6)->endOfDay();

        $targetStart = \Carbon\Carbon::parse($request->target_week)->startOfDay();

        $sessions = CourseTimetable::whereIn('course_intake_id', $intakeIds)
            ->whereBetween('date', [$sourceStart->toDateString(), $sourceEnd->toDateString()])
            ->get();

        if ($sessions->isEmpty()) {
            return redirect()->back()->with('error', 'No class sessions found in the source week.');
        }

        foreach ($sessions as $session) {
            $sessionDate = \Carbon\Carbon::parse($session->date);
            $dayOffset = $sourceStart->diffInDays($sessionDate);
            $targetDate = $targetStart->copy()->addDays($dayOffset)->toDateString();

            CourseTimetable::create([
                'course_intake_id' => $session->course_intake_id,
                'date' => $targetDate,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'venue' => $session->venue,
                'session_type' => $session->session_type,
                'notes' => $session->notes,
                'created_by' => $user->id,
            ]);
        }

        return redirect()->back()->with('success', 'Weekly timetable copied successfully!');
    }

    /**
     * Continuous Assessments Manager.
     */
    public function caIndex(Request $request)
    {
        $user = Auth::user();

        $intakes = CourseIntake::where('instructor_id', $user->id)
            ->with('course')
            ->get();

        $selectedIntakeId = $request->input('intake_id', $intakes->first() ? $intakes->first()->id : null);
        
        $students = [];
        $analytics = [
            'class_average' => 0,
            'highest_score' => 0,
            'lowest_score' => 0,
            'pass_rate' => 0,
        ];

        if ($selectedIntakeId) {
            $intake = CourseIntake::findOrFail($selectedIntakeId);

            // Get enrolled active/completed students
            $enrollments = CourseEnrollment::has('user')
                ->with('user')
                ->where('course_intake_id', $selectedIntakeId)
                ->where('payment_status', 'verified')
                ->get();

            $caMarks = CourseCaMark::where('course_intake_id', $selectedIntakeId)->get()->groupBy('user_id');
            
            // Get course assignments
            $assignments = CourseAssignment::where('course_intake_id', $selectedIntakeId)->get();
            $submissions = CourseAssignmentSubmission::whereIn('course_assignment_id', $assignments->pluck('id'))->get()->groupBy('user_id');

            $gradesList = [];

            foreach ($enrollments as $e) {
                if (!$e->user) continue;
                $studentMarks = $caMarks->get($e->user->id, collect());
                $studentSubs = $submissions->get($e->user->id, collect());

                $totalPossible = 0;
                $totalObtained = 0;

                // Add up assignment scores
                foreach ($assignments as $asg) {
                    $sub = $studentSubs->firstWhere('course_assignment_id', $asg->id);
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

                $scorePct = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : null;
                if ($scorePct !== null) {
                    $gradesList[] = $scorePct;
                }

                $students[] = [
                    'student_id' => $e->user->id,
                    'name' => $e->user->name,
                    'email' => $e->user->email,
                    'overall_pct' => $scorePct,
                    'marks' => $studentMarks->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->assessment_name,
                        'obtained' => $m->marks_obtained,
                        'max' => $m->max_marks,
                        'feedback' => $m->feedback,
                    ])->values(),
                ];
            }

            // Calculations
            if (count($gradesList) > 0) {
                $analytics['class_average'] = round(array_sum($gradesList) / count($gradesList), 2);
                $analytics['highest_score'] = max($gradesList);
                $analytics['lowest_score'] = min($gradesList);
                $passes = array_filter($gradesList, fn($g) => $g >= 50);
                $analytics['pass_rate'] = round((count($passes) / count($gradesList)) * 100, 2);
            }
        }

        return Inertia::render('Courses/InstructorCa', [
            'intakes' => $intakes,
            'students' => $students,
            'analytics' => $analytics,
            'selectedIntakeId' => (int) $selectedIntakeId,
        ]);
    }

    public function caStore(Request $request)
    {
        $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'user_id' => 'required|exists:users,id',
            'assessment_name' => 'required|string|max:255',
            'marks_obtained' => 'required|numeric|min:0',
            'max_marks' => 'required|numeric|min:1',
            'feedback' => 'nullable|string',
        ]);

        $user = Auth::user();
        $student = User::findOrFail($request->user_id);

        $mark = CourseCaMark::updateOrCreate(
            [
                'course_intake_id' => $request->course_intake_id,
                'user_id' => $student->id,
                'assessment_name' => $request->assessment_name,
            ],
            [
                'marks_obtained' => $request->marks_obtained,
                'max_marks' => $request->max_marks,
                'feedback' => $request->feedback,
                'entered_by' => $user->id,
            ]
        );

        // Notify student
        $student->notify(new SystemNotification(
            'ca_updated',
            'Assessment Grade Entered',
            'Your grade for "' . $request->assessment_name . '" has been updated: ' . $mark->marks_obtained . '/' . $mark->max_marks,
            route('student.ca')
        ));

        ActivityLog::log('enter_ca_mark', 'Recorded CA score for student ' . $student->name, $mark);

        return redirect()->back()->with('success', 'Continuous Assessment grade saved!');
    }

    public function caBulkUpload(Request $request)
    {
        $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'marks_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $user = Auth::user();
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($request->course_intake_id);
        $file = $request->file('marks_file');
        
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (count($data) <= 1) {
            return redirect()->back()->with('error', 'The uploaded CSV file is empty or formatted incorrectly.');
        }

        // Header check
        $header = array_map('strtolower', array_map('trim', $data[0]));
        $emailIdx = array_search('email', $header);
        $nameIdx = array_search('assessment_name', $header);
        $obtainedIdx = array_search('marks_obtained', $header);
        $maxIdx = array_search('max_marks', $header);
        $feedbackIdx = array_search('feedback', $header);

        if ($emailIdx === false || $nameIdx === false || $obtainedIdx === false || $maxIdx === false) {
            return redirect()->back()->with('error', 'CSV header must include: email, assessment_name, marks_obtained, max_marks');
        }

        $insertedCount = 0;

        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            if (empty($row) || !isset($row[$emailIdx])) continue;

            $email = trim($row[$emailIdx]);
            $student = User::where('email', $email)->first();

            if (!$student) continue;

            $obtained = (float) $row[$obtainedIdx];
            $max = (float) $row[$maxIdx];
            $assessName = trim($row[$nameIdx]);
            $feedback = $feedbackIdx !== false ? trim($row[$feedbackIdx]) : null;

            $mark = CourseCaMark::updateOrCreate(
                [
                    'course_intake_id' => $intake->id,
                    'user_id' => $student->id,
                    'assessment_name' => $assessName,
                ],
                [
                    'marks_obtained' => $obtained,
                    'max_marks' => $max,
                    'feedback' => $feedback,
                    'entered_by' => $user->id,
                ]
            );

            $student->notify(new SystemNotification(
                'ca_updated',
                'Assessment Grade Entered (Bulk)',
                'A new assessment score "' . $assessName . '" has been updated via bulk upload.',
                route('student.ca')
            ));

            $insertedCount++;
        }

        ActivityLog::log('bulk_upload_ca', 'Bulk uploaded CA marks for ' . $insertedCount . ' students in ' . $intake->course->title);

        return redirect()->back()->with('success', 'Successfully uploaded ' . $insertedCount . ' student marks!');
    }

    /**
     * Assignments Manager.
     */
    public function assignmentsIndex()
    {
        $user = Auth::user();

        $intakes = CourseIntake::where('instructor_id', $user->id)
            ->with('course')
            ->get();

        $assignments = CourseAssignment::with(['intake.course'])
            ->whereIn('course_intake_id', $intakes->pluck('id'))
            ->orderBy('due_date', 'desc')
            ->get();

        // Map submissions stats
        $assignmentIds = $assignments->pluck('id');
        $submissionCounts = CourseAssignmentSubmission::whereIn('course_assignment_id', $assignmentIds)
            ->selectRaw('course_assignment_id, COUNT(*) as total, SUM(CASE WHEN status="graded" THEN 1 ELSE 0 END) as graded')
            ->groupBy('course_assignment_id')
            ->get()
            ->keyBy('course_assignment_id');

        $mappedAssignments = $assignments->map(function ($asg) use ($submissionCounts) {
            $stats = $submissionCounts->get($asg->id);
            return array_merge($asg->toArray(), [
                'total_submissions' => $stats ? $stats->total : 0,
                'graded_submissions' => $stats ? $stats->graded : 0,
                'due_date' => $asg->due_date->format('Y-m-d H:i'),
            ]);
        });

        return Inertia::render('Courses/InstructorAssignments', [
            'assignments' => $mappedAssignments,
            'intakes' => $intakes,
        ]);
    }

    public function assignmentsStore(Request $request)
    {
        $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'due_date' => 'required|date|after_or_equal:today',
            'max_marks' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($request->course_intake_id);

        $filePath = null;
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('course_assignments/materials', 'public');
        }

        $assignment = CourseAssignment::create(array_merge($request->except('attachment'), [
            'attachment_path' => $filePath,
            'created_by' => $user->id,
        ]));

        // Notify enrolled students
        $enrollments = CourseEnrollment::has('user')->where('course_intake_id', $intake->id)->where('enrollment_status', 'active')->get();
        foreach ($enrollments as $e) {
            if ($e->user) {
                $e->user->notify(new SystemNotification(
                    'new_assignment',
                    'New Assignment Assigned',
                    'A new assignment "' . $assignment->title . '" has been posted for course ' . $intake->course->title,
                    route('student.assignments')
                ));
            }
        }

        ActivityLog::log('create_assignment', 'Created assignment ' . $assignment->title, $assignment);

        return redirect()->back()->with('success', 'Assignment created successfully!');
    }

    public function assignmentsUpdate(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_marks' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $assignment = CourseAssignment::findOrFail($id);
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($assignment->course_intake_id);

        $assignment->update($request->all());

        ActivityLog::log('update_assignment', 'Updated assignment ' . $assignment->title, $assignment);

        return redirect()->back()->with('success', 'Assignment updated successfully!');
    }

    public function assignmentsDestroy($id)
    {
        $user = Auth::user();
        $assignment = CourseAssignment::findOrFail($id);
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($assignment->course_intake_id);

        ActivityLog::log('delete_assignment', 'Deleted assignment ' . $assignment->title, $assignment);
        
        $assignment->delete();

        return redirect()->back()->with('success', 'Assignment deleted successfully!');
    }

    public function assignmentsSubmissions($assignmentId)
    {
        $user = Auth::user();
        $assignment = CourseAssignment::with('intake.course')->findOrFail($assignmentId);
        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($assignment->course_intake_id);

        $submissions = CourseAssignmentSubmission::with('student')
            ->where('course_assignment_id', $assignment->id)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'assignment' => $assignment,
            'submissions' => $submissions,
        ]);
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $request->validate([
            'marks_obtained' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $user = Auth::user();
        $submission = CourseAssignmentSubmission::with('assignment.intake')->findOrFail($submissionId);
        
        // Safety scope check
        CourseIntake::where('instructor_id', $user->id)->findOrFail($submission->assignment->course_intake_id);

        $submission->update([
            'marks_obtained' => $request->marks_obtained,
            'feedback' => $request->feedback,
            'status' => 'graded',
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        // Notify student
        $student = User::findOrFail($submission->user_id);
        $student->notify(new SystemNotification(
            'assignment_graded',
            'Assignment Graded',
            'Your submission for "' . $submission->assignment->title . '" has been graded: ' . $submission->marks_obtained . '/' . $submission->assignment->max_marks,
            route('student.assignments')
        ));

        ActivityLog::log('grade_assignment', 'Graded assignment submission for student ' . $student->name, $submission);

        return redirect()->back()->with('success', 'Submission graded successfully!');
    }

    /**
     * View completed tasks and assignments for the instructor.
     */
    public function completedTasks()
    {
        $user = Auth::user();

        // Get completed assignments assigned to this instructor (user)
        $completedAssignments = \App\Models\Assignment::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->with(['serviceRequest.client', 'tasks'])
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($asg) {
                return [
                    'id' => $asg->id,
                    'role_in_task' => $asg->role_in_task,
                    'status' => $asg->status,
                    'notes' => $asg->notes,
                    'started_at' => $asg->started_at ? $asg->started_at->format('Y-m-d H:i') : null,
                    'completed_at' => $asg->completed_at ? $asg->completed_at->format('Y-m-d H:i') : null,
                    'service_request' => $asg->serviceRequest ? [
                        'title' => $asg->serviceRequest->title,
                        'description' => $asg->serviceRequest->description,
                        'service_category' => $asg->serviceRequest->service_category,
                        'priority' => $asg->serviceRequest->priority,
                        'client' => $asg->serviceRequest->client ? [
                            'organization' => $asg->serviceRequest->client->organization,
                            'contact_person' => $asg->serviceRequest->client->contact_person,
                            'email' => $asg->serviceRequest->client->email,
                            'phone' => $asg->serviceRequest->client->phone,
                            'address' => $asg->serviceRequest->client->address,
                        ] : null,
                    ] : null,
                    'tasks' => $asg->tasks->map(fn($t) => [
                        'title' => $t->title,
                        'description' => $t->description,
                        'status' => $t->status,
                        'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                        'completed_at' => $t->completed_at ? $t->completed_at->format('Y-m-d H:i') : null,
                    ]),
                ];
            });

        return Inertia::render('Courses/InstructorCompletedTasks', [
            'completedAssignments' => $completedAssignments,
        ]);
    }

    /**
     * View learning contents for intakes assigned to this instructor.
     */
    public function learningContentIndex(Request $request)
    {
        $user = Auth::user();

        // Get instructor assigned intakes
        $intakes = CourseIntake::where('instructor_id', $user->id)
            ->with(['course'])
            ->get();

        $intakeIds = $intakes->pluck('id');

        // Fetch learning content
        $learningContents = \App\Models\LearningContent::whereIn('course_intake_id', $intakeIds)
            ->with(['intake.course'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Courses/InstructorLearningContent', [
            'intakes' => $intakes,
            'learningContents' => $learningContents,
        ]);
    }

    /**
     * Store new learning content.
     */
    public function learningContentStore(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'course_intake_id' => 'required|exists:course_intakes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $intake = CourseIntake::where('instructor_id', $user->id)->findOrFail($request->course_intake_id);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filePath = $file->store('learning_materials', 'public');
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            \App\Models\LearningContent::create([
                'course_intake_id' => $intake->id,
                'uploaded_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
            ]);

            return redirect()->back()->with('success', 'Learning content uploaded successfully.');
        }

        return redirect()->back()->with('error', 'Failed to upload learning content file.');
    }

    /**
     * Update learning content metadata or replace file.
     */
    public function learningContentUpdate(Request $request, $id)
    {
        $user = Auth::user();
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|max:51200', // Max 50MB
        ]);

        $content = \App\Models\LearningContent::findOrFail($id);
        
        // Ensure the content belongs to an intake assigned to this instructor
        CourseIntake::where('instructor_id', $user->id)->findOrFail($content->course_intake_id);

        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
        ];

        if ($request->hasFile('file')) {
            // Delete old file
            if (Storage::disk('public')->exists($content->file_path)) {
                Storage::disk('public')->delete($content->file_path);
            }

            $file = $request->file('file');
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_path'] = $file->store('learning_materials', 'public');
            $updateData['file_size'] = $file->getSize();
            $updateData['mime_type'] = $file->getMimeType();
        }

        $content->update($updateData);

        return redirect()->back()->with('success', 'Learning content updated successfully.');
    }

    /**
     * Delete learning content.
     */
    public function learningContentDestroy($id)
    {
        $user = Auth::user();
        
        $content = \App\Models\LearningContent::findOrFail($id);
        
        // Ensure the content belongs to an intake assigned to this instructor
        CourseIntake::where('instructor_id', $user->id)->findOrFail($content->course_intake_id);

        // Delete file
        if (Storage::disk('public')->exists($content->file_path)) {
            Storage::disk('public')->delete($content->file_path);
        }

        $content->delete();

        return redirect()->back()->with('success', 'Learning content deleted successfully.');
    }
}
