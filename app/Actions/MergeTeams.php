<?php

namespace App\Actions;

use App\Models\Team;
use App\Models\TeamAlias;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Führt ein Team in ein anderes derselben Reihe über: Folgen und Schreibweisen (Aliase)
 * wandern zum Zielteam, das Quellteam wird gelöscht. Der nächste Import ordnet damit
 * die Rohwerte beider Teams dem Zielteam zu.
 */
class MergeTeams
{
    /**
     * @return array{episodes: int, aliases: int}
     *
     * @throws InvalidArgumentException bei gleichem Team oder unterschiedlichen Reihen
     */
    public function __invoke(Team $source, Team $target): array
    {
        if ($source->is($target)) {
            throw new InvalidArgumentException(__('teams.merge_same'));
        }

        if ($source->series_id !== $target->series_id) {
            throw new InvalidArgumentException(__('teams.merge_other_series'));
        }

        return DB::transaction(function () use ($source, $target) {
            $episodes = $source->episodes()->update(['team_id' => $target->id]);
            $aliases = TeamAlias::query()->where('team_id', $source->id)->update(['team_id' => $target->id]);

            // Ermittlerangaben übernehmen, falls das Zielteam noch keine hat
            $target->fill([
                'city' => $target->city ?? $source->city,
                'investigators' => $target->investigators ?? $source->investigators,
            ])->save();

            $source->delete();

            return ['episodes' => $episodes, 'aliases' => $aliases];
        });
    }
}
