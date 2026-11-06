<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamAlias>
 */
class TeamAliasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // series_id wird beim Speichern aus dem Team übernommen.
        return [
            'team_id' => Team::factory(),
            'raw_value' => fake()->unique()->lastName().', '.fake()->lastName(),
        ];
    }
}
