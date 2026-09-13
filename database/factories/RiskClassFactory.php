<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RiskClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RiskClass> */
final class RiskClassFactory extends Factory
{
    protected $model = RiskClass::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(3, true),
            'max_ltv' => $this->faker->randomFloat(1, 50, 100),
            'nhg' => false,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'active' => true,
        ];
    }
}
