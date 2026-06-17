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
            if (Auth::user() && Auth::user()->primary_category === 'Student') {
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

        return Inertia::render('ServiceRequests/Index', [
            'serviceRequests' => $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString(),
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
            'client_id'        => 'required|exists:clients,id',
            'service_category' => 'required|in:translation,editing,brailling,sign_language,consultancy,short_courses',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'source_language'  => 'nullable|string|max:100',
            'target_language'  => 'nullable|string|max:100',
            'priority'         => 'required|in:low,medium,high,urgent',
            'deadline'         => 'nullable|date|after:today',
            'notes'            => 'nullable|string',
            'files.*'          => 'nullable|file|max:10240', // max 10MB each
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
                $filePath = $file->store('documents', 'public');
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
                'payments.quotation'
            ]);
        }
        return Inertia::render('ServiceRequests/Show', ['serviceRequest' => $serviceRequest]);
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
            $filePath = $file->store('documents', 'public');
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
        if (!$user->hasRole('executive_director') && !$user->hasRole('deputy_director')) {
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

        return redirect()->back()->with('success', 'Completed task successfully sent to the client.');
    }

    public function completedTasksIndex(Request $request)
    {
        $user = Auth::user();
        $allowedRoles = ['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'client'];
        
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
            $query->whereIn('status', ['review', 'completed']);
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

        return Inertia::render('ServiceRequests/CompletedTasks', [
            'serviceRequests' => $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString(),
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
            $user->hasRole('admin_assistant')) {
            // Authorized
        } else if ($user->hasRole('client')) {
            $parent = $document->documentable;
            if ($parent instanceof \App\Models\ServiceRequest) {
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
            if ($parent instanceof \App\Models\ServiceRequest) {
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

        $absolutePath = Storage::disk('public')->path($path);
        
        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . $document->filename . '"',
        ]);
    }
}
