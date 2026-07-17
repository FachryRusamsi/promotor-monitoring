<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Exports\AchievementReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $regionId = $request->query('region_id');
        $areaId = $request->query('area_id');
        $date = $request->query('date', today()->toDateString());

        $transactions = Transaction::with([
                'user.attendances' => function($q) use ($date) {
                    $q->whereDate('work_date', $date);
                },
                'details',
            ])
            ->whereDate('transaction_date', $date)
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
                    'total_rebuy' => $userTransactions->sum('jml_rebuy'),
                    'total_aktivasi_gemini' => $userTransactions->sum('jml_aktivasi_gemini'),
                    'msisdn_list' => $details->values()->toArray(),
                ];

            })
            ->filter()
            ->values()
            ->toArray();

        return Inertia::render('Admin/Report', [
            'reports' => $reportData,
            'date' => $date,
            'regions' => Region::with('areas')->get(),
            'currentFilters' => [
                'region_id' => $regionId,
                'area_id' => $areaId,
            ]
        ]);
    }

    public function exportAchievement(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $regionId = $request->query('region_id');
        $areaId = $request->query('area_id');

        return Excel::download(
            new AchievementReportExport($startDate, $endDate, $regionId, $areaId), 
            'Report-Achievement-Promotor.xlsx'
        );
    }
}