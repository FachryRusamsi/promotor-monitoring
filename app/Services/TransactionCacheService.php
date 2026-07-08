<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TransactionCacheService
{
    public function buildDailyReport(Collection $transactions): array
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        return $transactions
            ->groupBy('user_id')
            ->map(function ($userTransactions) {
                $user = $userTransactions->first()?->user;

                if (! $user) {
                    return null;
                }

                return [
                    'promotor_id' => $user->id,
                    'promotor_name' => $user->name,
                    'total_edukasi' => $userTransactions->sum('jml_edukasi'),
                    'total_sp' => $userTransactions->sum('jml_sp'),
                    'total_pulsa' => $userTransactions->sum('jml_pulsa'),
                    'total_aktivasi_gemini' => $userTransactions->sum('jml_aktivasi_gemini'),
                    'transactions' => $userTransactions->values()->toArray(),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
