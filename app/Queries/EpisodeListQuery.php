<?php

namespace App\Queries;

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use App\Support\BroadcasterNames;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Baut die Abfrage der Folgenliste aus den Filtern.
 */
class EpisodeListQuery
{
    /**
     * @return Builder<Episode>
     */
    public function build(EpisodeFilters $filters): Builder
    {
        $query = Episode::query()
            ->select('episodes.*')
            ->with(['series:id,name,slug', 'team:id,name']);

        if ($filters->series !== null) {
            $query->whereHas('series', fn (Builder $q) => $q->where('slug', $filters->series));
        }

        if ($filters->team !== null) {
            $query->where('episodes.team_id', $filters->team);
        }

        if ($filters->broadcaster !== null) {
            // Auch Koproduktionen finden („SWR“ trifft „SWR/SRF“)
            $query->where(function (Builder $q) use ($filters) {
                $q->where('episodes.broadcaster', $filters->broadcaster);
                foreach (BroadcasterNames::likePatterns($filters->broadcaster) as $pattern) {
                    $q->orWhereRaw('episodes.broadcaster LIKE ? ESCAPE ?', [$pattern, '!']);
                }
            });
        }

        match ($filters->status) {
            'seen' => $query->where('episodes.status', EpisodeStatus::Aired)->where('episodes.seen', true),
            'unseen' => $query->where('episodes.status', EpisodeStatus::Aired)->where('episodes.seen', false),
            'announced' => $query->where('episodes.status', EpisodeStatus::Announced),
            default => null,
        };

        if ($filters->yearFrom !== null) {
            $query->whereDate('episodes.first_aired', '>=', "{$filters->yearFrom}-01-01");
        }

        if ($filters->yearTo !== null) {
            $query->whereDate('episodes.first_aired', '<=', "{$filters->yearTo}-12-31");
        }

        // Zeitraum „gesehen am“ – trifft nur gesehene Folgen mit Datum
        if ($filters->seenFrom !== null) {
            $query->where('episodes.seen', true)->whereDate('episodes.seen_at', '>=', $filters->seenFrom);
        }

        if ($filters->seenTo !== null) {
            $query->where('episodes.seen', true)->whereDate('episodes.seen_at', '<=', $filters->seenTo);
        }

        if ($filters->search !== null) {
            // "!" als Escape-Zeichen funktioniert in SQLite und MariaDB gleich (Backslash nicht).
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters->search));
            $lower = $query->getConnection()->getDriverName() === 'sqlite' ? 'unicode_lower' : 'LOWER';
            $query->whereRaw("{$lower}(episodes.title) LIKE ? ESCAPE ?", ["%{$escaped}%", '!']);
        }

        $this->applySort($query, $filters);

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, Episode>
     */
    public function paginate(EpisodeFilters $filters): LengthAwarePaginator
    {
        return $this->build($filters)
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    /** @param Builder<Episode> $query */
    private function applySort(Builder $query, EpisodeFilters $filters): void
    {
        $direction = $filters->direction;

        match ($filters->sort) {
            // Folgen ohne Datum stehen immer am Ende
            'date' => $query->orderByRaw('episodes.first_aired is null')
                ->orderBy('episodes.first_aired', $direction),
            'title' => $query->orderBy('episodes.title', $direction),
            // Folgen ohne „gesehen am“ stehen immer am Ende
            'seen_at' => $query->orderByRaw('episodes.seen_at is null')
                ->orderBy('episodes.seen_at', $direction),
            'team' => $query->leftJoin('teams', 'teams.id', '=', 'episodes.team_id')
                ->orderByRaw('teams.name is null')
                ->orderBy('teams.name', $direction),
            default => $query->orderBy('episodes.sort_key', $direction),
        };

        // Stabile Reihenfolge bei Gleichstand (z. B. gleiche Nummer in beiden Reihen)
        $query->orderBy('episodes.sort_key', $direction)
            ->orderBy('episodes.series_id')
            ->orderBy('episodes.id');
    }
}
