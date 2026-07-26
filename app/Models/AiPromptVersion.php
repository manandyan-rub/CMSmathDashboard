<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptVersion extends Model
{
    protected $fillable = [
        'prompt_id',
        'version',
        'system_prompt',
        'user_prompt_template',
        'input_schema',
        'output_schema',
        'temperature',
        'max_output_tokens',
        'active',
        'author_id',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'input_schema' => 'array',
            'output_schema' => 'array',
            'active' => 'boolean',
            'temperature' => 'decimal:2',
        ];
    }

    public function prompt()
    {
        return $this->belongsTo(AiPrompt::class);
    }
}
