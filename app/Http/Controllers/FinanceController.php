<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinanceController extends Controller
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
    private function checkAccess()
    {
        $user = Auth::user();
        if (!$user->hasRole('executive_director') && !$user->hasRole('deputy_director')) {
            abort(403, 'Unauthorized.');
        }
    }

    public function index()
    {
        $this->checkAccess();

        // Bank Accounts list
        $bankAccounts = BankAccount::orderBy('bank_name')->get();

        // Client Payments list (pending and verified/rejected)
        $payments = Payment::with(['client', 'quotation.serviceRequest', 'verifiedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Cumulative Verified Revenue
        $totalRevenue = Payment::where('status', 'verified')->sum('amount_paid');

        // Cumulative Verified Revenue by Currency
        $revenueByCurrency = Payment::where('payments.status', 'verified')
            ->join('quotations', 'payments.quotation_id', '=', 'quotations.id')
            ->selectRaw('quotations.currency, SUM(payments.amount_paid) as total')
            ->groupBy('quotations.currency')
            ->pluck('total', 'currency')
            ->toArray();

        return Inertia::render('Finance/Index', [
            'bankAccounts' => $bankAccounts,
            'payments' => $payments,
            'totalRevenue' => (float)$totalRevenue,
            'revenueByCurrency' => $revenueByCurrency,
        ]);
    }

        // Bank Account actions
    public function storeBankAccount(Request $request)
    {
        $this->checkAccess();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
            'currency' => 'required|string|in:USD,ZWL,ZAR',
            'branch_code' => 'nullable|string|max:100',
            'swift_code' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        BankAccount::create($validated);

        return redirect()->route('finance.index')->with('success', 'Bank account added successfully.');
    }

    public function updateBankAccount(Request $request, BankAccount $bankAccount)
    {
        $this->checkAccess();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number,' . $bankAccount->id,
            'currency' => 'required|string|in:USD,ZWL,ZAR',
            'branch_code' => 'nullable|string|max:100',
            'swift_code' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        $bankAccount->fill($validated);
        if (!$bankAccount->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $bankAccount->save();

        return redirect()->route('finance.index')->with('success', 'Bank account updated successfully.');
    }

    public function toggleBankAccount(BankAccount $bankAccount)
    {
        $this->checkAccess();

        $bankAccount->update([
            'is_active' => !$bankAccount->is_active,
        ]);

        return redirect()->route('finance.index')->with('success', 'Bank account status toggled successfully.');
    }

    // Payment verification actions
    public function verifyPayment(Request $request, Payment $payment)
    {
        $this->checkAccess();

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        $status = $request->action === 'approve' ? 'verified' : 'rejected';

        $payment->update([
            'status' => $status,
            'notes' => $request->notes,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $msg = $request->action === 'approve' ? 'Payment has been approved and verified.' : 'Payment has been rejected.';
        return redirect()->route('finance.index')->with('success', $msg);
    }
}
