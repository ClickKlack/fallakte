<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    /**
     * Standard: bereits ausgestrahlte, ungesehene Folge ohne Team.
     * Der Status wird beim Speichern aus first_aired abgeleitet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'series_id' => Series::factory(),
            'number' => fake()->unique()->numberBetween(1, 999_999),
            'title' => rtrim(fake()->sentence(3), '.'),
            'first_aired' => fake()->dateTimeBetween('-50 years', '-1 day'),
            'team_id' => null,
            'seen' => false,
            'seen_at' => null,
            'wikipedia_ref' => null,
            'manual_fields' => [],
        ];
    }

    /** Angekündigte Folge mit Ausstrahlung in der Zukunft. */
    public function announced(): static
    {
        return $this->state(['first_aired' => fake()->dateTimeBetween('+1 day', '+1 year')]);
    }

    /** Angekündigte Folge ohne bekanntes Datum. */
    public function withoutDate(): static
    {
        return $this->state(['first_aired' => null]);
    }

    /** Gesehene Folge, optional mit Datum. */
    public function seen(?string $seenAt = null): static
    {
        return $this->state(['seen' => true, 'seen_at' => $seenAt]);
    }
}
