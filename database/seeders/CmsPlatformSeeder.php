<?php

namespace Database\Seeders;

use App\Models\CalculatorContent;
use App\Models\FeatureFlag;
use App\Models\PlatformSetting;
use App\Models\XpRule;
use Illuminate\Database\Seeder;

class CmsPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedFeatureFlags();
        $this->seedCalculators();
        $this->seedXpRules();
    }

    private function seedSettings(): void
    {
        $settings = [
            ['platform.name', 'general', 'string', 'MathDashboard', 'Platform name', true],
            ['platform.default_locale', 'localization', 'string', 'en', 'Default locale', true],
            ['platform.supported_locales', 'localization', 'json', ['en', 'hy', 'ru', 'fr'], 'Supported locales', true],
            ['math.default_precision', 'math', 'integer', 2, 'Default decimal precision', true],
            ['math.default_units', 'math', 'json', ['metric'], 'Default unit systems', true],
            ['uploads.max_size_mb', 'uploads', 'integer', 10, 'Maximum upload size', false],
            ['uploads.allowed_types', 'uploads', 'json', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], 'Allowed upload types', false],
            ['support.email', 'legal', 'string', 'support@example.com', 'Support email', true],
            ['legal.privacy_url', 'legal', 'string', null, 'Privacy policy URL', true],
            ['legal.terms_url', 'legal', 'string', null, 'Terms URL', true],
            ['registration.require_email_verification', 'registration', 'boolean', true, 'Require verified email', true],
            ['ai.monthly_user_limit', 'ai', 'integer', 100, 'Monthly AI generation limit', false],
            ['documents.user_limit', 'documents', 'integer', 100, 'Documents per user', false],
            ['profiles.public_enabled', 'privacy', 'boolean', false, 'Public profiles enabled', true],
            ['leaderboards.public_enabled', 'gamification', 'boolean', true, 'Public leaderboard enabled', true],
            ['gamification.daily_xp_limit', 'gamification', 'integer', 500, 'Daily XP limit', false],
            ['gamification.maximum_rewarded_attempts', 'gamification', 'integer', 3, 'Maximum rewarded attempts', false],
            ['gamification.repeated_answer_protection', 'gamification', 'boolean', true, 'Repeated-answer protection', false],
        ];

        foreach ($settings as [$key, $group, $type, $default, $label, $public]) {
            PlatformSetting::firstOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'type' => $type,
                    'value' => $default,
                    'default_value' => $default,
                    'validation_rules' => [],
                    'label' => $label,
                    'description' => "Runtime setting for {$label}.",
                    'public' => $public,
                    'editable' => true,
                ]
            );
        }
    }

    private function seedFeatureFlags(): void
    {
        $flags = [
            ['ai_enabled', 'AI enabled', false],
            ['registrations_enabled', 'Registrations enabled', true],
            ['public_leaderboard_enabled', 'Public leaderboard enabled', true],
            ['games_enabled', 'Games enabled', true],
            ['calculators_enabled', 'Calculators enabled', true],
            ['exercise_studio_enabled', 'Exercise Studio enabled', true],
            ['practice_generator_enabled', 'Practice generator enabled', true],
            ['maintenance_mode', 'Maintenance mode', false],
            ['scan_problem_enabled', 'Scan problem enabled', false],
            ['notebook_enabled', 'Notebook enabled', true],
        ];

        foreach ($flags as [$key, $title, $enabled]) {
            FeatureFlag::firstOrCreate(
                ['key' => $key],
                [
                    'title' => $title,
                    'description' => "Controls the {$title} platform capability.",
                    'enabled' => $enabled,
                    'environment' => 'all',
                    'public' => $key !== 'maintenance_mode',
                ]
            );
        }
    }

    private function seedCalculators(): void
    {
        $calculators = [
            ['rectangle', 'plane_geometry'],
            ['circle', 'plane_geometry'],
            ['triangle', 'plane_geometry'],
            ['parallelogram', 'plane_geometry'],
            ['trapezoid', 'plane_geometry'],
            ['cube', 'solid_geometry'],
            ['cuboid', 'solid_geometry'],
            ['sphere', 'solid_geometry'],
            ['cylinder', 'solid_geometry'],
            ['cone', 'solid_geometry'],
            ['stairs', 'measurement'],
        ];

        foreach ($calculators as $index => [$key, $category]) {
            $calculator = CalculatorContent::firstOrCreate(
                ['key' => $key],
                [
                    'category' => $category,
                    'active' => true,
                    'display_order' => ($index + 1) * 10,
                    'supported_units' => ['metric', 'imperial'],
                    'default_unit' => 'metric',
                    'default_precision' => 2,
                    'metadata' => ['solver_owned_by' => 'MathDashboard'],
                ]
            );

            $calculator->cmsTranslations()->firstOrCreate(
                ['locale' => 'en'],
                [
                    'values' => [
                        'title' => str($key)->headline()->toString(),
                        'description' => "Educational metadata for the {$key} calculator.",
                        'help_text' => null,
                        'educational_notes' => null,
                        'warning_text' => null,
                    ],
                    'reviewed' => true,
                    'machine_draft' => false,
                    'source_updated_at' => $calculator->updated_at,
                ]
            );
        }
    }

    private function seedXpRules(): void
    {
        $rules = [
            ['exercise_completed', 'easy', 5, 0],
            ['exercise_completed', 'hard', 15, 0],
            ['exercise_without_hints', null, 0, 3],
            ['previously_failed_corrected', null, 0, 2],
        ];

        foreach ($rules as [$action, $difficulty, $base, $bonus]) {
            XpRule::firstOrCreate(
                ['action_type' => $action, 'difficulty' => $difficulty, 'topic' => null],
                [
                    'base_xp' => $base,
                    'bonus_xp' => $bonus,
                    'daily_cap' => 500,
                    'maximum_rewarded_attempts' => 3,
                    'repeated_answer_protection' => true,
                    'active' => true,
                ]
            );
        }
    }
}
