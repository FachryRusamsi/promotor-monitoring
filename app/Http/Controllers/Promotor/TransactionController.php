<?php

namespace App\Http\Controllers\Promotor;

use App\Events\TransactionSubmitted;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransactionPhotos;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Promotor/TransactionForm');
    }

    public function store(Request $request)
    {
        // 1. Normalisasi MSISDN SEBELUM validasi
        $normalizedMsisdns = [];
        if ($request->has('msisdns') && is_array($request->input('msisdns'))) {
            foreach ($request->input('msisdns') as $item) {
                if (isset($item['number'])) {
                    $num = preg_replace('/\D/', '', $item['number']);
                    if (str_starts_with($num, '62')) {
                        $num = '0' . substr($num, 2);
                    } elseif (str_starts_with($num, '8')) {
                        $num = '0' . $num;
                    } elseif (!str_starts_with($num, '0')) {
                        $num = '0' . $num;
                    }
                    $item['number'] = $num;
                }
                $normalizedMsisdns[] = $item;
            }
            // Replace request data with normalized data for validation
            $request->merge(['msisdns' => $normalizedMsisdns]);
        }

        $request->validate([
            'jml_edukasi' => 'required|integer|min:0',
            'jml_sp' => 'required|integer|min:0',
            'jml_pulsa' => 'required|integer|min:0',
            'jml_aktivasi_gemini' => 'required|integer|min:0',
            'foto_edukasi' => 'nullable|file|image|max:10240', // max 10MB
            'foto_penjualan' => 'nullable|file|image|max:10240',
            'msisdns' => 'nullable|array',
            'msisdns.*.number' => 'required|string|min:9|max:13',
            'msisdns.*.type' => 'required|in:starter_pack,reload,gemini_activation',
            'msisdns.*.notes' => 'nullable|string',
        ]);

        $user = $request->user();

        // 2. Simpan foto ke temporary / final path dengan Storage
        $pathEdukasi = $request->hasFile('foto_edukasi') 
            ? $request->file('foto_edukasi')->store('transactions/edukasi', 'public') 
            : null;
            
        $pathPenjualan = $request->hasFile('foto_penjualan') 
            ? $request->file('foto_penjualan')->store('transactions/penjualan', 'public') 
            : null;

        // Ambil absensi terbaru hari ini (fallback default jika belum absensi untuk keperluan testing)
        $attendance = $user->attendances()->whereDate('work_date', now()->toDateString())->latest()->first();
        $outletId = $attendance ? $attendance->outlet_id : 1; 

        // 3. Simpan transaksi dan detail dalam DB Transaction (Atomic)
        $transaction = DB::transaction(function () use ($request, $user, $outletId, $attendance, $pathEdukasi, $pathPenjualan, $normalizedMsisdns) {
            $trx = Transaction::create([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'attendance_id' => $attendance ? $attendance->id : null,
                'transaction_date' => now()->toDateString(),
                'jml_edukasi' => $request->input('jml_edukasi'),
                'jml_sp' => $request->input('jml_sp'),
                'jml_pulsa' => $request->input('jml_pulsa'),
                'jml_aktivasi_gemini' => $request->input('jml_aktivasi_gemini'),
                'foto_edukasi' => $pathEdukasi,
                'foto_penjualan' => $pathPenjualan,
                'validation_status' => 'pending',
                // Assuming we also get latitude/longitude from request in real scenario, omitting for now or can add if needed
            ]);

            foreach ($normalizedMsisdns as $msisdn) {
                $trx->details()->create([
                    'msisdn' => $msisdn['number'],
                    'type' => $msisdn['type'],
                    'notes' => $msisdn['notes'] ?? null,
                ]);
            }

            return $trx;
        });

        // 4. Dispatch Event (untuk live update dashboard Admin)
        event(new TransactionSubmitted($transaction));

        // 5. Dispatch Queue Job untuk memproses foto (resize/compress) agar respons cepat
        ProcessTransactionPhotos::dispatch($transaction->id);

        return redirect()->route('promotor.dashboard')->with('success', 'Transaksi berhasil dilaporkan.');
    }
}
