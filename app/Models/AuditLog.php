<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'administrator_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }
}
