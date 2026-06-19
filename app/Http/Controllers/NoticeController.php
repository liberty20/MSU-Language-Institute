<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Course;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function isAdmin($user)
    {
        return $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary']);
    }

    public function index()
    {
        $user = Auth::user();
        
        if ($user->primary_category === 'Student') {
            // Students only see published notices targeted to all students or specific courses they are enrolled in
            $notices = Notice::whereNotNull('published_at')
                ->where(function($query) use ($user) {
                    $query->where('target_type', 'all_students')
                        ->orWhere(function($q) use ($user) {
                            $q->where('target_type', 'specific_course')
                                ->whereIn('course_id', function($qi) use ($user) {
                                    $qi->select('course_intakes.course_id')
                                        ->from('course_enrollments')
                                        ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                                        ->where('course_enrollments.user_id', $user->id)
                                        ->where('course_enrollments.enrollment_status', 'active');
                                });
                        });
                })
                ->with(['creator', 'documents'])
                ->orderBy('published_at', 'desc')
                ->get();
                
            return Inertia::render('Notices/Index', [
                'notices' => $notices,
                'courses' => []
            ]);
        } else {
            // Admin assistants, secretary, directors, etc. see all notices
            $notices = Notice::with(['creator', 'course', 'documents'])
                ->orderBy('created_at', 'desc')
                ->get();
                
            $courses = Course::orderBy('title')->get(['id', 'title', 'code']);
            
            return Inertia::render('Notices/Index', [
                'notices' => $notices,
                'courses' => $courses
            ]);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|in:all_students,specific_course',
            'course_id' => 'required_if:target_type,specific_course|nullable|exists:courses,id',
            'publish_immediately' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB each
        ]);

        $notice = Notice::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'course_id' => $validated['target_type'] === 'specific_course' ? $validated['course_id'] : null,
            'published_at' => $request->publish_immediately ? now() : null,
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $file->store('documents', 'public');
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();

                $notice->documents()->create([
                    'uploaded_by' => $user->id,
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'description' => 'Attached to notice: ' . $notice->title,
                ]);
            }
        }

        if ($notice->published_at) {
            $this->notifyStudents($notice);
        }

        return redirect()->back()->with('success', 'Notice created successfully.');
    }

    public function update(Request $request, Notice $notice)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|in:all_students,specific_course',
            'course_id' => 'required_if:target_type,specific_course|nullable|exists:courses,id',
            'publish' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'deleted_file_ids' => 'nullable|array',
            'deleted_file_ids.*' => 'exists:uploaded_documents,id',
        ]);

        // Delete requested attachments
        if ($request->has('deleted_file_ids')) {
            foreach ($request->deleted_file_ids as $docId) {
                $doc = UploadedDocument::find($docId);
                if ($doc && $doc->documentable_id === $notice->id && $doc->documentable_type === Notice::class) {
                    Storage::disk('public')->delete($doc->file_path);
                    $doc->delete();
                }
            }
        }

        $wasPublished = !is_null($notice->published_at);
        $shouldPublish = $request->publish || $wasPublished;

        $notice->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'course_id' => $validated['target_type'] === 'specific_course' ? $validated['course_id'] : null,
            'published_at' => $shouldPublish ? ($notice->published_at ?? now()) : null,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $file->store('documents', 'public');
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();

                $notice->documents()->create([
                    'uploaded_by' => $user->id,
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'description' => 'Attached to notice: ' . $notice->title,
                ]);
            }
        }

        if ($shouldPublish) {
            $this->notifyStudents($notice, $wasPublished ? 'Notice Updated: ' : 'Notice Published: ');
        }

        return redirect()->back()->with('success', 'Notice updated successfully.');
    }

    public function publish(Notice $notice)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user)) {
            abort(403, 'Unauthorized.');
        }

        if (!$notice->published_at) {
            $notice->update(['published_at' => now()]);
            $this->notifyStudents($notice);
        }

        return redirect()->back()->with('success', 'Notice published successfully.');
    }

    public function destroy(Notice $notice)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user)) {
            abort(403, 'Unauthorized.');
        }

        foreach ($notice->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $notice->delete();

        return redirect()->back()->with('success', 'Notice deleted successfully.');
    }

    private function notifyStudents(Notice $notice, $prefix = 'Notice Published: ')
    {
        $studentsQuery = User::where('primary_category', 'Student');
        
        if ($notice->target_type === 'specific_course') {
            $studentsQuery->whereIn('id', function($q) use ($notice) {
                $q->select('course_enrollments.user_id')
                    ->from('course_enrollments')
                    ->join('course_intakes', 'course_enrollments.course_intake_id', '=', 'course_intakes.id')
                    ->where('course_intakes.course_id', $notice->course_id)
                    ->where('course_enrollments.enrollment_status', 'active');
            });
        }
        
        $students = $studentsQuery->get();
        
        foreach ($students as $student) {
            $student->notify(new SystemNotification(
                'Notice',
                $prefix . $notice->title,
                \Str::limit(strip_tags($notice->content), 100),
                route('notices.index'),
                ['notice_id' => $notice->id]
            ));
        }
    }
}
