<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FixedPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedPeriod> */
final class FixedPeriodFactory extends Factory
{
    protected $model = FixedPeriod::class;

    public function definition(): array
    {
        return [
            'years' => $this->faker->unique()->numberBetween(1, 40),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'active' => true,
        ];
    }
}
