<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PowerBiController;

Route::prefix('powerbi')->group(function () {
    Route::get('/transactions', [PowerBiController::class, 'transactions']);
    Route::get('/attendances', [PowerBiController::class, 'attendances']);
});
