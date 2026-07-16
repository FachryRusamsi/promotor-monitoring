<?php

namespace App\Http\Controllers\Promotor;

use App\Events\TransactionSubmitted;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransactionPhotos;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EdukasiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Promotor/EdukasiForm');
    }

    public function store(Request $request)
    {
        $request->validate([
            'jml_edukasi' => 'required|integer|min:1',
            'foto_edukasi' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'required|string|max:255',
        ], [
            'jml_edukasi.required' => 'Jumlah edukasi wajib diisi.',
            'jml_edukasi.min' => 'Jumlah edukasi minimal 1.',
            'foto_edukasi.required' => 'Foto bukti edukasi wajib diunggah.',
            'location_name.required' => 'Keterangan lokasi wajib diisi.',
        ]);

        $user = $request->user();

        $pathEdukasi = [];
        if ($request->hasFile('foto_edukasi')) {
            $pathEdukasi[] = $request->file('foto_edukasi')->store('transactions/edukasi', 'public');
        }

        $attendance = $user->attendances()
            ->whereDate('work_date', now()->toDateString())
            ->where('status', 'working')
            ->latest()
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Anda harus Check-In terlebih dahulu dan belum Check-Out untuk melaporkan edukasi.');
        }

        $outletId = $attendance->outlet_id;

        $transaction = DB::transaction(function () use (
            $request,
            $user,
            $outletId,
            $attendance,
            $pathEdukasi
        ) {

            $trx = Transaction::create([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'attendance_id' => $attendance?->id,
                'transaction_date' => now()->toDateString(),

                'jml_edukasi' => $request->input('jml_edukasi'),
                'jml_sp' => 0,
                'jml_pulsa' => 0,
                'jml_aktivasi_gemini' => 0,

                'foto_edukasi' => empty($pathEdukasi) ? null : collect($pathEdukasi)->toJson(),
                'foto_penjualan' => null,
                
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'location_name' => $request->input('location_name'),

                'validation_status' => 'pending',
            ]);

            // No transaction details needed for edukasi (no MSISDN)

            return $trx;
        });

        // Trigger same event if necessary or a new one, but KPI queries look at Transactions directly
        event(new TransactionSubmitted($transaction));

        ProcessTransactionPhotos::dispatch($transaction->id);

        return redirect()
            ->route('promotor.dashboard')
            ->with('success', 'Laporan edukasi berhasil dikirim.');
    }
}
