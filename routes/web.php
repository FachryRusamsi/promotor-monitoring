<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Promotor\AttendanceController;
use App\Http\Controllers\Promotor\TrackingController;
use App\Http\Controllers\Promotor\TransactionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ──────────────────────────────────────────────────────────
//  Authenticated — Generic (profile, etc.)
// ──────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ──────────────────────────────────────────────────────────
//  Default /dashboard — redirect based on role
// ──────────────────────────────────────────────────────────

Route::get('/dashboard', function () {
    $user = auth()->user();
    $user->loadMissing('role');

    return match (strtolower($user->role->name ?? '')) {
        'admin'    => redirect()->route('admin.dashboard'),
        'promotor' => redirect()->route('promotor.dashboard'),
        default    => redirect()->route('login'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// ──────────────────────────────────────────────────────────
//  Admin Routes — /admin/*
// ──────────────────────────────────────────────────────────

Route::prefix('admin')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');
            
        Route::get('/promotor/{user}/transactions', [AdminDashboardController::class, 'promotorTransactions'])
            ->name('promotor.transactions');
            
        Route::get('/monitoring', [AdminDashboardController::class, 'monitoring'])
            ->name('monitoring');

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/export', [ReportController::class, 'exportAchievement'])
            ->name('reports.export');

        // Promotor tracking queries (reads from Redis)
        Route::get('/tracking/{promotor}/latest', [TrackingController::class, 'latest'])
            ->name('tracking.latest');

        Route::get('/tracking/{promotor}/history', [TrackingController::class, 'history'])
            ->name('tracking.history');
    });

// ──────────────────────────────────────────────────────────
//  Promotor Routes — /promotor/*
// ──────────────────────────────────────────────────────────

Route::prefix('promotor')
    ->middleware(['auth', 'verified', 'role:promotor'])
    ->name('promotor.')
    ->group(function () {

        Route::get('/dashboard', [TrackingController::class, 'index'])
            ->name('dashboard');

        // GPS tracking (high-frequency, Redis-only)
        Route::post('/tracking/update', [TrackingController::class, 'update'])
            ->middleware('throttle:15,1')
            ->name('tracking.update');

        // Anti-cheat violation reports
        Route::post('/tracking/violation', [TrackingController::class, 'violation'])
            ->name('tracking.violation');

        Route::get('/attendance', [AttendanceController::class, 'index'])
            ->name('attendance.index');

        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->name('attendance.checkin');

        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
            ->name('attendance.checkout');

        Route::get('/transactions', [TransactionController::class, 'index'])
            ->name('transactions.index');

        Route::post('/transactions', [TransactionController::class, 'store'])
            ->name('transactions.store');

        Route::get('/edukasi', [\App\Http\Controllers\Promotor\EdukasiController::class, 'index'])
            ->name('edukasi.index');

        Route::post('/edukasi', [\App\Http\Controllers\Promotor\EdukasiController::class, 'store'])
            ->name('edukasi.store');
    });

require __DIR__.'/auth.php';
