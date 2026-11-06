<?php

namespace App\Import;

use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;

/**
 * Ordnet Rohwerte aus der Ermittler-Spalte einem Team zu.
 * Unbekannte Rohwerte erzeugen ein neues Team mit dem Rohwert als Namen und einen Alias dazu.
 */
class TeamResolver
{
    /** @var array<string, int> Cache: "series_id|raw_value" => team_id */
    private array $cache = [];

    /** Anzahl der in diesem Lauf neu angelegten Teams. */
    public int $createdTeams = 0;

    /** Leert den Cache, z. B. vor einem neuen Lauf (nach einem zurückgerollten Probelauf sind IDs ungültig). */
    public function reset(): void
    {
        $this->cache = [];
        $this->createdTeams = 0;
    }

    public function resolve(Series $series, ?string $rawValue): ?int
    {
        if ($rawValue === null || trim($rawValue) === '') {
            return null;
        }

        $key = $series->id.'|'.$rawValue;

        return $this->cache[$key] ??= $this->lookupOrCreate($series, $rawValue);
    }

    private function lookupOrCreate(Series $series, string $rawValue): int
    {
        $teamId = TeamAlias::query()
            ->where('series_id', $series->id)
            ->where('raw_value', $rawValue)
            ->value('team_id');

        if ($teamId !== null) {
            return (int) $teamId;
        }

        $team = Team::create(['series_id' => $series->id, 'name' => $rawValue]);
        $team->aliases()->create(['raw_value' => $rawValue]);
        $this->createdTeams++;

        return $team->id;
    }
}
