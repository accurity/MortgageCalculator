<?php

namespace Database\Factories;

use App\Models\TaxYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxYear>
 */
class TaxYearFactory extends Factory
{
    protected $model = TaxYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jaar' => $this->faker->unique()->numberBetween(2000, 2100),
            'schijven' => [
                ['tot' => 38883.0, 'tarief' => 0.3570],
                ['tot' => 79137.0, 'tarief' => 0.3756],
                ['tot' => null, 'tarief' => 0.4950],
            ],
            'max_aftrektarief' => 0.3756,
            'ewf_percentage' => 0.0035,
            'ewf_grens' => 1350000.0,
            'hillen_aandeel' => 0.7333,
        ];
    }
}
