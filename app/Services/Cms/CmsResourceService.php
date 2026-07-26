<?php

namespace App\Services\Cms;

use App\Models\CalculatorContent;
use App\Models\CalculatorField;
use App\Models\CmsTranslation;
use App\Models\ContentWorkflow;
use App\Models\ContentVersion;
use App\Models\Formula;
use App\Models\FormulaRelation;
use App\Models\FormulaTranslation;
use App\Models\MathLevel;
use App\Models\MathLevelQuestion;
use App\Models\MathLevelQuestionOption;
use App\Models\MathLevelQuestionOptionTranslation;
use App\Models\MathLevelQuestionTranslation;
use App\Models\MathLevelTranslation;
use App\Models\MathPath;
use App\Models\MathPathTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CmsResourceService
{
    public function __construct(
        private readonly CmsResourceRegistry $registry,
        private readonly AuditService $audit,
        private readonly ContentVersionService $versions,
        private readonly PublishingService $publishing,
    ) {
    }

    public function paginate(string $resource, array $filters): LengthAwarePaginator
    {
        $definition = $this->registry->definition($resource);
        /** @var Builder $query */
        $query = $definition['model']::query();
        $query->with($definition['with'] ?? []);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($definition, $search) {
                foreach ($definition['search'] as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $inner->{$method}($column, 'like', "%{$search}%");
                }
                if (
                    !empty($definition['translation_fields'])
                    && method_exists($inner->getModel(), 'cmsTranslations')
                ) {
                    $inner->orWhereHas('cmsTranslations', fn (Builder $translations) => $translations
                        ->where('values', 'like', "%{$search}%"));
                }
            });
        }

        foreach ((array) ($filters['filter'] ?? []) as $field => $value) {
            if (!array_key_exists($field, $definition['fields']) || $value === '' || $value === null) {
                continue;
            }
            $query->where($field, $value);
        }

        if (($filters['workflow_state'] ?? null) && method_exists($query->getModel(), 'workflow')) {
            $query->whereHas('workflow', fn (Builder $workflow) => $workflow
                ->where('state', $filters['workflow_state']));
        }

        $sort = in_array($filters['sort'] ?? '', $definition['sorts'], true)
            ? $filters['sort']
            : $definition['default_sort'];
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = max(5, min(
            (int) config('cms.pagination.maximum', 100),
            (int) ($filters['per_page'] ?? config('cms.pagination.default', 20))
        ));

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function find(string $resource, int $id): Model
    {
        $definition = $this->registry->definition($resource);

        return $definition['model']::query()
            ->with($definition['with'] ?? [])
            ->findOrFail($id);
    }

    public function create(string $resource, array $data, ?string $reason = null): Model
    {
        return DB::transaction(function () use ($resource, $data, $reason) {
            $definition = $this->registry->definition($resource);
            $class = $definition['model'];
            $model = new $class();
            $relations = $this->extractRelations($data);
            $model->fill(Arr::only($data, $model->getFillable()))->save();
            $this->syncRelations($model, $relations);
            $this->ensureWorkflow($model);

            $snapshot = $this->audit->modelSnapshot($model->fresh());
            $this->versions->capture($model, [], $snapshot, $reason ?: 'Created');
            $this->audit->record("{$resource}.created", $model, [], $snapshot, $reason);

            return $this->find($resource, $model->getKey());
        });
    }

    public function update(string $resource, Model $model, array $data, ?string $reason = null): Model
    {
        return DB::transaction(function () use ($resource, $model, $data, $reason) {
            $before = $this->snapshotWithRelations($model);
            $relations = $this->extractRelations($data);
            $model->fill(Arr::only($data, $model->getFillable()))->save();
            $this->syncRelations($model, $relations);
            $after = $this->snapshotWithRelations($model->fresh());

            $this->versions->capture($model, $before, $after, $reason);
            $this->audit->record("{$resource}.updated", $model, $before, $after, $reason);

            return $this->find($resource, $model->getKey());
        });
    }

    public function duplicate(string $resource, Model $model): Model
    {
        return DB::transaction(function () use ($resource, $model) {
            $copy = $model->replicate();
            foreach (['slug', 'key'] as $uniqueField) {
                if ($copy->getAttribute($uniqueField)) {
                    $copy->setAttribute(
                        $uniqueField,
                        Str::limit($copy->getAttribute($uniqueField).'-copy-'.now()->format('His'), 120, '')
                    );
                }
            }
            $copy->save();

            $translations = $this->translationPayload($model);
            $this->syncTranslations($copy, $translations);
            if ($model instanceof MathLevelQuestion) {
                $this->syncQuestionOptions($copy, $model->options->map(
                    fn ($option) => array_merge(
                        Arr::except($option->toArray(), ['id', 'question_id', 'created_at', 'updated_at']),
                        [
                            'translations' => $option->translations->mapWithKeys(
                                fn ($translation) => [$translation->locale => Arr::only(
                                    $translation->toArray(),
                                    ['label', 'explanation', 'reviewed']
                                )]
                            )->all(),
                        ]
                    )
                )->all());
            }
            if ($model instanceof MathPath) {
                $copy->prerequisites()->sync($model->prerequisites->modelKeys());
            }
            if ($model instanceof Formula) {
                $this->syncFormulaRelations($copy, $model->relations->toArray());
            }
            if ($model instanceof CalculatorContent) {
                $this->syncCalculatorFields($copy, $model->fields->map(
                    fn (CalculatorField $field) => array_merge(
                        Arr::except(
                            $field->toArray(),
                            ['id', 'calculator_content_id', 'created_at', 'updated_at']
                        ),
                        ['translations' => $this->translationPayload($field)]
                    )
                )->all());
                $this->syncCalculatorRelations($copy, $model->relations->toArray());
            }
            $this->ensureWorkflow($copy);

            $snapshot = $this->snapshotWithRelations($copy);
            $this->versions->capture($copy, [], $snapshot, 'Duplicated');
            $this->audit->record("{$resource}.duplicated", $copy, [], $snapshot, "Copied from #{$model->getKey()}");

            return $this->find($resource, $copy->getKey());
        });
    }

    public function restoreVersion(
        string $resource,
        Model $model,
        ContentVersion $version,
        string $reason,
    ): Model {
        return DB::transaction(function () use ($resource, $model, $version, $reason) {
            $before = $this->snapshotWithRelations($model);
            $snapshot = $version->new_data ?? [];
            $relations = $this->extractRelations($snapshot);
            $model->fill(Arr::only($snapshot, $model->getFillable()))->save();
            $this->deleteTranslationsMissingFromSnapshot(
                $model,
                array_keys($relations['translations'])
            );
            $this->syncRelations($model, $relations);
            $after = $this->snapshotWithRelations($model->fresh());
            $this->versions->capture(
                $model,
                $before,
                $after,
                "{$reason} (restored version {$version->version_number})"
            );
            $this->audit->record(
                "{$resource}.version_restored",
                $model,
                $before,
                $after,
                $reason
            );

            return $this->find($resource, $model->getKey());
        });
    }

    public function destroy(string $resource, Model $model, string $mode, string $reason): void
    {
        $definition = $this->registry->definition($resource);
        $inUse = collect($definition['safe_delete_relations'] ?? [])
            ->mapWithKeys(fn (string $relation) => [$relation => $model->{$relation}()->count()])
            ->filter();

        if ($mode !== 'archive' && $inUse->isNotEmpty()) {
            throw ValidationException::withMessages([
                'resource' => 'This item is in use. Archive it instead of deleting it.',
            ]);
        }

        DB::transaction(function () use ($resource, $model, $mode, $reason) {
            $before = $this->snapshotWithRelations($model);
            if ($mode === 'archive') {
                if ($model->isFillable('archived_at')) {
                    $model->update(['archived_at' => now()]);
                }
                if (method_exists($model, 'workflow')) {
                    $this->publishing->transition($model, 'archived', $reason);
                }
            } else {
                $model->delete();
            }

            $this->audit->record(
                $mode === 'archive' ? "{$resource}.archived" : "{$resource}.deleted",
                $model,
                $before,
                ['mode' => $mode],
                $reason
            );
        });
    }

    public function reorder(string $resource, array $items): void
    {
        $model = $this->registry->model($resource);
        $orderField = collect(['order', 'display_order', 'sort_order'])
            ->first(fn (string $field) => $model->isFillable($field));
        if (!$orderField) {
            throw ValidationException::withMessages(['items' => 'This resource cannot be reordered.']);
        }

        DB::transaction(function () use ($resource, $model, $items, $orderField) {
            foreach ($items as $item) {
                $model->newQuery()->whereKey($item['id'])->update([$orderField => $item['order']]);
            }
            $this->audit->record("{$resource}.reordered", $model::class, [], ['items' => $items]);
        });
    }

    public function bulk(string $resource, array $ids, string $action, string $reason): int
    {
        $model = $this->registry->model($resource);
        $query = $model->newQuery()->whereKey($ids);

        $count = DB::transaction(function () use ($query, $model, $action) {
            return match ($action) {
                'activate' => $model->isFillable('active') ? $query->update(['active' => true]) : 0,
                'deactivate' => $model->isFillable('active') ? $query->update(['active' => false]) : 0,
                'archive' => $model->isFillable('archived_at') ? $query->update(['archived_at' => now()]) : 0,
                default => throw ValidationException::withMessages(['action' => 'Unsupported bulk action.']),
            };
        });

        $this->audit->record("{$resource}.bulk_{$action}", $model::class, [], ['ids' => $ids], $reason);

        return $count;
    }

    public function serialize(Model $model, string $locale): array
    {
        $data = $model->toArray();
        if ($model instanceof MathPath) {
            $data['prerequisite_ids'] = $model->prerequisites->modelKeys();
        }
        $translations = $this->translationPayload($model);
        $data['translations'] = $translations;
        $data['translation'] = $translations[$locale]
            ?? $translations[config('cms.source_locale', 'en')]
            ?? collect($translations)->first()
            ?? [];
        $data['translation_completeness'] = $this->translationCompleteness($translations);
        $data['publishing'] = method_exists($model, 'workflow')
            ? ($model->workflow?->toArray() ?? ['state' => 'draft'])
            : null;

        return $data;
    }

    private function extractRelations(array &$data): array
    {
        $relations = [
            'translations' => $data['translations'] ?? [],
            'options' => $data['options'] ?? [],
            'fields' => $data['fields'] ?? [],
            'prerequisite_ids' => $data['prerequisite_ids'] ?? [],
            'relations' => $data['relations'] ?? [],
        ];
        unset(
            $data['translations'],
            $data['options'],
            $data['fields'],
            $data['prerequisite_ids'],
            $data['relations']
        );

        return $relations;
    }

    private function syncRelations(Model $model, array $relations): void
    {
        $this->syncTranslations($model, $relations['translations']);
        if ($model instanceof MathLevelQuestion) {
            $this->validateStructuredAnswer($model, $relations['options']);
            $this->syncQuestionOptions($model, $relations['options']);
        }
        if ($model instanceof CalculatorContent) {
            $this->syncCalculatorFields($model, $relations['fields']);
            $this->syncCalculatorRelations($model, $relations['relations']);
        }
        if ($model instanceof MathPath) {
            $ids = collect($relations['prerequisite_ids'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0 && $id !== (int) $model->getKey())
                ->unique()
                ->values();
            $model->prerequisites()->sync($ids);
        }
        if ($model instanceof Formula) {
            $this->syncFormulaRelations($model, $relations['relations']);
        }
    }

    private function syncTranslations(Model $model, array $translations): void
    {
        foreach ($translations as $locale => $values) {
            if (!in_array($locale, config('cms.locales', []), true) || !is_array($values)) {
                continue;
            }

            if ($model instanceof MathPath) {
                MathPathTranslation::updateOrCreate(
                    ['math_path_id' => $model->id, 'locale' => $locale],
                    Arr::only($values, ['title', 'description'])
                );
                continue;
            }
            if ($model instanceof Formula) {
                FormulaTranslation::updateOrCreate(
                    ['formula_id' => $model->id, 'locale' => $locale],
                    Arr::only($values, ['name', 'description'])
                );
                continue;
            }
            if ($model instanceof MathLevel) {
                MathLevelTranslation::updateOrCreate(
                    ['math_level_id' => $model->id, 'locale' => $locale],
                    Arr::only($values, ['title', 'description', 'topic'])
                );
                continue;
            }
            if ($model instanceof MathLevelQuestion) {
                MathLevelQuestionTranslation::updateOrCreate(
                    ['question_id' => $model->id, 'locale' => $locale],
                    array_merge(
                        Arr::only($values, ['question', 'explanation', 'hints', 'reviewed']),
                        ['source_updated_at' => now()]
                    )
                );
                continue;
            }
            if (method_exists($model, 'cmsTranslations')) {
                CmsTranslation::updateOrCreate(
                    [
                        'translatable_type' => $model->getMorphClass(),
                        'translatable_id' => $model->getKey(),
                        'locale' => $locale,
                    ],
                    [
                        'values' => Arr::except($values, ['reviewed', 'machine_draft']),
                        'reviewed' => (bool) ($values['reviewed'] ?? false),
                        'machine_draft' => (bool) ($values['machine_draft'] ?? false),
                        'source_updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function deleteTranslationsMissingFromSnapshot(Model $model, array $locales): void
    {
        if (
            $model instanceof MathPath
            || $model instanceof MathLevel
            || $model instanceof Formula
            || $model instanceof MathLevelQuestion
        ) {
            $model->translations()->whereNotIn('locale', $locales ?: ['__none__'])->delete();

            return;
        }
        if (method_exists($model, 'cmsTranslations')) {
            $model->cmsTranslations()->whereNotIn('locale', $locales ?: ['__none__'])->delete();
        }
    }

    private function syncQuestionOptions(MathLevelQuestion $question, array $options): void
    {
        $kept = [];
        foreach ($options as $index => $optionData) {
            $option = isset($optionData['id'])
                ? $question->options()->findOrFail($optionData['id'])
                : new MathLevelQuestionOption(['question_id' => $question->id]);

            $option->fill([
                'option_text' => $optionData['option_text'] ?? $optionData['label'] ?? '',
                'explanation' => $optionData['explanation'] ?? null,
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                'order' => $optionData['order'] ?? $index,
            ]);
            $option->question_id = $question->id;
            $option->save();
            $kept[] = $option->id;

            foreach (($optionData['translations'] ?? []) as $locale => $values) {
                MathLevelQuestionOptionTranslation::updateOrCreate(
                    ['option_id' => $option->id, 'locale' => $locale],
                    [
                        'label' => trim((string) ($values['label'] ?? '')) !== ''
                            ? $values['label']
                            : $option->option_text,
                        'explanation' => $values['explanation'] ?? null,
                        'reviewed' => (bool) ($values['reviewed'] ?? false),
                    ]
                );
            }
        }

        $question->options()->whereNotIn('id', $kept ?: [0])->delete();
    }

    private function syncCalculatorFields(CalculatorContent $calculator, array $fields): void
    {
        $kept = [];
        foreach ($fields as $index => $fieldData) {
            $field = isset($fieldData['id'])
                ? $calculator->fields()->findOrFail($fieldData['id'])
                : new CalculatorField(['calculator_content_id' => $calculator->id]);
            $field->fill([
                'key' => $fieldData['key'],
                'input_type' => $fieldData['input_type'] ?? 'number',
                'example_values' => $fieldData['example_values'] ?? null,
                'supported_units' => $fieldData['supported_units'] ?? null,
                'display_order' => $fieldData['display_order'] ?? $index,
                'active' => (bool) ($fieldData['active'] ?? true),
            ]);
            $field->calculator_content_id = $calculator->id;
            $field->save();
            $kept[] = $field->id;
            $this->syncTranslations($field, $fieldData['translations'] ?? []);
        }
        $calculator->fields()->whereNotIn('id', $kept ?: [0])->delete();
    }

    private function syncFormulaRelations(Formula $formula, array $relations): void
    {
        $formula->relations()->delete();
        foreach ($relations as $index => $relation) {
            if (empty($relation['related_type']) || empty($relation['related_key'])) {
                continue;
            }
            FormulaRelation::create([
                'formula_id' => $formula->id,
                'related_type' => $relation['related_type'],
                'related_key' => $relation['related_key'],
                'sort_order' => $relation['sort_order'] ?? $index,
            ]);
        }
    }

    private function syncCalculatorRelations(CalculatorContent $calculator, array $relations): void
    {
        $calculator->relations()->delete();
        foreach ($relations as $relation) {
            if (empty($relation['related_type']) || empty($relation['related_key'])) {
                continue;
            }
            $calculator->relations()->create([
                'related_type' => $relation['related_type'],
                'related_key' => $relation['related_key'],
            ]);
        }
    }

    private function validateStructuredAnswer(MathLevelQuestion $question, array $options): void
    {
        if (in_array($question->type, ['multiple_choice', 'multiple_select', 'true_false'], true)) {
            $correct = collect($options)->where('is_correct', true)->count();
            $valid = $question->type === 'multiple_select' ? $correct >= 1 : $correct === 1;
            if (!$valid) {
                throw ValidationException::withMessages([
                    'options' => $question->type === 'multiple_select'
                        ? 'At least one option must be correct.'
                        : 'Exactly one option must be correct.',
                ]);
            }
        }

        if (in_array($question->type, ['numeric', 'equation', 'geometry_value'], true)
            && empty($question->answer_config)) {
            throw ValidationException::withMessages([
                'answer_config' => 'Structured answer configuration is required for mathematical answers.',
            ]);
        }
    }

    private function translationPayload(Model $model): array
    {
        if ($model instanceof MathPath || $model instanceof MathLevel || $model instanceof Formula) {
            return $model->translations->mapWithKeys(
                fn ($translation) => [$translation->locale => Arr::except(
                    $translation->toArray(),
                    ['id', 'formula_id', 'math_path_id', 'math_level_id', 'locale', 'created_at', 'updated_at']
                )]
            )->all();
        }
        if ($model instanceof MathLevelQuestion) {
            return $model->translations->mapWithKeys(
                fn ($translation) => [$translation->locale => Arr::except(
                    $translation->toArray(),
                    ['id', 'question_id', 'locale', 'created_at', 'updated_at']
                )]
            )->all();
        }
        if (method_exists($model, 'cmsTranslations')) {
            return $model->cmsTranslations->mapWithKeys(fn (CmsTranslation $translation) => [
                $translation->locale => array_merge($translation->values ?? [], [
                    'reviewed' => $translation->reviewed,
                    'machine_draft' => $translation->machine_draft,
                ]),
            ])->all();
        }

        return [];
    }

    private function translationCompleteness(array $translations): int
    {
        $locales = config('cms.locales', ['en']);
        if (!$locales) {
            return 100;
        }

        $complete = collect($locales)->filter(function (string $locale) use ($translations) {
            $values = $translations[$locale] ?? [];

            return collect($values)
                ->except(['reviewed', 'machine_draft'])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->isNotEmpty();
        })->count();

        return (int) round(($complete / count($locales)) * 100);
    }

    private function ensureWorkflow(Model $model): void
    {
        if (!method_exists($model, 'workflow')) {
            return;
        }

        ContentWorkflow::firstOrCreate([
            'workflowable_type' => $model->getMorphClass(),
            'workflowable_id' => $model->getKey(),
        ], ['state' => 'draft']);
    }

    private function snapshotWithRelations(Model $model): array
    {
        $snapshot = array_merge($this->audit->modelSnapshot($model), [
            'translations' => $this->translationPayload($model),
        ]);
        if ($model instanceof MathPath) {
            $snapshot['prerequisite_ids'] = $model->prerequisites()->pluck('math_paths.id')->all();
        }
        if ($model instanceof MathLevelQuestion) {
            $snapshot['options'] = $model->options()
                ->with('translations')
                ->get()
                ->map(fn (MathLevelQuestionOption $option) => array_merge(
                    Arr::except(
                        $option->toArray(),
                        ['id', 'question_id', 'created_at', 'updated_at', 'translations']
                    ),
                    [
                        'translations' => $option->translations->mapWithKeys(
                            fn ($translation) => [$translation->locale => Arr::only(
                                $translation->toArray(),
                                ['label', 'explanation', 'reviewed']
                            )]
                        )->all(),
                    ]
                ))
                ->all();
        }
        if ($model instanceof Formula) {
            $snapshot['relations'] = $model->relations()
                ->get(['related_type', 'related_key', 'sort_order'])
                ->toArray();
        }
        if ($model instanceof CalculatorContent) {
            $snapshot['fields'] = $model->fields()
                ->with('cmsTranslations')
                ->get()
                ->map(fn (CalculatorField $field) => array_merge(
                    Arr::except(
                        $field->toArray(),
                        ['id', 'calculator_content_id', 'created_at', 'updated_at', 'cms_translations']
                    ),
                    ['translations' => $this->translationPayload($field)]
                ))
                ->all();
            $snapshot['relations'] = $model->relations()
                ->get(['related_type', 'related_key'])
                ->toArray();
        }

        return $snapshot;
    }
}
