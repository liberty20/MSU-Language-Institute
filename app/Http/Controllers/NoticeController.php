<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Course;
use App\Models\UploadedDocument;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['publicIndex', 'liveData', 'downloadAttachment']);
    }

    private function isAdmin($user)
    {
        return $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary']);
    }

    public function index()
    {
        $user = Auth::user();
        \Log::info("Notices index requested", ['user_id' => $user->id, 'primary_category' => $user->primary_category]);
        
        if ($user->primary_category === 'Student') {
            $activeEnrollments = CourseEnrollment::where('user_id', $user->id)
                ->where('enrollment_status', 'active')
                ->with('intake')
                ->get();

            if ($activeEnrollments->isEmpty()) {
                $notices = collect();
            } else {
                $notices = Notice::whereNotNull('published_at')
                    ->where(function($query) use ($activeEnrollments) {
                        $query->where(function($q) use ($activeEnrollments) {
                            $q->where('target_type', 'all_students')
                              ->where(function($sub) use ($activeEnrollments) {
                                  foreach ($activeEnrollments as $enrollment) {
                                      $sub->orWhere('published_at', '>=', $enrollment->created_at);
                                  }
                              });
                        })
                        ->orWhere(function($q) use ($activeEnrollments) {
                            $q->where('target_type', 'specific_course')
                              ->where(function($sub) use ($activeEnrollments) {
                                  foreach ($activeEnrollments as $enrollment) {
                                      $courseId = $enrollment->intake->course_id;
                                      $sub->orWhere(function($courseSub) use ($courseId, $enrollment) {
                                          $courseSub->where('course_id', $courseId)
                                                    ->where('published_at', '>=', $enrollment->created_at);
                                      });
                                  }
                              });
                        });
                    })
                    ->with(['creator', 'documents', 'course'])
                    ->orderBy('published_at', 'desc')
                    ->get();
            }
                
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
            'target_type' => 'required|in:publish_to_portal,all_students,specific_course',
            'course_id' => 'required_if:target_type,specific_course|nullable|exists:courses,id',
            'publish_immediately' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB each
        ]);

        \Log::info("Notice creation initiated", [
            'creator_id' => $user->id,
            'title' => $validated['title'],
            'target_type' => $validated['target_type'],
            'course_id' => $validated['course_id'] ?? null,
        ]);

        $isPublishToPortal = $validated['target_type'] === 'publish_to_portal';
        $notice = Notice::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'course_id' => $validated['target_type'] === 'specific_course' ? $validated['course_id'] : null,
            'published_at' => ($isPublishToPortal || $request->publish_immediately) ? now() : null,
            'created_by' => $user->id,
        ]);

        \Log::info("Notice created successfully", ['notice_id' => $notice->id]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $file->storeAs('documents/' . time() . '_' . uniqid(), $filename, 'public');
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
            'target_type' => 'required|in:publish_to_portal,all_students,specific_course',
            'course_id' => 'required_if:target_type,specific_course|nullable|exists:courses,id',
            'publish' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'deleted_file_ids' => 'nullable|array',
            'deleted_file_ids.*' => 'exists:uploaded_documents,id',
        ]);

        \Log::info("Notice update initiated", [
            'editor_id' => $user->id,
            'notice_id' => $notice->id,
            'old_target_type' => $notice->target_type,
            'old_course_id' => $notice->course_id,
            'new_target_type' => $validated['target_type'],
            'new_course_id' => $validated['course_id'] ?? null,
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

        $isPublishToPortal = $validated['target_type'] === 'publish_to_portal';
        $wasPublished = !is_null($notice->published_at);
        $shouldPublish = $request->publish || $wasPublished || $isPublishToPortal;

        $notice->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_type' => $validated['target_type'],
            'course_id' => $validated['target_type'] === 'specific_course' ? $validated['course_id'] : null,
            'published_at' => $shouldPublish ? ($notice->published_at ?? now()) : null,
        ]);

        \Log::info("Notice updated successfully", ['notice_id' => $notice->id]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $file->storeAs('documents/' . time() . '_' . uniqid(), $filename, 'public');
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
            \Log::info("Publishing notice", ['notice_id' => $notice->id]);
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

        \Log::info("Notice deletion initiated", ['notice_id' => $notice->id]);

        foreach ($notice->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $notice->delete();
        \Log::info("Notice deleted successfully", ['notice_id' => $notice->id]);

        return redirect()->back()->with('success', 'Notice deleted successfully.');
    }

    private function notifyStudents(Notice $notice, $prefix = 'Notice Published: ')
    {
        if ($notice->target_type === 'publish_to_portal') {
            \Log::info("Skipping student notification: notice targeted to landing portal", ['notice_id' => $notice->id]);
            return;
        }

        \Log::info("Resolving notice recipients", [
            'notice_id' => $notice->id,
            'target_type' => $notice->target_type,
            'course_id' => $notice->course_id,
        ]);

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

        \Log::info("Notice recipients resolved", [
            'notice_id' => $notice->id,
            'recipients_count' => count($students),
            'recipient_ids' => $students->pluck('id')->toArray(),
        ]);
        
        foreach ($students as $student) {
            \Log::info("Dispatching notification", ['notice_id' => $notice->id, 'recipient_id' => $student->id]);
            $student->notify(new SystemNotification(
                'Notice',
                $prefix . $notice->title,
                \Str::limit(strip_tags($notice->content), 100),
                route('notices.index'),
                ['notice_id' => $notice->id]
            ));
        }
    }

    public function publicIndex(Request $request)
    {
        $notices = Notice::whereNotNull('published_at')
            ->with(['creator', 'course', 'documents'])
            ->orderBy('published_at', 'desc')
            ->get();

        $courses = Course::orderBy('title')->get(['id', 'title', 'code']);

        return Inertia::render('Notices/PublicIndex', [
            'notices' => $notices,
            'courses' => $courses,
        ]);
    }

    public function liveData()
    {
        $notices = Notice::whereNotNull('published_at')
            ->where('target_type', 'publish_to_portal')
            ->with(['creator', 'course', 'documents'])
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json([
            'notices' => $notices,
        ]);
    }

    public function liveDataAuthenticated()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['notices' => []]);
        }

        \Log::info("Authenticated notices live data requested", ['user_id' => $user->id, 'primary_category' => $user->primary_category]);

        if ($user->primary_category === 'Student') {
            $activeEnrollments = CourseEnrollment::where('user_id', $user->id)
                ->where('enrollment_status', 'active')
                ->with('intake')
                ->get();

            if ($activeEnrollments->isEmpty()) {
                $notices = collect();
            } else {
                $notices = Notice::whereNotNull('published_at')
                    ->where(function($query) use ($activeEnrollments) {
                        $query->where(function($q) use ($activeEnrollments) {
                            $q->where('target_type', 'all_students')
                              ->where(function($sub) use ($activeEnrollments) {
                                  foreach ($activeEnrollments as $enrollment) {
                                      $sub->orWhere('published_at', '>=', $enrollment->created_at);
                                  }
                              });
                        })
                        ->orWhere(function($q) use ($activeEnrollments) {
                            $q->where('target_type', 'specific_course')
                              ->where(function($sub) use ($activeEnrollments) {
                                  foreach ($activeEnrollments as $enrollment) {
                                      $courseId = $enrollment->intake->course_id;
                                      $sub->orWhere(function($courseSub) use ($courseId, $enrollment) {
                                          $courseSub->where('course_id', $courseId)
                                                    ->where('published_at', '>=', $enrollment->created_at);
                                      });
                                  }
                              });
                        });
                    })
                    ->with(['creator', 'documents', 'course'])
                    ->orderBy('published_at', 'desc')
                    ->get();
            }
        } else {
            // Admin users see all notices
            $notices = Notice::with(['creator', 'course', 'documents'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'notices' => $notices
        ]);
    }

    public function downloadAttachment($id)
    {
        $doc = UploadedDocument::findOrFail($id);
        if ($doc->documentable_type !== Notice::class) {
            abort(403, 'Unauthorized.');
        }

        if (!$doc->file_path || !Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'File not found.');
        }

        $absolutePath = Storage::disk('public')->path($doc->file_path);
        return response()->download($absolutePath, $doc->filename);
    }
}
