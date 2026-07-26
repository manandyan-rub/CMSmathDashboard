<?php

namespace App\Services\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsResourceRegistry
{
    public function all(): array
    {
        return config('cms_resources', []);
    }

    public function definition(string $resource): array
    {
        $definition = config("cms_resources.{$resource}");
        if (!$definition) {
            throw new NotFoundHttpException('Unknown CMS resource.');
        }

        return $definition;
    }

    public function model(string $resource): Model
    {
        $class = $this->definition($resource)['model'];

        return new $class();
    }

    public function resourceForModel(Model|string $model): ?string
    {
        $class = is_string($model) ? $model : $model::class;

        return collect($this->all())
            ->search(fn (array $definition) => $definition['model'] === $class) ?: null;
    }

    public function viewPermission(string $resource): string
    {
        $definition = $this->definition($resource);

        return $definition['view_permission']
            ?? $definition['permission'].'.view';
    }

    public function managePermission(string $resource): string
    {
        $definition = $this->definition($resource);

        return $definition['manage_permission']
            ?? $definition['permission'].'.manage';
    }

    public function rules(string $resource, ?int $id = null): array
    {
        $definition = $this->definition($resource);
        $model = $this->model($resource);
        $rules = [];

        foreach ($definition['fields'] as $field => $meta) {
            $fieldRules = $meta['rules'] ?? ['nullable'];
            if (($meta['unique'] ?? false) === true) {
                $fieldRules[] = Rule::unique($model->getTable(), $field)->ignore($id);
            }
            $rules[$field] = $fieldRules;
            foreach ($meta['item_rules'] ?? [] as $nestedField => $nestedRules) {
                $suffix = $nestedField === '*' ? '' : ".{$nestedField}";
                $rules["{$field}.*{$suffix}"] = $nestedRules;
            }
        }

        if (!empty($definition['translation_fields'])) {
            $rules['translations'] = ['nullable', 'array'];
            foreach ($definition['translation_fields'] as $field) {
                $rules["translations.*.{$field}"] = ['nullable'];
            }
            $rules['translations.*.reviewed'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function schema(string $resource): array
    {
        $definition = $this->definition($resource);
        $fields = collect($definition['fields'])->map(function (array $meta, string $field) {
            $fieldMeta = [
                'key' => $field,
                'label' => Str::headline($field),
                'type' => $meta['type'] ?? 'text',
                'required' => (bool) ($meta['required'] ?? false),
            ];

            if (
                in_array($meta['type'] ?? null, ['relation', 'relation_multiple'], true)
                && isset($meta['options'])
            ) {
                [$class, $labelField] = $meta['options'];
                $fieldMeta['options'] = $class::query()
                    ->orderBy($labelField)
                    ->limit(500)
                    ->get(['id', $labelField])
                    ->map(fn (Model $model) => [
                        'value' => $model->getKey(),
                        'label' => $model->getAttribute($labelField),
                    ])
                    ->all();
            } elseif (($meta['options_source'] ?? null) === 'formula_categories') {
                $fieldMeta['options'] = \App\Models\Formula::query()
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
                    ->map(fn (string $category) => [
                        'value' => $category,
                        'label' => $category,
                    ])
                    ->values()
                    ->all();
            } elseif (isset($meta['options']) && is_array($meta['options'])) {
                $fieldMeta['options'] = collect($meta['options'])->map(
                    fn ($option) => ['value' => $option, 'label' => Str::headline((string) $option)]
                )->all();
            }

            return $fieldMeta;
        })->values()->all();

        return [
            'key' => $resource,
            'title' => $definition['title'],
            'fields' => $fields,
            'translation_fields' => collect($definition['translation_fields'] ?? [])->map(
                fn (string $field) => ['key' => $field, 'label' => Str::headline($field)]
            )->values(),
            'locales' => config('cms.locales', ['en']),
            'sorts' => $definition['sorts'],
            'permissions' => [
                'view' => $this->viewPermission($resource),
                'manage' => $this->managePermission($resource),
            ],
        ];
    }
}
