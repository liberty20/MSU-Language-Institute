<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && $user->primary_category === 'Student') {
                abort(403, 'Unauthorized. Students cannot access client modules.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ServiceRequest::with(['client', 'submittedBy']);

        // Scope queries so clients only see their own service requests
        if ($user->primary_category === 'Client') {
            $query->where('submitted_by', $user->id);
        }

        // Scope queries so staff only see their assigned requests where quotation is approved
        if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            })->whereHas('quotations', function ($q) {
                $q->where('status', 'approved');
            });
        }

        // Scope by department is disabled so that users in all units, except Administration and Operations Support, have access to the same modules and functionalities based on standard user permissions.
        /*
        if (!$user->hasRole('client') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }
        */

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                  ->orWhere('reference_number', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('category')) {
            $query->where('service_category', $request->category);
        } elseif ($request->filled('service')) {
            $query->where('service_category', $request->service);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $serviceRequests */
        $serviceRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('ServiceRequests/Index', [
            'serviceRequests' => $serviceRequests->withQueryString(),
            'filters'         => [
                'status'   => $request->status,
                'search'   => $request->search,
                'category' => $request->category ?? $request->service,
                'service'  => $request->service ?? $request->category,
            ],
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        if ($user->hasRole('client')) {
            $client = Client::where('email', $user->email)->first();
            if (!$client) {
                $client = Client::create([
                    'client_type' => 'individual',
                    'contact_person' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => 'active',
                ]);
            }
            return Inertia::render('ServiceRequests/Create', [
                'clients' => $client ? [$client] : [],
                'default_client_id' => $client ? $client->id : null,
            ]);
        }

        return Inertia::render('ServiceRequests/Create', [
            'clients' => Client::where('status', 'active')->orderBy('contact_person')->get(),
            'default_client_id' => null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('client')) {
            $client = Client::where('email', $user->email)->first();
            if (!$client) {
                $client = Client::create([
                    'client_type' => 'individual',
                    'contact_person' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => 'active',
                ]);
            }
            $request->merge(['client_id' => $client->id]);
        }

        $validated = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'service_category'   => 'required|in:translation,editing,brailling,sign_language,consultancy,short_courses',
            'title'              => 'required|string|max:255',
            'description'        => 'required|string',
            'source_language'    => 'nullable|string|max:100',
            'target_language'    => 'nullable|array',
            'target_language.*'  => 'string|max:100',
            'priority'           => 'required|in:low,medium,high,urgent',
            'deadline'           => 'nullable|date|after:today',
            'notes'              => 'nullable|string',
            'files.*'            => 'nullable|file|max:10240', // max 10MB each
        ]);

        $validated['submitted_by'] = Auth::id();
        $validated['status']       = 'pending';

        // Auto-assign department based on service category
        $deptCode = null;
        if (in_array($validated['service_category'], ['translation', 'editing', 'consultancy'])) {
            $deptCode = 'LCSU';
        } elseif (in_array($validated['service_category'], ['brailling', 'sign_language'])) {
            $deptCode = 'SNSU';
        } elseif ($validated['service_category'] === 'short_courses') {
            $deptCode = 'ILASU';
        }
        
        if ($deptCode) {
            $dept = \DB::table('departments')->where('code', $deptCode)->first();
            if ($dept) {
                $validated['department_id'] = $dept->id;
            }
        }

        $serviceRequest = ServiceRequest::create($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $file->storeAs('documents/' . time() . '_' . uniqid(), $filename, 'public');
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();

                $serviceRequest->documents()->create([
                    'uploaded_by' => Auth::id(),
                    'filename'    => $filename,
                    'file_path'   => $filePath,
                    'file_size'   => $fileSize,
                    'mime_type'   => $mimeType,
                    'description' => 'Attached with service request creation',
                ]);
            }
        }

        return redirect()->route('service-requests.index')->with('success', 'Service request submitted successfully.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        
        // Scope checks for show
        if ($user->hasRole('client') && $serviceRequest->submitted_by !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
            $hasAccess = $serviceRequest->assignments()->where('assigned_to', $user->id)->exists() &&
                         $serviceRequest->quotations()->where('status', 'approved')->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized.');
            }
        }

        if ($user->hasRole('client')) {
            $serviceRequest->load([
                'client', 
                'submittedBy', 
                'assignedTo', 
                'quotations' => function ($q) {
                    $q->where('status', 'approved')->with('preparedBy');
                }, 
                'assignments.assignedTo',
                'documents.uploader',
                'assignments.documents.uploader',
                'payments.quotation'
            ]);
        } else {
            $serviceRequest->load([
                'client', 
                'submittedBy', 
                'assignedTo', 
                'quotations.preparedBy', 
                'quotations.approvals.approver', 
                'assignments.assignedTo',
                'documents.uploader',
                'assignments.documents.uploader',
                'payments.verifiedBy',
                'payments.quotation',
                'ccReviews.sender',
                'ccReviews.reviewer'
            ]);
        }
        
        $coordinators = [];
        if ($user->hasRole('coordinator')) {
            $coordinators = \App\Models\User::role('coordinator')->where('id', '!=', $user->id)->get(['id', 'name', 'email']);
        }
        $directors = [];
        if ($user->hasRole('coordinator')) {
            $directors = \App\Models\User::role(['executive_director', 'deputy_director'])->get(['id', 'name', 'email']);
        }

        $config = \App\Models\SystemSetting::get('deputy_system_config', []);
        $directRoutingEnabled = $config['deliverable_direct_routing'] ?? false;

        return Inertia::render('ServiceRequests/Show', [
            'serviceRequest' => $serviceRequest,
            'coordinators'   => $coordinators,
            'directors'      => $directors,
            'directRoutingEnabled' => $directRoutingEnabled,
        ]);
    }

    public function rate(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();

        // Enforce authorization
        if ($serviceRequest->submitted_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($serviceRequest->status !== 'completed') {
            return redirect()->back()->with('error', 'Only completed service requests can be rated.');
        }

        $validated = $request->validate([
            'rating'          => 'required|integer|min:1|max:5',
            'review_comments' => 'nullable|string|max:1000',
        ]);

        $serviceRequest->update([
            'rating'          => $validated['rating'],
            'review_comments' => $validated['review_comments'],
        ]);

        return redirect()->back()->with('success', 'Thank you for your rating and feedback!');
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        return Inertia::render('ServiceRequests/Edit', [
            'serviceRequest' => $serviceRequest,
            'clients'        => Client::where('status', 'active')->orderBy('contact_person')->get(),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status'      => 'sometimes|in:pending,quoted,approved,in_progress,review,completed,cancelled',
            'priority'    => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'notes'       => 'nullable|string',
        ]);

        $serviceRequest->fill($validated);
        if (!$serviceRequest->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $serviceRequest->save();

        return redirect()->back()->with('success', 'Service request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();
        return redirect()->route('service-requests.index')->with('success', 'Service request deleted.');
    }

    public function reviews()
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            abort(403, 'Unauthorized.');
        }

        $serviceRequests = ServiceRequest::with('client')
             ->where('submitted_by', $user->id)
             ->where('status', 'completed')
             ->orderBy('created_at', 'desc')
             ->get();

        return Inertia::render('Reviews/Index', [
            'serviceRequests' => $serviceRequests
        ]);
    }

    public function attachDocument(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        
        // Scope checks for attach
        if ($user->hasRole('client') && $serviceRequest->submitted_by !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'file'        => 'required|file|max:10240', // max 10MB
            'description' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filePath = $file->storeAs('documents/' . time() . '_' . uniqid(), $filename, 'public');
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            $serviceRequest->documents()->create([
                'uploaded_by' => $user->id,
                'filename'    => $filename,
                'file_path'   => $filePath,
                'file_size'   => $fileSize,
                'mime_type'   => $mimeType,
                'description' => $request->input('description', 'Client uploaded attachment'),
            ]);
        }

        return redirect()->back()->with('success', 'Document attached successfully.');
    }

    public function deliverRequest(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin_assistant')) {
            abort(403, 'Unauthorized action.');
        }

        $hasVerifiedPayment = $serviceRequest->payments()->where('status', 'verified')->exists();
        if (!$hasVerifiedPayment) {
            return redirect()->back()->with('error', 'Cannot deliver task: No verified proof of payment has been uploaded or verified for this service request.');
        }

        $serviceRequest->status = 'completed';
        $serviceRequest->notes = $request->input('notes', $serviceRequest->notes);
        $serviceRequest->completed_at = now();
        $serviceRequest->save();

        \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $serviceRequest->id);

        // Audit Trail Entry
        $completedAssignment = $serviceRequest->assignments()->where('status', 'completed')->first();
        $document = $completedAssignment ? $completedAssignment->documents()->first() : null;
        $docName = $document ? $document->filename : 'Deliverable';
        $clientName = $serviceRequest->client ? ($serviceRequest->client->organization ?? $serviceRequest->client->contact_person) : 'Client';
        
        $auditProperties = [
            'document_submitted' => $docName,
            'submitting_user' => $user->name,
            'client_recipient' => $clientName,
            'submission_timestamp' => now()->toIso8601String(),
            'action' => 'Deliverable Submitted to Client',
        ];

        \App\Models\ActivityLog::log(
            'deliverable_submitted_to_client',
            "Deliverable '{$docName}' submitted to client '{$clientName}' by Admin Assistant {$user->name}",
            $serviceRequest,
            $auditProperties,
            $user->id
        );

        // Real-time Notification for Executive Director and Deputy Director
        $directors = \App\Models\User::role(['executive_director', 'deputy_director'])->get();
        foreach ($directors as $director) {
            $director->notify(new \App\Notifications\SystemNotification(
                'deliverable_submission',
                'Deliverable Submitted to Client',
                "The deliverable for request {$serviceRequest->reference_number} has been submitted to client '{$clientName}' by Admin Assistant {$user->name}.",
                route('service-requests.show', $serviceRequest->id),
                ['service_request_id' => $serviceRequest->id]
            ));
        }

        return redirect()->back()->with('success', 'Completed task successfully sent to the client.');
    }

    public function completedTasksIndex(Request $request)
    {
        $user = Auth::user();
        $allowedRoles = ['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'client', 'coordinator', 'secretary'];
        
        $hasAllowedRole = false;
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                $hasAllowedRole = true;
                break;
            }
        }
        
        if (!$hasAllowedRole) {
            abort(403, 'Unauthorized.');
        }

        $query = ServiceRequest::with(['client', 'submittedBy', 'assignedTo', 'documents.uploader', 'assignments.documents.uploader', 'assignments.assignedTo']);

        if ($user->hasRole('client')) {
            $query->where('submitted_by', $user->id)
                  ->where('status', 'completed');
        } else {
            $query->whereIn('status', ['review', 'director_approval', 'admin_submission', 'completed']);
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            // Scope by department is disabled so that users in all units, except Administration and Operations Support, have access to the same modules and functionalities based on standard user permissions.
            /*
            if ($user->department_id) {
                $query->where('department_id', $user->department_id);
            }
            */
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $serviceRequests */
        $serviceRequests = $query->orderBy('updated_at', 'desc')->paginate(10);

        return Inertia::render('ServiceRequests/CompletedTasks', [
            'serviceRequests' => $serviceRequests->withQueryString(),
            'filters'         => $user->hasRole('client') ? [] : $request->only(['status']),
        ]);
    }

    public function downloadDocument(\App\Models\UploadedDocument $document)
    {
        $user = Auth::user();
        
        // Administrative roles can access any documents
        if ($user->hasRole('executive_director') || 
            $user->hasRole('deputy_director') || 
            $user->hasRole('ict_administrator') || 
            $user->hasRole('coordinator') || 
            $user->hasRole('admin_assistant')) {
            // Authorized
        } else if ($user->hasRole('client')) {
            $parent = $document->documentable;
            if ($parent instanceof ServiceRequest) {
                if ($parent->submitted_by !== $user->id) {
                    abort(403, 'Unauthorized.');
                }
            } elseif ($parent instanceof \App\Models\Assignment) {
                if ($parent->serviceRequest->submitted_by !== $user->id) {
                    abort(403, 'Unauthorized.');
                }
                if ($parent->serviceRequest->status !== 'completed') {
                    abort(403, 'Unauthorized. Completed deliverables are pending review and have not been delivered to you yet.');
                }
            } else {
                abort(403, 'Unauthorized.');
            }
        } else if ($user->hasRole('language_expert') || $user->hasRole('part_time_staff')) {
            $parent = $document->documentable;
            if ($parent instanceof ServiceRequest) {
                $hasAccess = $parent->assignments()->where('assigned_to', $user->id)->exists();
                if (!$hasAccess) {
                    abort(403, 'Unauthorized.');
                }
            } elseif ($parent instanceof \App\Models\Assignment) {
                if ($parent->assigned_to !== $user->id) {
                    abort(403, 'Unauthorized.');
                }
            } else {
                abort(403, 'Unauthorized.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $path = $document->file_path;
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        $absolutePath = storage_path('app/public/' . $path);
        
        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . $document->filename . '"',
        ]);
    }

    public function ccReview(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('coordinator') && !$user->hasRole('admin_assistant')) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'reviewer_ids' => 'required|array',
            'reviewer_ids.*' => 'exists:users,id',
            'notes' => 'nullable|string',
        ]);

        foreach ($request->reviewer_ids as $reviewerId) {
            \DB::table('cc_reviews')->insert([
                'service_request_id' => $serviceRequest->id,
                'sender_id' => $user->id,
                'reviewer_id' => $reviewerId,
                'status' => 'pending',
                'comments' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $reviewer = \App\Models\User::find($reviewerId);
            if ($reviewer) {
                $reviewer->notify(new \App\Notifications\SystemNotification(
                    'cc_review_request',
                    'CC Review Request',
                    "Coordinator {$user->name} requested your review on deliverable for request {$serviceRequest->reference_number}.",
                    route('service-requests.show', $serviceRequest->id)
                ));
            }
        }

        \App\Models\ActivityLog::log(
            'cc_review_requested',
            "Coordinator {$user->name} requested CC review on request {$serviceRequest->reference_number}.",
            $serviceRequest,
            ['reviewer_ids' => $request->reviewer_ids, 'notes' => $request->notes]
        );

        return redirect()->back()->with('success', 'CC review request sent successfully.');
    }

    public function respondCcReview(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('coordinator')) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'comments' => 'required|string',
            'status' => 'required|in:approved,rejected',
        ]);

        $ccReview = \DB::table('cc_reviews')->where('id', $id)->first();
        if (!$ccReview || (int)$ccReview->reviewer_id !== (int)$user->id) {
            abort(403, 'Unauthorized.');
        }

        \DB::table('cc_reviews')->where('id', $id)->update([
            'status' => $request->status === 'approved' ? 'reviewed' : 'rejected',
            'comments' => $request->comments,
            'updated_at' => now(),
        ]);

        $ccReview = \DB::table('cc_reviews')->where('id', $id)->first();
        if ($ccReview) {
            \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $ccReview->service_request_id, $user->id);
        }

        $serviceRequest = ServiceRequest::find($ccReview->service_request_id);

        \App\Models\ActivityLog::log(
            'cc_review_responded',
            "Coordinator {$user->name} responded to CC review request for request {$serviceRequest->reference_number}: {$request->status}.",
            $serviceRequest,
            ['comments' => $request->comments, 'status' => $request->status]
        );

        $sender = \App\Models\User::find($ccReview->sender_id);
        if ($sender && $serviceRequest) {
            $sender->notify(new \App\Notifications\SystemNotification(
                'cc_review_response',
                'CC Review Completed',
                "Coordinator {$user->name} completed review on request {$serviceRequest->reference_number}: {$request->status}.",
                route('service-requests.show', $serviceRequest->id)
            ));
        }

        return redirect()->back()->with('success', 'CC review response submitted.');
    }

    public function forwardToDirector(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('coordinator')) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'director_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $serviceRequest->status = 'director_approval';
        $serviceRequest->save();

        \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $serviceRequest->id);

        $director = \App\Models\User::find($request->director_id);
        if ($director) {
            $director->notify(new \App\Notifications\SystemNotification(
                'director_approval_request',
                'Approval Request',
                "Coordinator {$user->name} has forwarded the deliverable for request {$serviceRequest->reference_number} to you for final approval.",
                route('deliverable-approvals.show', $serviceRequest->id)
            ));
        }

        \App\Models\ActivityLog::log(
            'forwarded_to_director',
            "Coordinator {$user->name} forwarded deliverable for request {$serviceRequest->reference_number} to Director/Deputy Director.",
            $serviceRequest,
            ['forwarded_to' => $request->director_id, 'notes' => $request->notes]
        );

        return redirect()->back()->with('success', 'Deliverable successfully forwarded to Director for approval.');
    }

    public function directorApprove(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('executive_director') && !$user->hasRole('deputy_director') && !$user->hasRole('coordinator')) {
            abort(403, 'Unauthorized.');
        }

        $serviceRequest->status = 'admin_submission';
        $serviceRequest->save();

        \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $serviceRequest->id);

        $adminAssistants = \App\Models\User::role('admin_assistant')->get();
        foreach ($adminAssistants as $aa) {
            $aa->notify(new \App\Notifications\SystemNotification(
                'admin_submission_pending',
                'Deliverable Ready for Submission',
                "The deliverable for request {$serviceRequest->reference_number} has been approved by {$user->name} and is ready for submission to the client.",
                route('service-requests.show', $serviceRequest->id)
            ));
        }

        \App\Models\ActivityLog::log(
            'director_approved_deliverable',
            "Director {$user->name} approved deliverable for request {$serviceRequest->reference_number}.",
            $serviceRequest,
            ['notes' => $request->input('notes')]
        );

        return redirect()->back()->with('success', 'Deliverable approved. Automatically routed to Administrative Assistant.');
    }

    public function directorReject(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('executive_director') && !$user->hasRole('deputy_director') && !$user->hasRole('coordinator')) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $serviceRequest->status = 'review';
        $serviceRequest->save();

        \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $serviceRequest->id);

        $coordinators = \App\Models\User::role('coordinator')->get();
        foreach ($coordinators as $coordinator) {
            $coordinator->notify(new \App\Notifications\SystemNotification(
                'director_rejected_deliverable',
                'Deliverable Rejected',
                "Director {$user->name} rejected the deliverable for request {$serviceRequest->reference_number}. Reason: {$request->reason}",
                route('service-requests.show', $serviceRequest->id)
            ));
        }

        \App\Models\ActivityLog::log(
            'director_rejected_deliverable',
            "Director {$user->name} rejected deliverable for request {$serviceRequest->reference_number} due to: {$request->reason}.",
            $serviceRequest,
            ['reason' => $request->reason]
        );

        return redirect()->back()->with('success', 'Deliverable rejected and returned to Coordinator.');
    }

    public function directorApprovalView(ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if ($serviceRequest->status !== 'director_approval') {
            abort(403, 'Unauthorized. No deliverable is pending director approval for this request.');
        }

        $serviceRequest->load([
            'client', 
            'submittedBy', 
            'assignedTo', 
            'quotations.preparedBy', 
            'quotations.approvals.approver', 
            'assignments.assignedTo',
            'documents.uploader',
            'assignments.documents.uploader',
            'payments.verifiedBy',
            'payments.quotation',
            'ccReviews.sender',
            'ccReviews.reviewer'
        ]);

        $coordinators = \App\Models\User::role('coordinator')->get(['id', 'name', 'email']);
        $directors = \App\Models\User::role(['executive_director', 'deputy_director'])->get(['id', 'name', 'email']);

        $config = \App\Models\SystemSetting::get('deputy_system_config', []);
        $directRoutingEnabled = $config['deliverable_direct_routing'] ?? false;

        return Inertia::render('ServiceRequests/Show', [
            'serviceRequest' => $serviceRequest,
            'coordinators'   => $coordinators,
            'directors'      => $directors,
            'isDirectorApprovalView' => true,
            'directRoutingEnabled' => $directRoutingEnabled,
        ]);
    }

    public function coordinatorApprove(Request $request, ServiceRequest $serviceRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole('coordinator')) {
            abort(403, 'Unauthorized.');
        }

        $config = \App\Models\SystemSetting::get('deputy_system_config', []);
        $directRoutingPermitted = $config['deliverable_direct_routing'] ?? false;
        
        if (!$directRoutingPermitted) {
            abort(403, 'Direct routing is not permitted by system configuration.');
        }

        $serviceRequest->status = 'admin_submission';
        $serviceRequest->save();

        \App\Services\ReminderService::markAsCompleted(ServiceRequest::class, $serviceRequest->id);

        $adminAssistants = \App\Models\User::role('admin_assistant')->get();
        foreach ($adminAssistants as $aa) {
            $aa->notify(new \App\Notifications\SystemNotification(
                'admin_submission_pending',
                'Deliverable Ready for Submission',
                "The deliverable for request {$serviceRequest->reference_number} has been approved by Coordinator {$user->name} and is ready for submission to the client.",
                route('service-requests.show', $serviceRequest->id)
            ));
        }

        \App\Models\ActivityLog::log(
            'coordinator_approved_deliverable',
            "Coordinator {$user->name} approved deliverable directly for request {$serviceRequest->reference_number} (Bypassed Director).",
            $serviceRequest,
            ['notes' => $request->input('notes')]
        );

        return redirect()->back()->with('success', 'Deliverable approved directly and routed to Administrative Assistant.');
    }
}
