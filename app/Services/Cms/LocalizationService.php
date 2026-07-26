<?php

namespace App\Services\Cms;

use App\Models\Achievement;
use App\Models\CalculatorContent;
use App\Models\CmsTranslation;
use App\Models\Formula;
use App\Models\FormulaTranslation;
use App\Models\GameDefinition;
use App\Models\MathLevel;
use App\Models\MathLevelQuestion;
use App\Models\MathLevelQuestionTranslation;
use App\Models\MathLevelTranslation;
use App\Models\MathPath;
use App\Models\MathPathTranslation;
use App\Models\PracticeTemplate;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LocalizationService
{
    private const ENTITIES = [
        'formulas' => [Formula::class, ['name', 'description'], 'translations'],
        'paths' => [MathPath::class, ['title', 'description'], 'translations'],
        'levels' => [MathLevel::class, ['title', 'description', 'topic'], 'translations'],
        'questions' => [MathLevelQuestion::class, ['question', 'explanation', 'hints'], 'translations'],
        'calculators' => [CalculatorContent::class, ['title', 'description', 'help_text', 'educational_notes', 'warning_text'], 'cmsTranslations'],
        'practice_templates' => [PracticeTemplate::class, ['question_text', 'hint_text', 'explanation_text'], 'cmsTranslations'],
        'games' => [GameDefinition::class, ['title', 'description', 'instructions'], 'cmsTranslations'],
        'achievements' => [Achievement::class, ['title', 'description'], 'cmsTranslations'],
        'rewards' => [Reward::class, ['title', 'description'], 'cmsTranslations'],
    ];

    public function __construct(private readonly AuditService $audit)
    {
    }

    public function overview(): array
    {
        $locales = config('cms.locales', ['en']);

        return collect(self::ENTITIES)->map(function (array $definition, string $type) use ($locales) {
            [$class, $fields, $relation] = $definition;
            $total = $class::count();
            $counts = collect($locales)->mapWithKeys(function (string $locale) use ($class, $relation) {
                $count = $class::whereHas($relation, fn ($query) => $query->where('locale', $locale))->count();

                return [$locale => $count];
            });
            $possible = max(1, $total * count($locales));

            return [
                'type' => $type,
                'total' => $total,
                'fields' => $fields,
                'by_locale' => $counts,
                'completeness' => (int) round(($counts->sum() / $possible) * 100),
            ];
        })->values()->all();
    }

    public function paginate(
        string $entityType,
        string $locale,
        bool $missingOnly,
        bool $outdatedOnly,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        [$class, $fields, $relation] = $this->definition($entityType);
        $query = $class::query()->with($relation);
        $parentTable = (new $class())->getTable();
        $translationTable = (new $class())->{$relation}()->getRelated()->getTable();
        if ($missingOnly) {
            $query->whereDoesntHave($relation, fn ($translation) => $translation->where('locale', $locale));
        }
        if ($outdatedOnly) {
            if (Schema::hasColumn($translationTable, 'source_updated_at')) {
                $query->whereHas($relation, fn ($translation) => $translation
                    ->where('locale', $locale)
                    ->whereColumn(
                        "{$translationTable}.source_updated_at",
                        '<',
                        "{$parentTable}.updated_at"
                    ));
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($search !== '') {
            $query->where(function ($inner) use ($search, $relation, $fields) {
                foreach (['slug', 'key', 'category', 'question'] as $column) {
                    if ($inner->getModel()->isFillable($column)) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                }
                $inner->orWhereHas($relation, function ($translation) use ($search, $fields) {
                    foreach ($fields as $field) {
                        if ($translation->getModel() instanceof CmsTranslation) {
                            $translation->where('values', 'like', "%{$search}%");
                            break;
                        }
                        $translation->orWhere($field, 'like', "%{$search}%");
                    }
                });
            });
        }

        return $query->latest('id')->paginate(min(100, max(5, $perPage)));
    }

    public function serialize(Model $model, string $entityType): array
    {
        [, $fields, $relation] = $this->definition($entityType);
        $translations = $model->{$relation}->mapWithKeys(function ($translation) {
            $values = $translation instanceof CmsTranslation
                ? ($translation->values ?? [])
                : Arr::except(
                    $translation->toArray(),
                    [
                        'id',
                        'formula_id',
                        'math_path_id',
                        'math_level_id',
                        'question_id',
                        'locale',
                        'created_at',
                        'updated_at',
                    ]
                );

            return [$translation->locale => array_merge($values, [
                'reviewed' => (bool) ($translation->reviewed ?? false),
                'machine_draft' => (bool) ($translation->machine_draft ?? false),
            ])];
        })->all();

        return [
            'id' => $model->getKey(),
            'identity' => $model->getAttribute('slug')
                ?? $model->getAttribute('key')
                ?? $model->getAttribute('category')
                ?? "#{$model->getKey()}",
            'fields' => $fields,
            'translations' => $translations,
            'updated_at' => $model->updated_at,
        ];
    }

    public function update(
        string $entityType,
        int $id,
        string $locale,
        array $values,
        bool $reviewed,
        bool $machineDraft,
        string $reason,
    ): Model {
        [$class, $fields] = $this->definition($entityType);
        $model = $class::findOrFail($id);
        $values = Arr::only($values, $fields);

        DB::transaction(function () use (
            $entityType,
            $model,
            $locale,
            $values,
            $reviewed,
            $machineDraft,
            $reason
        ) {
            $before = $this->serialize($model->load($this->definition($entityType)[2]), $entityType);
            $this->writeTranslation($model, $locale, $values, $reviewed, $machineDraft);

            if ($locale === config('cms.source_locale', 'en')) {
                $this->markOtherLocalesOutdated($model, $locale);
            }

            $after = $this->serialize($model->fresh($this->definition($entityType)[2]), $entityType);
            $this->audit->record(
                'translation.updated',
                $model,
                ['translation' => $before['translations'][$locale] ?? null],
                ['translation' => $after['translations'][$locale] ?? null],
                $reason
            );
        });

        return $model->fresh($this->definition($entityType)[2]);
    }

    public function copySource(string $entityType, int $id, string $targetLocale, string $reason): Model
    {
        [$class, , $relation] = $this->definition($entityType);
        $model = $class::with($relation)->findOrFail($id);
        $payload = $this->serialize($model, $entityType);
        $source = $payload['translations'][config('cms.source_locale', 'en')] ?? null;
        if (!$source) {
            throw ValidationException::withMessages(['translation' => 'The source locale is missing.']);
        }

        return $this->update(
            $entityType,
            $id,
            $targetLocale,
            Arr::except($source, ['reviewed', 'machine_draft']),
            false,
            false,
            $reason
        );
    }

    public function import(array $items, string $reason): int
    {
        return DB::transaction(function () use ($items, $reason) {
            $count = 0;
            foreach ($items as $item) {
                $this->update(
                    $item['entity_type'],
                    $item['entity_id'],
                    $item['locale'],
                    $item['values'],
                    (bool) ($item['reviewed'] ?? false),
                    (bool) ($item['machine_draft'] ?? false),
                    $reason
                );
                $count++;
            }

            return $count;
        });
    }

    public function types(): array
    {
        return array_keys(self::ENTITIES);
    }

    public function findSerialized(string $entityType, int $id): array
    {
        [$class, , $relation] = $this->definition($entityType);

        return $this->serialize($class::with($relation)->findOrFail($id), $entityType);
    }

    public function exportItems(string $entityType): iterable
    {
        [$class, , $relation] = $this->definition($entityType);

        return $class::query()->with($relation)->lazyById(250);
    }

    private function definition(string $entityType): array
    {
        $definition = self::ENTITIES[$entityType] ?? null;
        if (!$definition) {
            throw ValidationException::withMessages(['entity_type' => 'Unsupported translation entity type.']);
        }

        return $definition;
    }

    private function writeTranslation(
        Model $model,
        string $locale,
        array $values,
        bool $reviewed,
        bool $machineDraft,
    ): void {
        $attributes = array_merge($values, ['source_updated_at' => $model->updated_at ?? now()]);
        if ($model instanceof Formula) {
            FormulaTranslation::updateOrCreate(
                ['formula_id' => $model->id, 'locale' => $locale],
                Arr::only($attributes, ['name', 'description'])
            );
            return;
        }
        if ($model instanceof MathPath) {
            MathPathTranslation::updateOrCreate(
                ['math_path_id' => $model->id, 'locale' => $locale],
                Arr::only($attributes, ['title', 'description'])
            );
            return;
        }
        if ($model instanceof MathLevel) {
            MathLevelTranslation::updateOrCreate(
                ['math_level_id' => $model->id, 'locale' => $locale],
                Arr::only($attributes, ['title', 'description', 'topic'])
            );
            return;
        }
        if ($model instanceof MathLevelQuestion) {
            MathLevelQuestionTranslation::updateOrCreate(
                ['question_id' => $model->id, 'locale' => $locale],
                array_merge(Arr::only($attributes, ['question', 'explanation', 'hints']), [
                    'reviewed' => $reviewed,
                    'source_updated_at' => $attributes['source_updated_at'],
                ])
            );
            return;
        }

        CmsTranslation::updateOrCreate(
            [
                'translatable_type' => $model->getMorphClass(),
                'translatable_id' => $model->getKey(),
                'locale' => $locale,
            ],
            [
                'values' => $values,
                'reviewed' => $reviewed,
                'machine_draft' => $machineDraft,
                'source_updated_at' => $attributes['source_updated_at'],
                'reviewed_by' => $reviewed ? request()->user()?->id : null,
                'reviewed_at' => $reviewed ? now() : null,
            ]
        );
    }

    private function markOtherLocalesOutdated(Model $model, string $sourceLocale): void
    {
        if (method_exists($model, 'cmsTranslations')) {
            $model->cmsTranslations()->where('locale', '!=', $sourceLocale)->update([
                'reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }
        if ($model instanceof MathLevelQuestion) {
            $model->translations()->where('locale', '!=', $sourceLocale)->update(['reviewed' => false]);
        }
    }
}
