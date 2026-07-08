<?php

namespace App\Http\Controllers\Promotor;

use App\Events\PromotorLocationUpdated;
use App\Http\Controllers\Controller;
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

        // 1. Get Attendance Status
        $attendance = $user->attendances()->whereDate('work_date', $today)->latest()->first();
        $attendanceStatus = 'Belum Check In';
        if ($attendance) {
            $attendanceStatus = $attendance->check_out_time ? 'Sudah Check Out' : 'Sudah Check In';
        }

        // 2. Get Today's Metrics
        $transactions = $user->transactions()->whereDate('transaction_date', $today)->get();
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
     * Receive GPS coordinates from the frontend.
     *
     * Strategy: Store in Redis only (no MySQL insert) for high-throughput
     * real-time tracking. The key auto-expires after 5 minutes so stale
     * locations are automatically pruned.
     *
     * Redis key format:
     *   promotor:location:{user_id}          → latest position (single key, overwritten)
     *   promotor:location:{user_id}:history  → recent position log (list, capped)
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
            'latitude'    => (float) $request->input('latitude'),
            'longitude'   => (float) $request->input('longitude'),
            'accuracy'    => $request->input('accuracy'),
            'speed'       => $request->input('speed'),
            'heading'     => $request->input('heading'),
            'recorded_at' => $request->input('recorded_at', now()->toISOString()),
            'server_at'   => now()->toISOString(),
        ];

        // ── Store latest position in Redis (overwrites previous) ──
        $cacheKey = "promotor:location:{$user->id}";
        Cache::store('redis')->put($cacheKey, $payload, self::CACHE_TTL);

        // ── Push to a capped history list for trail/path rendering ──
        $historyKey = "promotor:location:{$user->id}:history";
        $history = Cache::store('redis')->get($historyKey, []);
        $history[] = $payload;

        // Keep only last 50 entries to avoid memory bloat
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        Cache::store('redis')->put($historyKey, $history, self::CACHE_TTL);

        // ── Broadcast via Laravel Reverb for real-time admin dashboard ──
        broadcast(new PromotorLocationUpdated($payload))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Receive anti-cheat violation reports from the frontend.
     *
     * Violations: tab_hidden, gps_denied, gps_unavailable, gps_timeout
     */
    public function violation(Request $request): JsonResponse
    {
        $request->validate([
            'type'      => 'required|string|in:tab_hidden,gps_denied,gps_unavailable,gps_timeout',
            'timestamp' => 'nullable|date',
        ]);

        $user = $request->user();

        $violation = [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'type'      => $request->input('type'),
            'timestamp' => $request->input('timestamp', now()->toISOString()),
            'ip'        => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Store in Redis for admin review (append to list, expire 24h)
        $violationKey = "promotor:violations:{$user->id}";
        $violations = Cache::store('redis')->get($violationKey, []);
        $violations[] = $violation;
        Cache::store('redis')->put($violationKey, $violations, 86400); // 24 hours

        Log::warning('[AntiCheat] Violation detected', $violation);

        return response()->json(['status' => 'recorded']);
    }

    /**
     * Get the latest known position of a specific promotor.
     * Used by the admin monitoring dashboard.
     */
    public function latest(int $promotorId): JsonResponse
    {
        $cacheKey = "promotor:location:{$promotorId}";
        $location = Cache::store('redis')->get($cacheKey);

        if (!$location) {
            return response()->json([
                'status' => 'offline',
                'data'   => null,
            ]);
        }

        return response()->json([
            'status' => 'online',
            'data'   => $location,
        ]);
    }

    /**
     * Get recent position history trail of a specific promotor.
     */
    public function history(int $promotorId): JsonResponse
    {
        $historyKey = "promotor:location:{$promotorId}:history";
        $history = Cache::store('redis')->get($historyKey, []);

        return response()->json([
            'data'  => $history,
            'count' => count($history),
        ]);
    }
}
