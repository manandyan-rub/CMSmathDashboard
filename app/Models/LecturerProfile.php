<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'approval_status',
        'monthly_ai_limit',
        'storage_limit_bytes',
        'approved_by',
        'approved_at',
        'suspended_at',
        'moderation_note',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'suspended_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
