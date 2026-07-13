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

            $request->merge([
                'msisdns' => $normalizedMsisdns
            ]);
        }

        $request->validate([
            'jml_edukasi' => 'required|integer|min:0|max:1000',
            'jml_sp' => 'required|integer|min:0|max:1000',
            'jml_pulsa' => 'required|integer|min:0|max:1000',
            'jml_aktivasi_gemini' => 'required|integer|min:0|max:1000',

            'foto_edukasi' => 'nullable|array',
            'foto_edukasi.*' => 'image|mimes:jpeg,png,jpg|max:10240',
            'foto_penjualan' => 'nullable|array',
            'foto_penjualan.*' => 'image|mimes:jpeg,png,jpg|max:10240',

            'msisdns' => 'nullable|array',
            'msisdns.*.number' => [
                'required',
                'regex:/^08[0-9]{8,11}$/'
            ],
            'msisdns.*.type' => 'required|in:starter_pack,reload,gemini_activation',
            'msisdns.*.notes' => 'nullable|string',
        ]);

        // Fraud Validation: Ensure the number of MSISDNs matches the claimed aggregate numbers
        $msisdnCollection = collect($normalizedMsisdns);
        $countSp = $msisdnCollection->where('type', 'starter_pack')->count();
        $countPulsa = $msisdnCollection->where('type', 'reload')->count();
        $countGemini = $msisdnCollection->where('type', 'gemini_activation')->count();

        $fraudErrors = [];
        if ($countSp != $request->jml_sp) {
            $fraudErrors[] = "SP (diinput: $countSp nomor, total klaim: {$request->jml_sp})";
        }
        if ($countPulsa != $request->jml_pulsa) {
            $fraudErrors[] = "Pulsa (diinput: $countPulsa nomor, total klaim: {$request->jml_pulsa})";
        }
        if ($countGemini != $request->jml_aktivasi_gemini) {
            $fraudErrors[] = "Gemini (diinput: $countGemini nomor, total klaim: {$request->jml_aktivasi_gemini})";
        }

        if (!empty($fraudErrors)) {
            return back()->withErrors([
                'msisdns' => 'Peringatan Fraud: Rincian nomor tidak cocok dengan angka penjualan! Detail: ' . implode(' | ', $fraudErrors)
            ])->withInput();
        }

        $user = $request->user();

        $pathEdukasi = [];
        if ($request->hasFile('foto_edukasi')) {
            foreach ($request->file('foto_edukasi') as $file) {
                $pathEdukasi[] = $file->store('transactions/edukasi', 'public');
            }
        }

        $pathPenjualan = [];
        if ($request->hasFile('foto_penjualan')) {
            foreach ($request->file('foto_penjualan') as $file) {
                $pathPenjualan[] = $file->store('transactions/penjualan', 'public');
            }
        }

        $attendance = $user->attendances()
            ->whereDate('work_date', now()->toDateString())
            ->latest()
            ->first();

        // Temporary fallback
        $outletId = $attendance ? $attendance->outlet_id : 1;

        $transaction = DB::transaction(function () use (
            $request,
            $user,
            $outletId,
            $attendance,
            $pathEdukasi,
            $pathPenjualan,
            $normalizedMsisdns
        ) {

            $trx = Transaction::create([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'attendance_id' => $attendance?->id,
                'transaction_date' => now()->toDateString(),

                'jml_edukasi' => $request->jml_edukasi,
                'jml_sp' => $request->jml_sp,
                'jml_pulsa' => $request->jml_pulsa,
                'jml_aktivasi_gemini' => $request->jml_aktivasi_gemini,

                'foto_edukasi' => $pathEdukasi,
                'foto_penjualan' => $pathPenjualan,

                'validation_status' => 'pending',
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

        event(new TransactionSubmitted($transaction));

        ProcessTransactionPhotos::dispatch($transaction->id);

        return redirect()
            ->route('promotor.dashboard')
            ->with('success', 'Transaksi berhasil dilaporkan.');
    }
}