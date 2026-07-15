<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Region;
use App\Models\Area;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $regionId = $request->query('region_id');
        $areaId = $request->query('area_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Query KPI Nasional atau per Region/Area
        $kpiQuery = Transaction::query();
        
        if ($regionId) {
            $kpiQuery->whereHas('user', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        if ($areaId) {
            $kpiQuery->whereHas('user', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }
        if ($startDate && $endDate) {
            $kpiQuery->whereBetween('transaction_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $kpiQuery->whereDate('transaction_date', '>=', $startDate);
        } elseif ($endDate) {
            $kpiQuery->whereDate('transaction_date', '<=', $endDate);
        }

        $kpi = [
            'total_edukasi' => (int) $kpiQuery->sum('jml_edukasi'),
            'total_sp' => (int) $kpiQuery->sum('jml_sp'),
            'total_pulsa' => (int) $kpiQuery->sum('jml_pulsa'),
            'total_gemini' => (int) $kpiQuery->sum('jml_aktivasi_gemini'),
        ];

        // Relation filter for withSum
        $transactionFilter = function($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $q->whereDate('transaction_date', '>=', $startDate);
            } elseif ($endDate) {
                $q->whereDate('transaction_date', '<=', $endDate);
            }
        };

        // Ranking Region (Semua region)
        $regionRankings = Region::withSum(['transactions as total_sales' => $transactionFilter], DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'))
            ->withSum(['transactions as total_edukasi' => $transactionFilter], 'jml_edukasi')
            ->withSum(['transactions as total_sp' => $transactionFilter], 'jml_sp')
            ->withSum(['transactions as total_pulsa' => $transactionFilter], 'jml_pulsa')
            ->withSum(['transactions as total_gemini' => $transactionFilter], 'jml_aktivasi_gemini')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'total_sales' => (int) ($region->total_sales ?? 0),
                    'total_edukasi' => (int) ($region->total_edukasi ?? 0),
                    'total_sp' => (int) ($region->total_sp ?? 0),
                    'total_pulsa' => (int) ($region->total_pulsa ?? 0),
                    'total_gemini' => (int) ($region->total_gemini ?? 0),
                ];
            });

        // Ranking Top 5 Branch
        $branchQuery = Area::withSum(['transactions as total_sales' => $transactionFilter], DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'));
        
        if ($regionId) {
            $branchQuery->where('region_id', $regionId);
        }

        $topBranches = $branchQuery->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'region_name' => $branch->region->name ?? '',
                    'total_sales' => $branch->total_sales ?? 0
                ];
            });

        // Top Rank Promotors
        $promotorQuery = User::whereHas('role', fn($q) => $q->where('name', 'promotor'))
            ->withSum(['transactions as total_sales' => $transactionFilter], DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'));
            
        if ($regionId) {
            $promotorQuery->where('region_id', $regionId);
        }
        if ($areaId) {
            $promotorQuery->where('area_id', $areaId);
        }

        $topPromotors = $promotorQuery->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'region_name' => $user->region->name ?? '',
                    'area_name' => $user->area->name ?? '',
                    'total_sales' => $user->total_sales ?? 0
                ];
            });

        // All Promotors KPI breakdown (for modal/switch view)
        $allPromotorsQuery = User::whereHas('role', fn($q) => $q->where('name', 'promotor'))
            ->withSum(['transactions as total_edukasi' => $transactionFilter], 'jml_edukasi')
            ->withSum(['transactions as total_sp' => $transactionFilter], 'jml_sp')
            ->withSum(['transactions as total_pulsa' => $transactionFilter], 'jml_pulsa')
            ->withSum(['transactions as total_gemini' => $transactionFilter], 'jml_aktivasi_gemini');

        if ($regionId) {
            $allPromotorsQuery->where('region_id', $regionId);
        }
        if ($areaId) {
            $allPromotorsQuery->where('area_id', $areaId);
        }

        $allPromotors = $allPromotorsQuery->with(['region', 'area'])->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'region_name' => $user->region->name ?? '-',
                'area_name' => $user->area->name ?? '-',
                'total_sales' => (int) (($user->total_edukasi ?? 0) + ($user->total_sp ?? 0) + ($user->total_pulsa ?? 0) + ($user->total_gemini ?? 0)),
                'total_edukasi' => (int) ($user->total_edukasi ?? 0),
                'total_sp' => (int) ($user->total_sp ?? 0),
                'total_pulsa' => (int) ($user->total_pulsa ?? 0),
                'total_gemini' => (int) ($user->total_gemini ?? 0),
            ];
        });

        // All Branches KPI breakdown (for dynamic chart)
        $allBranchesQuery = Area::withSum(['transactions as total_sales' => $transactionFilter], DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'))
            ->withSum(['transactions as total_edukasi' => $transactionFilter], 'jml_edukasi')
            ->withSum(['transactions as total_sp' => $transactionFilter], 'jml_sp')
            ->withSum(['transactions as total_pulsa' => $transactionFilter], 'jml_pulsa')
            ->withSum(['transactions as total_gemini' => $transactionFilter], 'jml_aktivasi_gemini');

        if ($regionId) {
            $allBranchesQuery->where('region_id', $regionId);
        }

        $allBranches = $allBranchesQuery->get()->map(function($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'total_sales' => (int) ($branch->total_sales ?? 0),
                'total_edukasi' => (int) ($branch->total_edukasi ?? 0),
                'total_sp' => (int) ($branch->total_sp ?? 0),
                'total_pulsa' => (int) ($branch->total_pulsa ?? 0),
                'total_gemini' => (int) ($branch->total_gemini ?? 0),
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'regions' => Region::with('areas')->get(),
            'currentFilters' => [
                'region_id' => $regionId,
                'area_id' => $areaId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'kpi' => $kpi,
            'regionRankings' => $regionRankings,
            'topBranches' => $topBranches,
            'allBranches' => $allBranches,
            'topPromotors' => $topPromotors,
            'allPromotors' => $allPromotors,
        ]);
    }

    public function monitoring(Request $request): Response
    {
        $regionId = $request->query('region_id');
        $areaId = $request->query('area_id');

        $promotors = User::whereHas('role', fn ($q) => $q->where('name', 'promotor'))
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId))
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->with([
                'attendances' => fn ($q) => $q->whereDate('work_date', today()),
                'transactions' => fn ($q) => $q->whereDate('transaction_date', today())
            ])
            ->get()
            ->map(function ($promotor) {
                $attendance = $promotor->attendances->first();
                $hasReported = $promotor->transactions->isNotEmpty();

                return [
                    'id' => $promotor->id,
                    'name' => $promotor->name,
                    'check_in_time' => $attendance?->check_in_at?->format('H:i'),
                    'check_in_lat' => $attendance?->check_in_lat,
                    'check_in_lng' => $attendance?->check_in_lng,
                    'check_out_time' => $attendance?->check_out_at?->format('H:i'),
                    'check_out_lat' => $attendance?->check_out_lat,
                    'check_out_lng' => $attendance?->check_out_lng,
                    'status' => $attendance?->status,
                    'has_reported' => $hasReported,
                ];
            });

        return Inertia::render('Admin/Monitoring', [
            'promotors' => $promotors,
            'regions' => Region::with('areas')->get(),
            'currentFilters' => [
                'region_id' => $regionId,
                'area_id' => $areaId,
            ]
        ]);
    }

    public function promotorTransactions(Request $request, User $user)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = $user->transactions()->with('details');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get()->map(function($trx) {
            return [
                'id' => $trx->id,
                'transaction_date' => $trx->transaction_date,
                'created_at' => $trx->created_at->format('Y-m-d H:i:s'),
                'jml_edukasi' => $trx->jml_edukasi,
                'jml_sp' => $trx->jml_sp,
                'jml_pulsa' => $trx->jml_pulsa,
                'jml_aktivasi_gemini' => $trx->jml_aktivasi_gemini,
                'foto_edukasi' => $trx->foto_edukasi,
                'foto_penjualan' => $trx->foto_penjualan,
                'latitude' => $trx->latitude,
                'longitude' => $trx->longitude,
                'location_name' => $trx->location_name,
                'details' => $trx->details,
            ];
        });

        return response()->json($transactions);
    }
}
