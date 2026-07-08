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
        $cacheKey = 'admin.reports.daily_aggregation_' . now()->toDateString();

        $reportData = Cache::remember($cacheKey, 300, function () {

            $transactions = Transaction::with([
                    'user',
                    'details',
                ])
                ->whereDate('transaction_date', now()->toDateString())
                ->latest()
                ->get();

            return $transactions
                ->groupBy('user_id')
                ->map(function ($userTransactions) {

                    $user = optional($userTransactions->first())->user;

                    if (!$user) {
                        return null;
                    }

                    $details = $userTransactions
                        ->flatMap(function ($trx) {

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

                        'msisdn_list' => $details->values()->toArray(),

                    ];

                })
                ->filter()
                ->values()
                ->toArray();

        });

        return Inertia::render('Admin/Report', [

            'reports' => $reportData,

            'date' => now()->toDateString(),

        ]);
    }
}