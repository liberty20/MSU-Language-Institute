<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::with(['generatedBy'])->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Reports/Index', [
            'reports' => $reports
        ]);
    }
}
