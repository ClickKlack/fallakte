<?php

namespace Database\Factories;

use App\Models\Series;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->city();
        $investigators = fake()->lastName().'/'.fake()->lastName();

        return [
            'series_id' => Series::factory(),
            'name' => "{$city} – {$investigators}",
            'city' => $city,
            'investigators' => $investigators,
        ];
    }
}
