<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        // Cache key based on today's date to group daily aggregations.
        // Cache duration is 5 minutes (300 seconds) to prevent database overload on frequent refreshes.
        $cacheKey = 'admin.reports.daily_aggregation_' . now()->toDateString();
        
        $reportData = Cache::remember($cacheKey, 300, function () {
            // Fetch today's transactions with related user and details
            $transactions = Transaction::with(['user', 'details'])
                ->whereDate('transaction_date', now()->toDateString())
                ->orderBy('created_at', 'desc')
                ->get();

            // Aggregate data per promotor
            return $transactions->groupBy('user_id')->map(function ($userTransactions) {
                $user = $userTransactions->first()->user;
                
                // Extract and format all MSISDN details for this promotor
                $allDetails = $userTransactions->flatMap(function ($trx) {
                    return $trx->details->map(function ($detail) use ($trx) {
                        return [
                            'msisdn' => $detail->msisdn,
                            'type' => $detail->type,
                            'notes' => $detail->notes,
                            'validation_status' => $trx->validation_status,
                            'transaction_date' => $trx->created_at->toDateTimeString(),
                        ];
                    });
                });
                
                return [
                    'promotor_id' => $user->id,
                    'promotor_name' => $user->name,
                    'total_edukasi' => $userTransactions->sum('jml_edukasi'),
                    'total_sp' => $userTransactions->sum('jml_sp'),
                    'total_pulsa' => $userTransactions->sum('jml_pulsa'),
                    'total_aktivasi_gemini' => $userTransactions->sum('jml_aktivasi_gemini'),
                    'msisdn_list' => $allDetails->values()->toArray(),
                ];
            })->values()->toArray();
        });

        return Inertia::render('Admin/Report', [
            'reports' => $reportData,
            'date' => now()->toDateString(),
        ]);
    }
}
