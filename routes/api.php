<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatusApiController;
use App\Http\Controllers\Api\SiteStatusController;

Route::post('/sites/status', [SiteStatusController::class, 'update']);
// Status Update API for Monitor Script
Route::post('/sites/update-status', [StatusApiController::class, 'updateStatus']);
Route::post('/sites/bulk-update-status', [StatusApiController::class, 'bulkUpdateStatus']);