<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = ['key', 'name', 'driver', 'base_url', 'secret_env_key', 'active', 'metadata'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'metadata' => 'array'];
    }

    public function models()
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }
}
