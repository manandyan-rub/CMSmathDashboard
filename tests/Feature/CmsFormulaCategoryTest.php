<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmsFormulaCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_formula_categories_from_database(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        DB::table('formulas')->insert([
            [
                'category' => 'statistics:stats-measures',
                'formula' => '\\sigma^2 = \\frac{1}{N}\\sum(x_i-\\mu)^2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'algebra:equations',
                'formula' => 'ax+b=0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'statistics:stats-measures',
                'formula' => 'z=\\frac{x-\\mu}{\\sigma}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/cms-api/formula-categories');

        $response
            ->assertOk()
            ->assertExactJson([
                'items' => [
                    'algebra:equations',
                    'statistics:stats-measures',
                ],
            ]);
    }
}
