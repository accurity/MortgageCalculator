<?php

namespace Database\Factories;

use App\Models\Lender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lender>
 */
class LenderFactory extends Factory
{
    protected $model = Lender::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'apply_url' => $this->faker->url(),
            'afm_number' => (string) $this->faker->numberBetween(10000000, 99999999),
            'logo_path' => null,
            'logo_url' => null,
            'active' => true,
            'sort_order' => 0,
            'delta' => 0,
        ];
    }
}
