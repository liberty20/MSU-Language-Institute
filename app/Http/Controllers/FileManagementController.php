<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\CourseApplication;
use App\Models\CourseEnrollment;
use App\Models\UploadedDocument;
use App\Models\Payment;
use App\Models\LearningContent;

class FileManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Preview a file inline in the browser.
     */
    public function preview($type, $id, $field = null)
    {
        return $this->serveFile($type, $id, $field, false);
    }

    /**
     * Force download a file.
     */
    public function download($type, $id, $field = null)
    {
        return $this->serveFile($type, $id, $field, true);
    }

    /**
     * Resolve file path, check authorization, and serve file.
     */
    protected function serveFile($type, $id, $field, $forceDownload)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $path = null;
        $originalName = null;
        $mimeType = null;

        // 1. Resolve and Authorize based on Document Type
        switch ($type) {
            case 'course_application':
                $application = CourseApplication::findOrFail($id);
                $isOwner = ($user->email === $application->email);
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);

                if (!$isOwner && !$isAdmin) {
                    abort(403, 'Unauthorized access to this application document.');
                }

                if ($field === 'national_id') {
                    $path = $application->national_id_copy_path;
                    $originalName = basename($path);
                } elseif ($field === 'payment_proof') {
                    $path = $application->payment_proof_path;
                    $originalName = basename($path);
                } else {
                    abort(400, 'Invalid field requested.');
                }
                break;

            case 'course_enrollment':
                $enrollment = CourseEnrollment::findOrFail($id);
                $isStudentOwner = ($user->id === $enrollment->user_id);
                $isInstructor = ($enrollment->intake && $user->id === $enrollment->intake->instructor_id);
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);

                if (!$isStudentOwner && !$isInstructor && !$isAdmin) {
                    abort(403, 'Unauthorized access to this enrollment document.');
                }

                $path = $enrollment->payment_proof_path;
                $originalName = basename($path);
                break;

            case 'uploaded_document':
                $document = UploadedDocument::findOrFail($id);
                $parent = $document->documentable;
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);
                $hasAccess = false;

                if ($isAdmin) {
                    $hasAccess = true;
                } elseif ($user->hasRole('client')) {
                    if ($parent instanceof \App\Models\ServiceRequest) {
                        $hasAccess = ($parent->submitted_by === $user->id);
                    } elseif ($parent instanceof \App\Models\Assignment) {
                        $hasAccess = ($parent->serviceRequest && $parent->serviceRequest->submitted_by === $user->id && $parent->serviceRequest->status === 'completed');
                    }
                } elseif ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
                    if ($parent instanceof \App\Models\ServiceRequest) {
                        $hasAccess = $parent->assignments()->where('assigned_to', $user->id)->exists();
                    } elseif ($parent instanceof \App\Models\Assignment) {
                        $hasAccess = ($parent->assigned_to === $user->id);
                    }
                } elseif ($parent instanceof \App\Models\Notice) {
                    if ($parent->published_at !== null) {
                        if ($user->primary_category === 'Student') {
                            if ($parent->target_type === 'all_students') {
                                $hasAccess = true;
                            } else {
                                $hasAccess = \App\Models\CourseEnrollment::where('user_id', $user->id)
                                    ->where('enrollment_status', 'active')
                                    ->whereIn('course_intake_id', function($q) use ($parent) {
                                        $q->select('id')
                                            ->from('course_intakes')
                                            ->where('course_id', $parent->course_id);
                                    })
                                    ->exists();
                            }
                        } else {
                            $hasAccess = true;
                        }
                    } else {
                        $hasAccess = ($user->primary_category === 'Staff');
                    }
                }

                if (!$hasAccess) {
                    abort(403, 'Unauthorized access to this document.');
                }

                $path = $document->file_path;
                $originalName = $document->filename;
                $mimeType = $document->mime_type;
                break;

            case 'announcement':
                $announcement = \App\Models\Announcement::findOrFail($id);
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);
                $isInstructor = ($user->id === $announcement->instructor_id);
                $isStudent = \App\Models\CourseEnrollment::where('user_id', $user->id)
                    ->where('enrollment_status', 'active')
                    ->whereIn('course_intake_id', function($q) use ($announcement) {
                        $q->select('id')
                            ->from('course_intakes')
                            ->where('course_id', $announcement->course_id);
                    })
                    ->exists();

                if (!$isAdmin && !$isInstructor && !$isStudent) {
                    abort(403, 'Unauthorized access to this announcement attachment.');
                }

                $path = $announcement->attachment_path;
                $originalName = basename($path);
                break;

            case 'payment':
                $payment = Payment::findOrFail($id);
                $isClientOwner = ($user->id === $payment->client_id);
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);

                if (!$isClientOwner && !$isAdmin) {
                    abort(403, 'Unauthorized access to this payment proof.');
                }

                $path = $payment->proof_file_path;
                $originalName = basename($path);
                break;

            case 'learning_content':
                $content = LearningContent::findOrFail($id);
                $isInstructor = ($content->intake && $user->id === $content->intake->instructor_id);
                $isStudent = CourseEnrollment::where('course_intake_id', $content->course_intake_id)
                    ->where('user_id', $user->id)
                    ->where('enrollment_status', 'active')
                    ->exists();
                $isAdmin = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant']);

                if (!$isInstructor && !$isStudent && !$isAdmin) {
                    abort(403, 'Unauthorized access to this learning content.');
                }

                $path = $content->file_path;
                $originalName = $content->file_name;
                $mimeType = $content->mime_type;
                break;

            default:
                abort(400, 'Invalid document type.');
        }

        // 2. Validate file existence
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Requested document not found on server.');
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (!$mimeType) {
            $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        }

        // 3. Resolve clean filename extension
        $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
        if ($ext && !str_ends_with(strtolower($originalName), '.' . strtolower($ext))) {
            $originalName .= '.' . $ext;
        }

        // 4. Return appropriate file response
        if ($forceDownload) {
            return response()->download($absolutePath, $originalName);
        } else {
            return response()->file($absolutePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $originalName . '"',
            ]);
        }
    }
}
