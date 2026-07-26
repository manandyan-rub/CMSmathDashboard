<?php

namespace App\Services\Cms;

use App\Models\ContentVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContentVersionService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function capture(
        Model $model,
        array $before,
        array $after,
        ?string $reason = null,
    ): ContentVersion {
        $before = $this->audit->sanitize($before);
        $after = $this->audit->sanitize($after);
        $changed = collect(array_unique(array_merge(array_keys($before), array_keys($after))))
            ->filter(fn (string $key) => Arr::get($before, $key) !== Arr::get($after, $key))
            ->values()
            ->all();

        $nextVersion = ContentVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->max('version_number') + 1;

        return ContentVersion::create([
            'versionable_type' => $model->getMorphClass(),
            'versionable_id' => $model->getKey(),
            'version_number' => $nextVersion,
            'changed_fields' => $changed,
            'previous_data' => $before,
            'new_data' => $after,
            'author_id' => request()->user()?->id,
            'reason' => $reason,
        ]);
    }

    public function restore(ContentVersion $version, ?string $reason = null): Model
    {
        return DB::transaction(function () use ($version, $reason) {
            $model = $version->versionable()->firstOrFail();
            $before = $this->audit->modelSnapshot($model);
            $restorable = Arr::only($version->new_data ?? [], $model->getFillable());
            $model->fill($restorable)->save();
            $after = $this->audit->modelSnapshot($model->fresh());

            $this->capture($model, $before, $after, $reason ?: "Restored version {$version->version_number}");
            $this->audit->record('content.version_restored', $model, $before, $after, $reason);

            return $model->fresh();
        });
    }
}
