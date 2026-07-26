<?php

namespace App\Services\Cms;

use App\Models\PracticeTemplate;

class PracticeTemplatePreviewService
{
    public function preview(PracticeTemplate $template): array
    {
        $values = [];
        foreach ($template->entered_fields as $index => $field) {
            $range = $template->value_ranges[$field] ?? ['min' => 1, 'max' => 10];
            $min = (float) ($range['min'] ?? 1);
            $max = (float) ($range['max'] ?? max(10, $min));
            $fraction = (($template->id + $index + 1) % 7 + 1) / 8;
            $value = $min + (($max - $min) * $fraction);
            $values[$field] = $template->integer_only
                ? (int) round($value)
                : round($value, $template->decimal_precision);
        }

        return [
            'template_key' => $template->key,
            'calculator_key' => $template->calculator?->key,
            'known_values' => $values,
            'requested_fields' => $template->requested_fields,
            'allowed_units' => $template->allowed_units,
            'expected_output' => [
                'kind' => 'deterministic_calculator_result',
                'fields' => $template->requested_fields,
                'resolved_by' => 'MathDashboard deterministic calculator layer',
            ],
            'notice' => 'The CMS only previews template inputs. It does not duplicate solver logic.',
        ];
    }
}
