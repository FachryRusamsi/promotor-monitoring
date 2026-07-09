<?php

namespace App\Http\Controllers\Promotor;

use App\Events\PromotorLocationUpdated;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    /**
     * Redis key TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Show the promotor dashboard / tracking page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = now()->toDateString();

        $attendanceService = new AttendanceService();
        $attendanceStatus = $attendanceService->getTodayAttendanceStatus($user);

        $transactions = $user->transactions()
            ->whereDate('transaction_date', $today)
            ->get();

        $totalEdukasi = $transactions->sum('jml_edukasi');
        $totalPenjualan = $transactions->sum('jml_sp') + $transactions->sum('jml_pulsa');
        $totalAktivasi = $transactions->sum('jml_aktivasi_gemini');

        return Inertia::render('Promotor/Dashboard', [
            'metrics' => [
                'edukasi' => $totalEdukasi,
                'penjualan' => $totalPenjualan,
                'aktivasi' => $totalAktivasi,
            ],
            'attendanceStatus' => $attendanceStatus,
        ]);
    }

    /**
     * Receive GPS coordinates from frontend.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'accuracy'    => 'nullable|numeric|min:0',
            'speed'       => 'nullable|numeric',
            'heading'     => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();

        $payload = [
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'latitude'    => (float) $request->latitude,
            'longitude'   => (float) $request->longitude,
            'accuracy'    => $request->accuracy,
            'speed'       => $request->speed,
            'heading'     => $request->heading,
            'recorded_at' => $request->recorded_at ?? now()->toISOString(),
            'server_at'   => now()->toISOString(),
        ];

        // Store latest position
        $cacheKey = "promotor:location:{$user->id}";
        Cache::put($cacheKey, $payload, self::CACHE_TTL);

        // Store recent history (max 50 points)
        $historyKey = "promotor:location:{$user->id}:history";
        $history = Cache::get($historyKey, []);

        $history[] = $payload;

        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        Cache::put($historyKey, $history, self::CACHE_TTL);

        // Broadcast realtime update
        broadcast(new PromotorLocationUpdated($payload))->toOthers();

        return response()->json([
            'status' => 'ok',
        ]);
    }

    /**
     * Receive anti-cheat violation reports.
     */
    public function violation(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:tab_hidden,gps_denied,gps_unavailable,gps_timeout',
            'timestamp' => 'nullable|date',
        ]);

        $user = $request->user();

        $violation = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'type' => $request->type,
            'timestamp' => $request->timestamp ?? now()->toISOString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $violationKey = "promotor:violations:{$user->id}";

        $violations = Cache::get($violationKey, []);
        $violations[] = $violation;

        Cache::put($violationKey, $violations, 86400);

        Log::warning('[AntiCheat] Violation detected', $violation);

        return response()->json([
            'status' => 'recorded',
        ]);
    }

    /**
     * Get latest location.
     */
    public function latest(int $promotorId): JsonResponse
    {
        $cacheKey = "promotor:location:{$promotorId}";
        $location = Cache::get($cacheKey);

        if (!$location) {
            return response()->json([
                'status' => 'offline',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'online',
            'data' => $location,
        ]);
    }

    /**
     * Get recent location history.
     */
    public function history(int $promotorId): JsonResponse
    {
        $historyKey = "promotor:location:{$promotorId}:history";

        $history = Cache::get($historyKey, []);

        return response()->json([
            'data' => $history,
            'count' => count($history),
        ]);
    }
}