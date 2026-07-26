<?php

namespace App\Services\Cms;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditService
{
    public function record(
        string $action,
        Model|string|null $entity = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $reason = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $entityType = $entity instanceof Model ? $entity->getMorphClass() : $entity;
        $entityId = $entity instanceof Model ? $entity->getKey() : null;

        return AuditLog::create([
            'administrator_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'request_id' => $request->attributes->get('request_id', (string) Str::uuid()),
        ]);
    }

    public function sanitize(array $values): array
    {
        $secretFields = collect(config('cms.audit_secret_fields', []))
            ->map(fn (string $key) => Str::lower($key));

        return collect($values)->mapWithKeys(function ($value, $key) use ($secretFields) {
            $normalized = Str::of((string) $key)->lower()->replace(['-', ' '], '_')->toString();
            $secret = $secretFields->contains(
                fn (string $candidate) => $normalized === $candidate || Str::contains($normalized, $candidate)
            );

            if ($secret) {
                return [$key => '[REDACTED]'];
            }

            if (is_array($value)) {
                return [$key => $this->sanitize($value)];
            }

            return [$key => $value];
        })->all();
    }

    public function modelSnapshot(Model $model): array
    {
        return $this->sanitize(Arr::except($model->attributesToArray(), [
            'content_html',
            'content_json',
            'request_json',
            'response_raw',
        ]));
    }
}
