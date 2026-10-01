<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $guarded = [];

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    // Format: TRX-20260930-001 (urut per hari)
    public static function nextNumber(): string
    {
        $n = static::whereDate('created_at', today())->count() + 1;
        return 'TRX-' . now()->format('Ymd') . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
    }
}
