<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::user() && Auth::user()->hasRole('student')) {
                abort(403, 'Unauthorized. Students cannot access client modules.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            abort(403, 'Unauthorized.');
        }

        // Active bank accounts of MSU
        $bankAccounts = BankAccount::where('is_active', true)->get();

        // History of payments uploaded by this client
        $payments = Payment::with(['quotation.serviceRequest', 'verifiedBy'])
            ->where('client_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Payments/Index', [
            'bankAccounts' => $bankAccounts,
            'payments' => $payments,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            abort(403, 'Unauthorized.');
        }

        // Fetch approved quotations for this client
        $quotations = Quotation::where('status', 'approved')
            ->whereHas('serviceRequest', function ($q) use ($user) {
                $q->where('submitted_by', $user->id);
            })
            ->with('serviceRequest')
            ->get();

        $selectedQuotationId = $request->input('quotation_id');

        return Inertia::render('Payments/Create', [
            'quotations' => $quotations,
            'selectedQuotationId' => $selectedQuotationId ? (int)$selectedQuotationId : null,
            'bankAccounts' => BankAccount::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'bank_used' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // max 10MB
            'notes' => 'nullable|string|max:1000',
        ]);

        $quotation = Quotation::findOrFail($request->quotation_id);

        // Security check: must belong to the logged-in client
        $serviceRequest = $quotation->serviceRequest;
        if ($serviceRequest->submitted_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->storeAs('proofs/' . time() . '_' . uniqid(), $file->getClientOriginalName(), 'public');

            Payment::create([
                'service_request_id' => $serviceRequest->id,
                'quotation_id' => $quotation->id,
                'client_id' => $user->id,
                'amount_paid' => $request->amount_paid,
                'bank_used' => $request->bank_used,
                'proof_file_path' => $filePath,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            return redirect()->route('payments.index')->with('success', 'Proof of payment uploaded successfully. Our finance department will verify it shortly.');
        }

        return redirect()->back()->with('error', 'Failed to upload proof of payment.');
    }

    public function viewProof(\App\Models\Payment $payment)
    {
        $user = Auth::user();
        
        // Administrative roles can access any payment proofs
        if ($user->hasRole('executive_director') || 
            $user->hasRole('deputy_director') || 
            $user->hasRole('ict_administrator') || 
            $user->hasRole('admin_assistant')) {
            // Authorized
        } else if ($user->hasRole('client')) {
            // Client can only see their own proof of payments
            if ($payment->client_id !== $user->id) {
                abort(403, 'Unauthorized.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $path = $payment->proof_file_path;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        $absolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        
        $filename = basename($path);
        
        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}

