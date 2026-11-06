<?php

namespace Database\Factories;

use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Series>
 */
class SeriesFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Reihe '.fake()->unique()->numerify('####');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /** Die echte Reihe „Tatort“. */
    public function tatort(): static
    {
        return $this->state(['name' => 'Tatort', 'slug' => Series::TATORT]);
    }

    /** Die echte Reihe „Polizeiruf 110“. */
    public function polizeiruf(): static
    {
        return $this->state(['name' => 'Polizeiruf 110', 'slug' => Series::POLIZEIRUF]);
    }
}
