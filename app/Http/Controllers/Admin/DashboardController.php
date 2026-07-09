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

        $kpi = [
            'total_edukasi' => (int) $kpiQuery->sum('jml_edukasi'),
            'total_sp' => (int) $kpiQuery->sum('jml_sp'),
            'total_pulsa' => (int) $kpiQuery->sum('jml_pulsa'),
            'total_gemini' => (int) $kpiQuery->sum('jml_aktivasi_gemini'),
        ];

        // Ranking Region (Semua region)
        $regionRankings = Region::withSum('transactions as total_sales', DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'))
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'total_sales' => $region->total_sales ?? 0
                ];
            });

        // Ranking Top 5 Branch
        $branchQuery = Area::withSum('transactions as total_sales', DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'));
        
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
            ->withSum('transactions as total_sales', DB::raw('jml_edukasi + jml_sp + jml_pulsa + jml_aktivasi_gemini'));
            
        if ($regionId) {
            $promotorQuery->where('region_id', $regionId);
        }
        if ($areaId) {
            $promotorQuery->where('area_id', $areaId);
        }

        $topPromotors = $promotorQuery->orderByDesc('total_sales')
            ->limit(10)
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
            ->withSum('transactions as total_edukasi', 'jml_edukasi')
            ->withSum('transactions as total_sp', 'jml_sp')
            ->withSum('transactions as total_pulsa', 'jml_pulsa')
            ->withSum('transactions as total_gemini', 'jml_aktivasi_gemini');

        if ($regionId) {
            $allPromotorsQuery->where('region_id', $regionId);
        }
        if ($areaId) {
            $allPromotorsQuery->where('area_id', $areaId);
        }

        $allPromotors = $allPromotorsQuery->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'total_edukasi' => (int) ($user->total_edukasi ?? 0),
                'total_sp' => (int) ($user->total_sp ?? 0),
                'total_pulsa' => (int) ($user->total_pulsa ?? 0),
                'total_gemini' => (int) ($user->total_gemini ?? 0),
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'regions' => Region::with('areas')->get(),
            'currentFilters' => [
                'region_id' => $regionId,
                'area_id' => $areaId,
            ],
            'kpi' => $kpi,
            'regionRankings' => $regionRankings,
            'topBranches' => $topBranches,
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
}
