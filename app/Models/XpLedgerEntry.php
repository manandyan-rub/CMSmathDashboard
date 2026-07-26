<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XpLedgerEntry extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'balance_after',
        'source',
        'reason',
        'administrator_id',
        'reference_type',
        'reference_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}
