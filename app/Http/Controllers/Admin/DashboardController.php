<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $promotors = User::whereHas('role', fn ($q) => $q->where('name', 'promotor'))
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
        ]);
    }
}
