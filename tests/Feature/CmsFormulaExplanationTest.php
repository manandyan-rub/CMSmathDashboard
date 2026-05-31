<?php

namespace Tests\Feature;

use App\Models\Formula;
use App\Models\FormulaStep;
use App\Models\FormulaTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsFormulaExplanationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_formula_explanations_for_locale(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $formula = Formula::create([
            'category' => 'statistics:stats-measures',
            'formula' => 'P(|X-\mu| \geq k\sigma) \leq \frac{1}{k^2}',
        ]);

        FormulaTranslation::create([
            'formula_id' => $formula->id,
            'locale' => 'hy',
            'name' => 'Չեբիշևի անհավասարություն',
            'description' => 'Մեկնաբանություն',
        ]);

        FormulaStep::create([
            'formula_id' => $formula->id,
            'locale' => 'hy',
            'steps_json' => [
                [
                    'latex' => 'P(|X-\mu| \geq k\sigma)',
                    'note' => 'Start with the target probability.',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/cms-api/formula-explanations?locale=hy');

        $response
            ->assertOk()
            ->assertJsonPath('items.0.id', $formula->id)
            ->assertJsonPath('items.0.locale', 'hy')
            ->assertJsonPath('items.0.steps_count', 1)
            ->assertJsonPath('items.0.steps_json.0.note', 'Start with the target probability.');
    }

    public function test_admin_can_update_formula_explanation_steps_per_locale(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $formula = Formula::create([
            'category' => 'algebra:equations',
            'formula' => 'ax+b=0',
        ]);

        $payload = [
            'locale' => 'fr',
            'steps_json' => [
                [
                    'latex' => 'ax=-b',
                    'note' => 'Move the constant term.',
                ],
                [
                    'latex' => 'x=-\frac{b}{a}',
                    'note' => 'Divide by the coefficient.',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->patchJson("/cms-api/formula-explanations/{$formula->id}/steps", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('item.locale', 'fr')
            ->assertJsonPath('item.steps_count', 2)
            ->assertJsonPath('item.steps_json.1.note', 'Divide by the coefficient.');

        $this->assertDatabaseHas('formula_steps', [
            'formula_id' => $formula->id,
            'locale' => 'fr',
        ]);
    }

    public function test_admin_can_fetch_single_formula_explanation_for_selected_locale(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $formula = Formula::create([
            'category' => 'algebra:equations',
            'formula' => 'ax+b=0',
        ]);

        FormulaTranslation::create([
            'formula_id' => $formula->id,
            'locale' => 'fr',
            'name' => 'Equation lineaire',
            'description' => 'French description',
        ]);

        FormulaStep::create([
            'formula_id' => $formula->id,
            'locale' => 'fr',
            'steps_json' => [
                [
                    'latex' => 'ax=-b',
                    'note' => 'Move the constant.',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->getJson("/cms-api/formula-explanations/{$formula->id}?locale=fr");

        $response
            ->assertOk()
            ->assertJsonPath('item.id', $formula->id)
            ->assertJsonPath('item.locale', 'fr')
            ->assertJsonPath('item.name', 'Equation lineaire')
            ->assertJsonPath('item.steps_json.0.note', 'Move the constant.');
    }

    public function test_admin_can_filter_formula_explanations_by_cache_status(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $cachedForLocale = Formula::create([
            'category' => 'algebra:equations',
            'formula' => 'ax+b=0',
        ]);
        $cachedForOtherLocale = Formula::create([
            'category' => 'geometry:area',
            'formula' => 'A=\pi r^2',
        ]);
        $missingEverywhere = Formula::create([
            'category' => 'probability:basic',
            'formula' => 'P(A|B)=\frac{P(A\cap B)}{P(B)}',
        ]);

        FormulaStep::create([
            'formula_id' => $cachedForLocale->id,
            'locale' => 'hy',
            'steps_json' => [
                [
                    'latex' => 'ax=-b',
                    'note' => 'Move b.',
                ],
            ],
        ]);

        FormulaStep::create([
            'formula_id' => $cachedForOtherLocale->id,
            'locale' => 'ru',
            'steps_json' => [
                [
                    'latex' => 'A=\pi r^2',
                    'note' => 'Area formula.',
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->getJson('/cms-api/formula-explanations?locale=hy&cache=cached')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $cachedForLocale->id);

        $missingResponse = $this->actingAs($admin)
            ->getJson('/cms-api/formula-explanations?locale=hy&cache=missing')
            ->assertOk()
            ->assertJsonCount(2, 'items');

        $missingIds = collect($missingResponse->json('items'))->pluck('id');

        $this->assertTrue($missingIds->contains($cachedForOtherLocale->id));
        $this->assertTrue($missingIds->contains($missingEverywhere->id));
    }
}
