<?php

use App\Http\Controllers\Api\SiteStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/sites/status', [SiteStatusController::class, 'update']);