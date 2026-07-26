<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    use HasCmsGovernance;

    protected $fillable = [
        'key',
        'category',
        'feature',
        'model_id',
        'fallback_model_id',
        'monthly_limit',
        'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function model()
    {
        return $this->belongsTo(AiModel::class);
    }

    public function fallbackModel()
    {
        return $this->belongsTo(AiModel::class, 'fallback_model_id');
    }

    public function versions()
    {
        return $this->hasMany(AiPromptVersion::class, 'prompt_id')->latest('version');
    }

    public function activeVersion()
    {
        return $this->hasOne(AiPromptVersion::class, 'prompt_id')->where('active', true);
    }
}
