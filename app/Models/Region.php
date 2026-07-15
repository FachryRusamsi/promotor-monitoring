<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, User::class);
    }
}