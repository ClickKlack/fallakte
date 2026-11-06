<?php

namespace Database\Factories;

use App\Models\ImportRun;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRun>
 */
class ImportRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-1 month');

        return [
            'series_id' => Series::factory(),
            'started_at' => $started,
            'finished_at' => (clone $started)->modify('+30 seconds'),
            'created_count' => fake()->numberBetween(0, 50),
            'updated_count' => fake()->numberBetween(0, 50),
            'skipped_count' => fake()->numberBetween(0, 5),
            'error_count' => 0,
            'errors' => null,
        ];
    }
}
