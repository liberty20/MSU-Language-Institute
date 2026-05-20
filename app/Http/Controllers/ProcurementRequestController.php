<?php

namespace App\Http\Controllers;

use App\Models\ProcurementRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProcurementRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcurementRequest::with(['requestedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $procurements = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('ProcurementRequests/Index', [
            'procurements' => $procurements
        ]);
    }
}
