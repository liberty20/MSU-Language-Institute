<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ApprovalController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('service-requests', ServiceRequestController::class);
    Route::apiResource('quotations', QuotationController::class);
    Route::post('approvals', [ApprovalController::class, 'store']);
});
