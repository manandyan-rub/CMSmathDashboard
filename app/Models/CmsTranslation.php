<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsTranslation extends Model
{
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'values',
        'reviewed',
        'machine_draft',
        'source_updated_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'reviewed' => 'boolean',
            'machine_draft' => 'boolean',
            'source_updated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function translatable()
    {
        return $this->morphTo();
    }
}
