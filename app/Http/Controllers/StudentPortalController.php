<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\CourseTimetable;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\CourseCaMark;
use App\Models\ActivityLog;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StudentPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || $user->primary_category !== 'Student') {
                abort(403, 'Unauthorized. Only students can access student modules.');
            }
            return $next($request);
        });
    }

    /**
     * View Continuous Assessment (CA) marks, assignments scores, and progress.
     */
    public function caMarks()
    {
        $user = Auth::user();
        
        $enrollments = CourseEnrollment::with(['intake.course', 'intake.instructor'])
            ->where(['user_id' => $user->id])
            ->whereIn('enrollment_status', ['active', 'completed'])
            ->get();

        $courseData = [];

        foreach ($enrollments as $enrollment) {
            $intake = $enrollment->intake;
            $course = $intake->course;

            // Fetch course assignments
            $assignments = CourseAssignment::where(['course_intake_id' => $intake->id])->get();
            $assignmentIds = $assignments->pluck('id');

            // Fetch submissions
            $submissions = CourseAssignmentSubmission::whereIn('course_assignment_id', $assignmentIds)
                ->where(['user_id' => $user->id])
                ->get()
                ->keyBy(fn($item) => $item->course_assignment_id);

            // Fetch extra CA marks (tests, quizzes etc.)
            $caMarks = CourseCaMark::where(['course_intake_id' => $intake->id])
                ->where(['user_id' => $user->id])
                ->get();

            $totalPossible = 0;
            $totalObtained = 0;
            $gradedCount = 0;

            $assessmentHistory = [];

            // Compile Assignment Scores
            foreach ($assignments as $asg) {
                $sub = $submissions->get($asg->id);
                $status = 'pending';
                $score = null;
                $feedback = '';

                if ($sub) {
                    $status = $sub->status;
                    if ($status === 'graded') {
                        $score = $sub->marks_obtained;
                        $feedback = $sub->feedback;
                        $totalObtained += $score;
                        $totalPossible += $asg->max_marks;
                        $gradedCount++;
                    }
                } elseif (now()->gt($asg->due_date)) {
                    $status = 'overdue';
                }

                $assessmentHistory[] = [
                    'name' => 'Assignment: ' . $asg->title,
                    'type' => 'Assignment',
                    'due_date' => $asg->due_date->format('Y-m-d H:i'),
                    'status' => $status,
                    'marks_obtained' => $score,
                    'max_marks' => $asg->max_marks,
                    'feedback' => $feedback,
                ];
            }

            // Compile other CA marks
            foreach ($caMarks as $mark) {
                $totalObtained += $mark->marks_obtained;
                $totalPossible += $mark->max_marks;
                $gradedCount++;

                $assessmentHistory[] = [
                    'name' => $mark->assessment_name,
                    'type' => 'Assessment',
                    'due_date' => $mark->created_at->format('Y-m-d'),
                    'status' => 'graded',
                    'marks_obtained' => $mark->marks_obtained,
                    'max_marks' => $mark->max_marks,
                    'feedback' => $mark->feedback,
                ];
            }

            // Calculate overall course progress (e.g. graded assessments out of total)
            $totalAssessments = count($assignments) + count($caMarks);
            $progress = $totalAssessments > 0 ? round(($gradedCount / $totalAssessments) * 100) : 0;
            $averageScore = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0;

            $courseData[] = [
                'enrollment_id' => $enrollment->id,
                'course_title' => $course->title,
                'course_code' => $course->code,
                'instructor' => $intake->instructor ? $intake->instructor->name : 'TBA',
                'progress' => $progress,
                'average_score' => $averageScore,
                'assessment_history' => $assessmentHistory,
            ];
        }

        return Inertia::render('Courses/StudentCa', [
            'courseData' => $courseData,
        ]);
    }

    /**
     * Download Continuous Assessment Report as CSV.
     */
    public function downloadCaReport()
    {
        $user = Auth::user();
        
        $enrollments = CourseEnrollment::with(['intake.course'])
            ->where(['user_id' => $user->id])
            ->whereIn('enrollment_status', ['active', 'completed'])
            ->get();

        $callback = function() use ($enrollments, $user) {
            $file = fopen('php://output', 'w');
            
            // Header Info
            fputcsv($file, ['MSU NATIONAL LANGUAGE INSTITUTE - STUDENT ASSESSMENT REPORT']);
            fputcsv($file, ['Student Name', $user->name]);
            fputcsv($file, ['Email', $user->email]);
            fputcsv($file, ['Report Date', now()->format('Y-m-d H:i')]);
            fputcsv($file, []); // blank line

            foreach ($enrollments as $enrollment) {
                $intake = $enrollment->intake;
                $course = $intake->course;

                fputcsv($file, ['Course', $course->title . ' (' . $course->code . ')']);
                fputcsv($file, ['Intake Batch', $intake->name]);
                fputcsv($file, ['Assessment Name', 'Type', 'Status', 'Marks Obtained', 'Max Marks', 'Feedback']);

                // Fetch assignments and ca marks
                $assignments = CourseAssignment::where(['course_intake_id' => $intake->id])->get();
                $assignmentIds = $assignments->pluck('id');
                $submissions = CourseAssignmentSubmission::whereIn('course_assignment_id', $assignmentIds)
                    ->where(['user_id' => $user->id])
                    ->get()
                    ->keyBy(fn($item) => $item->course_assignment_id);

                $caMarks = CourseCaMark::where(['course_intake_id' => $intake->id])
                    ->where(['user_id' => $user->id])
                    ->get();

                foreach ($assignments as $asg) {
                    $sub = $submissions->get($asg->id);
                    $status = $sub ? $sub->status : (now()->gt($asg->due_date) ? 'overdue' : 'pending');
                    $score = $sub && $sub->status === 'graded' ? $sub->marks_obtained : 'N/A';
                    $feedback = $sub ? $sub->feedback : '';

                    fputcsv($file, [$asg->title, 'Assignment', $status, $score, $asg->max_marks, $feedback]);
                }

                foreach ($caMarks as $mark) {
                    fputcsv($file, [$mark->assessment_name, 'Other Assessment', 'graded', $mark->marks_obtained, $mark->max_marks, $mark->feedback]);
                }

                fputcsv($file, []); // blank separating line
            }

            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . str_replace(' ', '_', strtolower($user->name)) . '_ca_report.csv"',
        ];

        return response()->stream($callback, 200, $headers);
    }

    /**
     * View Course Timetables.
     */
    public function timetable()
    {
        $user = Auth::user();
        
        // Find enrolled intakes
        $intakeIds = CourseEnrollment::where(['user_id' => $user->id])
            ->whereIn('enrollment_status', ['active', 'completed'])
            ->pluck('course_intake_id');

        $timetables = CourseTimetable::with(['intake.course', 'intake.instructor'])
            ->whereIn('course_intake_id', $intakeIds)
            ->orderBy('date', 'asc')
            ->orderByRaw('start_time asc')
            ->get();

        return Inertia::render('Courses/StudentTimetable', [
            'timetables' => $timetables,
        ]);
    }

    /**
     * View Course Assignments.
     */
    public function assignments()
    {
        $user = Auth::user();

        // Enrolled intakes
        $intakeIds = CourseEnrollment::where(['user_id' => $user->id])
            ->whereIn('enrollment_status', ['active', 'completed'])
            ->pluck('course_intake_id');

        $assignments = CourseAssignment::with(['intake.course'])
            ->whereIn('course_intake_id', $intakeIds)
            ->orderByRaw('due_date asc')
            ->get();

        $assignmentIds = $assignments->pluck('id');

        $submissions = CourseAssignmentSubmission::whereIn('course_assignment_id', $assignmentIds)
            ->where(['user_id' => $user->id])
            ->get()
            ->keyBy(fn($item) => $item->course_assignment_id);

        $mappedAssignments = $assignments->map(function ($asg) use ($submissions) {
            $sub = $submissions->get($asg->id);
            $status = 'pending';
            $submissionDetails = null;

            if ($sub) {
                $status = $sub->status;
                $submissionDetails = [
                    'id' => $sub->id,
                    'submitted_at' => $sub->submitted_at->format('Y-m-d H:i'),
                    'attachment_path' => $sub->attachment_path,
                    'attachment_name' => basename($sub->attachment_path),
                    'notes' => $sub->notes,
                    'marks_obtained' => $sub->marks_obtained,
                    'feedback' => $sub->feedback,
                    'graded_at' => $sub->graded_at ? $sub->graded_at->format('Y-m-d H:i') : null,
                ];
            } elseif (now()->gt($asg->due_date)) {
                $status = 'overdue';
            }

            return [
                'id' => $asg->id,
                'course_title' => $asg->intake->course->title,
                'course_code' => $asg->intake->course->code,
                'title' => $asg->title,
                'description' => $asg->description,
                'attachment_path' => $asg->attachment_path,
                'attachment_name' => $asg->attachment_path ? basename($asg->attachment_path) : null,
                'due_date' => $asg->due_date->format('Y-m-d H:i'),
                'max_marks' => $asg->max_marks,
                'status' => $status,
                'submission' => $submissionDetails,
            ];
        });

        return Inertia::render('Courses/StudentAssignments', [
            'assignments' => $mappedAssignments,
        ]);
    }

    /**
     * Submit an assignment.
     */
    public function submitAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'attachment' => 'required|file|mimes:pdf,zip,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $assignment = CourseAssignment::with('intake.instructor')->findOrFail($assignmentId);

        $file = $request->file('attachment');
        $filePath = $file->storeAs('course_assignments/submissions/' . time() . '_' . uniqid(), $file->getClientOriginalName(), 'public');

        // Check if overdue
        $status = now()->gt($assignment->due_date) ? 'overdue' : 'submitted';

        $submission = CourseAssignmentSubmission::updateOrCreate(
            [
                'course_assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'submitted_at' => now(),
                'attachment_path' => $filePath,
                'notes' => $request->notes,
                'status' => $status,
            ]
        );

        // Audit Log
        ActivityLog::log(
            'submit_assignment',
            'Student submitted assignment: ' . $assignment->title . ' for course ' . $assignment->intake->course->title,
            $submission
        );

        // Notify instructor
        if ($assignment->intake && $assignment->intake->instructor) {
            $assignment->intake->instructor->notify(new SystemNotification(
                'assignment_submitted',
                'New Assignment Submission',
                $user->name . ' submitted ' . $assignment->title . ' for course ' . $assignment->intake->course->title,
                route('instructor.assignments.index'),
                ['assignment_id' => $assignment->id, 'student_id' => $user->id]
            ));
        }

        return redirect()->back()->with('success', 'Assignment submitted successfully!');
    }

    /**
     * Submit a testimonial for a completed course.
     */
    public function submitTestimonial(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:course_enrollments,id',
            'text' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        // Check if enrollment belongs to user and is completed
        $enrollment = CourseEnrollment::where(['id' => $validated['enrollment_id']])
            ->where(['user_id' => $user->id])
            ->where(['enrollment_status' => 'completed'])
            ->firstOrFail();

        // Store the pending testimony under a separate setting key
        $pendingTestimonials = \App\Models\SystemSetting::get('short_courses_pending_testimonials', []);
        $pendingTestimonials[] = [
            'id' => uniqid(),
            'name' => $user->name,
            'course' => $enrollment->intake->course->title,
            'text' => $validated['text'],
            'submitted_at' => now()->toDateTimeString(),
        ];

        \App\Models\SystemSetting::set('short_courses_pending_testimonials', $pendingTestimonials);

        // Notify Admins
        $admins = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['ict_administrator', 'executive_director', 'deputy_director']);
        })->get();

        foreach ($admins as $admin) {
            SystemNotification::sendUnique(
                $admin,
                'testimony_moderation',
                'New Testimony Pending Review',
                'Student ' . $user->name . ' has submitted a testimony for ' . $enrollment->intake->course->title . ' that requires moderation.',
                route('admin.settings.index') . '?tab=testimonies'
            );
        }

        // Log activity
        ActivityLog::log(
            'submit_testimonial',
            'Student ' . $user->name . ' submitted a testimonial for ' . $enrollment->intake->course->title,
            $enrollment
        );

        return redirect()->back()->with('success', 'Your testimony has successfully sent.');
    }

    /**
     * View learning materials uploaded by instructors for enrolled courses.
     */
    public function learningContent()
    {
        $user = Auth::user();

        // Get student active enrollments
        $enrollments = CourseEnrollment::with(['intake.course', 'intake.instructor'])
            ->where(['user_id' => $user->id])
            ->where(['enrollment_status' => 'active'])
            ->get();

        $intakeIds = $enrollments->pluck('course_intake_id')->toArray();

        // Fetch learning content materials
        $learningContents = \App\Models\LearningContent::whereIn('course_intake_id', $intakeIds)
            ->with(['intake.course', 'uploader'])
            ->orderByRaw('created_at desc')
            ->get();

        return Inertia::render('Courses/StudentLearningContent', [
            'enrollments' => $enrollments,
            'learningContents' => $learningContents,
        ]);
    }
}

