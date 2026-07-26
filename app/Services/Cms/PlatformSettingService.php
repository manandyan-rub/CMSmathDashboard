<?php

namespace App\Services\Cms;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlatformSettingService
{
    private const CACHE_KEY = 'cms.platform-settings';

    public function all(bool $publicOnly = false): array
    {
        $settings = Cache::remember(self::CACHE_KEY, 600, fn () => PlatformSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get());

        return $settings
            ->when($publicOnly, fn ($items) => $items->where('public', true))
            ->mapWithKeys(fn (PlatformSetting $setting) => [
                $setting->key => $this->typedValue($setting),
            ])
            ->all();
    }

    public function update(PlatformSetting $setting, mixed $value): PlatformSetting
    {
        if (!$setting->editable) {
            throw ValidationException::withMessages(['value' => 'This setting is read-only.']);
        }

        $rules = $setting->validation_rules ?: ['nullable'];
        Validator::make(['value' => $value], ['value' => $rules])->validate();

        $setting->update(['value' => $value]);
        Cache::forget(self::CACHE_KEY);

        return $setting->fresh();
    }

    private function typedValue(PlatformSetting $setting): mixed
    {
        $value = $setting->value ?? $setting->default_value;

        return match ($setting->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'array' => is_array($value) ? $value : [],
            default => $value,
        };
    }
}
