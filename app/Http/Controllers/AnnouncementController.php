<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getAssignedCourseIds($user)
    {
        // Instructors can manage announcements for courses they instruct
        return CourseIntake::where('instructor_id', $user->id)
            ->pluck('course_id')
            ->unique()
            ->toArray();
    }

    public function instructorIndex()
    {
        $user = Auth::user();
        
        $assignedCourseIds = $this->getAssignedCourseIds($user);
        
        $announcements = Announcement::with('course')
            ->where('instructor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'course_id' => $announcement->course_id,
                    'course_name' => $announcement->course ? $announcement->course->title : 'N/A',
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'attachment_path' => $announcement->attachment_path,
                    'attachment_filename' => $announcement->attachment_path ? basename($announcement->attachment_path) : null,
                    'published_at' => $announcement->published_at,
                    'status' => $announcement->status,
                    'created_at' => $announcement->created_at,
                ];
            });
            
        $courses = Course::whereIn('id', $assignedCourseIds)
            ->orderBy('title')
            ->get(['id', 'title', 'code']);

        return Inertia::render('Courses/InstructorAnnouncements', [
            'announcements' => $announcements,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $assignedCourseIds = $this->getAssignedCourseIds($user);
        
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id|in:' . implode(',', $assignedCourseIds ?: [0]),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB
            'status' => 'required|in:draft,published',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = $file->getClientOriginalName();
            $attachmentPath = $file->storeAs('announcements/' . time() . '_' . uniqid(), $filename, 'public');
        }

        $publishedAt = $validated['status'] === 'published' ? ($validated['publish_date'] ?? now()) : null;

        Announcement::create([
            'course_id' => $validated['course_id'],
            'instructor_id' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'attachment_path' => $attachmentPath,
            'published_at' => $publishedAt,
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $user = Auth::user();
        if ($announcement->instructor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $assignedCourseIds = $this->getAssignedCourseIds($user);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id|in:' . implode(',', $assignedCourseIds ?: [0]),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'status' => 'required|in:draft,published',
            'delete_attachment' => 'nullable|boolean',
        ]);

        $attachmentPath = $announcement->attachment_path;

        if ($validated['delete_attachment'] && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
            $attachmentPath = null;
        }

        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $file = $request->file('attachment');
            $filename = $file->getClientOriginalName();
            $attachmentPath = $file->storeAs('announcements/' . time() . '_' . uniqid(), $filename, 'public');
        }

        $publishedAt = $announcement->published_at;
        if ($validated['status'] === 'published') {
            $publishedAt = $validated['publish_date'] ?? ($announcement->published_at ?? now());
        } elseif ($validated['status'] === 'draft') {
            $publishedAt = null;
        }

        $announcement->update([
            'course_id' => $validated['course_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'attachment_path' => $attachmentPath,
            'published_at' => $publishedAt,
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $user = Auth::user();
        if ($announcement->instructor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully.');
    }

    public function publish(Announcement $announcement)
    {
        $user = Auth::user();
        if ($announcement->instructor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $announcement->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Announcement published successfully.');
    }

    public function studentIndex()
    {
        $user = Auth::user();
        if ($user->primary_category !== 'Student') {
            abort(403, 'Access denied.');
        }

        $enrolledCourseIds = CourseEnrollment::join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
            ->where('course_enrollments.user_id', $user->id)
            ->where('course_enrollments.enrollment_status', 'active')
            ->pluck('course_intakes.course_id')
            ->unique()
            ->toArray();

        $announcements = Announcement::with(['course', 'instructor'])
            ->whereIn('course_id', $enrolledCourseIds)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->get()
            ->map(function ($announcement) use ($user) {
                $hasRead = AnnouncementRead::where('announcement_id', $announcement->id)
                    ->where('student_id', $user->id)
                    ->exists();

                return [
                    'id' => $announcement->id,
                    'course_id' => $announcement->course_id,
                    'course_name' => $announcement->course ? $announcement->course->title : 'N/A',
                    'instructor_name' => $announcement->instructor ? $announcement->instructor->name : 'Instructor',
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'attachment_path' => $announcement->attachment_path,
                    'attachment_filename' => $announcement->attachment_path ? basename($announcement->attachment_path) : null,
                    'published_at' => $announcement->published_at,
                    'is_read' => $hasRead,
                ];
            });

        $courses = Course::whereIn('id', $enrolledCourseIds)->get(['id', 'title', 'code']);

        return Inertia::render('Courses/StudentAnnouncements', [
            'announcements' => $announcements,
            'courses' => $courses,
        ]);
    }

    public function markAsRead(Announcement $announcement)
    {
        $user = Auth::user();
        if ($user->primary_category !== 'Student') {
            abort(403, 'Access denied.');
        }

        $isEnrolled = CourseEnrollment::join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
            ->where('course_enrollments.user_id', $user->id)
            ->where('course_intakes.course_id', $announcement->course_id)
            ->where('course_enrollments.enrollment_status', 'active')
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Unauthorized.');
        }

        AnnouncementRead::firstOrCreate([
            'announcement_id' => $announcement->id,
            'student_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }
}
