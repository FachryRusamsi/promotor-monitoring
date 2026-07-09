<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $regionId = $request->query('region_id');
        $areaId = $request->query('area_id');

        $transactions = Transaction::with([
                'user.attendances' => function($q) {
                    $q->whereDate('work_date', today());
                },
                'details',
            ])
            ->whereDate('transaction_date', today())
            ->when($regionId, function($q) use ($regionId) {
                $q->whereHas('user', fn($u) => $u->where('region_id', $regionId));
            })
            ->when($areaId, function($q) use ($areaId) {
                $q->whereHas('user', fn($u) => $u->where('area_id', $areaId));
            })
            ->latest()
            ->get();

        $reportData = $transactions
            ->groupBy('user_id')
            ->map(function ($userTransactions) {

                $user = optional($userTransactions->first())->user;

                if (!$user) {
                    return null;
                }

                $attendance = $user->attendances->first();

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
                    'check_in_at' => $attendance?->check_in_at?->format('Y-m-d H:i:s'),
                    'check_out_at' => $attendance?->check_out_at?->format('Y-m-d H:i:s'),
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

        return Inertia::render('Admin/Report', [
            'reports' => $reportData,
            'date' => today()->toDateString(),
            'regions' => Region::with('areas')->get(),
            'currentFilters' => [
                'region_id' => $regionId,
                'area_id' => $areaId,
            ]
        ]);
    }
}