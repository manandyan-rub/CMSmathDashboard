<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'group',
        'type',
        'value',
        'default_value',
        'validation_rules',
        'label',
        'description',
        'public',
        'editable',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'default_value' => 'json',
            'validation_rules' => 'array',
            'public' => 'boolean',
            'editable' => 'boolean',
        ];
    }
}
