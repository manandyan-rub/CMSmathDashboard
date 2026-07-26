<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportRequest extends Model
{
    protected $fillable = [
        'requested_by',
        'resource',
        'format',
        'filters',
        'status',
        'file_path',
        'error',
        'completed_at',
    ];

    protected function casts(): array
    {
        return ['filters' => 'array', 'completed_at' => 'datetime'];
    }
}
