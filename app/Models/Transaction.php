<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'outlet_id',
        'attendance_id',
        'transaction_date',
        'jml_edukasi',
        'jml_sp',
        'jml_rebuy',
        'jml_aktivasi_gemini',
        'foto_edukasi',
        'foto_penjualan',
        'latitude',
        'longitude',
        'location_name',
        'validation_status',
        'validation_notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'latitude'         => 'decimal:7',
            'longitude'        => 'decimal:7',
            'foto_edukasi'     => 'array',
            'foto_penjualan'   => 'array',
        ];
    }

    // ──────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
