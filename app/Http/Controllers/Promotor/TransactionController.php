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

class TransactionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Promotor/TransactionForm');
    }

    public function store(Request $request)
    {
        // Normalisasi MSISDN
        $msisdn = $request->input('msisdn');
        if ($msisdn) {
            $num = preg_replace('/\D/', '', $msisdn);
            if (str_starts_with($num, '62')) {
                $num = '0' . substr($num, 2);
            } elseif (str_starts_with($num, '8')) {
                $num = '0' . $num;
            } elseif (!str_starts_with($num, '0')) {
                $num = '0' . $num;
            }
            $request->merge(['msisdn' => $num]);
        }

        $request->validate([
            'msisdn' => [
                'required',
                'regex:/^(0814|0815|0816|0855|0856|0857|0858|0895|0896|0897|0898|0899)[0-9]{4,11}$/'
            ],
            'type' => 'required|in:starter_pack,reload,gemini_activation',
            'notes' => 'nullable|string',
            'foto_penjualan' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'required|string|max:255',
        ], [
            'msisdn.regex' => 'Nomor HP harus diawali dengan prefix Indosat/Tri yang valid.',
            'location_name.required' => 'Keterangan lokasi wajib diisi.'
        ]);

        $user = $request->user();

        $type = $request->input('type');
        $jml_sp = $type === 'starter_pack' ? 1 : 0;
        $jml_pulsa = $type === 'reload' ? 1 : 0;
        $jml_aktivasi_gemini = $type === 'gemini_activation' ? 1 : 0;
        $jml_edukasi = 0; // Transaksi penjualan tidak mencampur data edukasi lagi

        $pathPenjualan = [];
        if ($request->hasFile('foto_penjualan')) {
            $pathPenjualan[] = $request->file('foto_penjualan')->store('transactions/penjualan', 'public');
        }

        $attendance = $user->attendances()
            ->whereDate('work_date', now()->toDateString())
            ->where('status', 'working')
            ->latest()
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Anda harus Check-In terlebih dahulu dan belum Check-Out untuk melaporkan transaksi.');
        }

        $outletId = $attendance->outlet_id;

        $transaction = DB::transaction(function () use (
            $request,
            $user,
            $outletId,
            $attendance,
            $pathPenjualan,
            $jml_edukasi,
            $jml_sp,
            $jml_pulsa,
            $jml_aktivasi_gemini
        ) {

            $trx = Transaction::create([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'attendance_id' => $attendance?->id,
                'transaction_date' => now()->toDateString(),

                'jml_edukasi' => $jml_edukasi,
                'jml_sp' => $jml_sp,
                'jml_pulsa' => $jml_pulsa,
                'jml_aktivasi_gemini' => $jml_aktivasi_gemini,

                'foto_edukasi' => null,
                'foto_penjualan' => empty($pathPenjualan) ? null : collect($pathPenjualan)->toJson(),
                
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'location_name' => $request->input('location_name'),

                'validation_status' => 'pending',
            ]);

            $trx->details()->create([
                'msisdn' => $request->input('msisdn'),
                'type' => $request->input('type'),
                'notes' => $request->input('notes'),
            ]);

            return $trx;
        });

        event(new TransactionSubmitted($transaction));

        ProcessTransactionPhotos::dispatch($transaction->id);

        return redirect()
            ->route('promotor.dashboard')
            ->with('success', 'Transaksi berhasil dilaporkan.');
    }
}