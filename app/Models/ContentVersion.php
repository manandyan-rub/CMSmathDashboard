<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentVersion extends Model
{
    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'changed_fields',
        'previous_data',
        'new_data',
        'author_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'previous_data' => 'array',
            'new_data' => 'array',
        ];
    }

    public function versionable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
