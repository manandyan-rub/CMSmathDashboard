<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MathPath extends Model
{
    use HasCmsGovernance, HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'icon',
        'cover_image',
        'difficulty_min',
        'difficulty_max',
        'minimum_required_level',
        'order',
        'active',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'minimum_required_level' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MathPathTranslation::class, 'math_path_id');
    }

    public function levels()
    {
        return $this->hasMany(MathLevel::class, 'path_id')->orderBy('order');
    }

    public function prerequisites()
    {
        return $this->belongsToMany(
            self::class,
            'math_path_prerequisites',
            'path_id',
            'prerequisite_path_id'
        );
    }

    public function resolveTranslation(array $locales): ?MathPathTranslation
    {
        foreach ($locales as $locale) {
            $translation = $this->translations->firstWhere('locale', $locale);
            if ($translation) {
                return $translation;
            }
        }

        return $this->translations->first();
    }
}
