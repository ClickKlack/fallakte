<?php

namespace App\Services;

use App\Enums\EpisodeStatus;
use App\Import\ParserRegistry;
use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Kennzahlen für das Statistik-Dashboard.
 * Grundregel: Angekündigte Folgen zählen nirgends mit (Spec 4.3).
 * Die Auswertung erfolgt in PHP – bei einigen tausend Folgen schnell genug und datenbankneutral.
 */
class Statistics
{
    /** @var Collection<int, Episode>|null ausgestrahlte Folgen (series_id, team_id, first_aired, seen, seen_at) */
    private ?Collection $aired = null;

    public function __construct(private readonly ParserRegistry $parsers) {}

    /**
     * Fortschritt je Reihe und gesamt.
     *
     * @return array{series: list<array{slug: string, name: string, aired: int, seen: int}>, total: array{aired: int, seen: int}}
     */
    public function totals(): array
    {
        $aired = $this->aired();

        $series = $this->series()->map(fn (Series $s) => [
            'slug' => $s->slug,
            'name' => $s->name,
            'aired' => $aired->where('series_id', $s->id)->count(),
            'seen' => $aired->where('series_id', $s->id)->where('seen', true)->count(),
        ])->values()->all();

        return [
            'series' => $series,
            'total' => [
                'aired' => array_sum(array_column($series, 'aired')),
                'seen' => array_sum(array_column($series, 'seen')),
            ],
        ];
    }

    /**
     * Ausgestrahlte und gesehene Folgen je Ausstrahlungsjahr, lückenlos vom ersten bis zum letzten Jahr.
     *
     * @return list<array{year: int, aired: int, seen: int}>
     */
    public function byAirYear(?string $seriesSlug = null): array
    {
        $episodes = $this->aired($seriesSlug)->filter(fn (Episode $e) => $e->first_aired !== null);

        if ($episodes->isEmpty()) {
            return [];
        }

        $grouped = $episodes->groupBy(fn (Episode $e) => $e->first_aired->year);

        return collect(range($grouped->keys()->min(), $grouped->keys()->max()))
            ->map(fn (int $year) => [
                'year' => $year,
                'aired' => $grouped->get($year)?->count() ?? 0,
                'seen' => $grouped->get($year)?->where('seen', true)->count() ?? 0,
            ])->all();
    }

    /**
     * Gesehene Folgen je Jahr laut „gesehen am“ – nur Einträge mit Datum, lückenlos.
     *
     * @return list<array{year: int, seen: int}>
     */
    public function seenPerYear(?string $seriesSlug = null): array
    {
        $grouped = $this->aired($seriesSlug)
            ->filter(fn (Episode $e) => $e->seen && $e->seen_at !== null)
            ->groupBy(fn (Episode $e) => $e->seen_at->year);

        if ($grouped->isEmpty()) {
            return [];
        }

        return collect(range($grouped->keys()->min(), $grouped->keys()->max()))
            ->map(fn (int $year) => ['year' => $year, 'seen' => $grouped->get($year)?->count() ?? 0])
            ->all();
    }

    /** Gesehene Folgen ohne Datum (fehlen in seenPerYear – wird als Hinweis angezeigt). */
    public function seenWithoutDate(?string $seriesSlug = null): int
    {
        return $this->aired($seriesSlug)->filter(fn (Episode $e) => $e->seen && $e->seen_at === null)->count();
    }

    /**
     * Teams mit dem höchsten Fortschritt (ab einer Mindestzahl ausgestrahlter Folgen, damit
     * „1 von 1“ nicht die Liste anführt) und Teams mit den meisten ungesehenen Folgen.
     *
     * @return array{progress: list<array<string, mixed>>, unseen: list<array<string, mixed>>}
     */
    public function teams(int $limit = 5, int $minAired = 5, ?string $seriesSlug = null): array
    {
        $stats = $this->aired($seriesSlug)
            ->whereNotNull('team_id')
            ->groupBy('team_id')
            ->map(fn (Collection $episodes, int $teamId) => [
                'id' => $teamId,
                'aired' => $episodes->count(),
                'seen' => $episodes->where('seen', true)->count(),
            ]);

        $teams = Team::query()->with('series:id,slug,name')->findMany($stats->keys())->keyBy('id');

        $rows = $stats->map(fn (array $s) => [
            ...$s,
            'name' => $teams[$s['id']]->name,
            'series' => ['slug' => $teams[$s['id']]->series->slug, 'name' => $teams[$s['id']]->series->name],
            'unseen' => $s['aired'] - $s['seen'],
            'ratio' => $s['seen'] / $s['aired'],
        ]);

        $progress = $rows->filter(fn ($r) => $r['aired'] >= $minAired && $r['seen'] > 0)
            ->sortBy([['ratio', 'desc'], ['aired', 'desc'], ['name', 'asc']])
            ->take($limit);

        $unseen = $rows->filter(fn ($r) => $r['unseen'] > 0)
            ->sortBy([['unseen', 'desc'], ['name', 'asc']])
            ->take($limit);

        $strip = fn (Collection $c) => $c->map(fn ($r) => collect($r)->except('ratio')->all())->values()->all();

        return ['progress' => $strip($progress), 'unseen' => $strip($unseen)];
    }

    /**
     * Letzter Importlauf je Reihe.
     *
     * @return list<array<string, mixed>>
     */
    public function lastImports(): array
    {
        return $this->series()->map(function (Series $series) {
            $run = ImportRun::query()->where('series_id', $series->id)->latest('started_at')->latest('id')->first();

            return [
                'series' => ['slug' => $series->slug, 'name' => $series->name],
                'run' => $run ? [
                    'started_at' => $run->started_at->toIso8601String(),
                    'finished_at' => $run->finished_at?->toIso8601String(),
                    'created' => $run->created_count,
                    'updated' => $run->updated_count,
                    'skipped' => $run->skipped_count,
                    'errors' => $run->error_count,
                    'error_text' => $run->errors,
                ] : null,
            ];
        })->values()->all();
    }

    /** @return Collection<int, Series> */
    private function series(): Collection
    {
        return Series::query()->whereIn('slug', $this->parsers->slugs())->orderBy('id')->get();
    }

    /** @return Collection<int, Episode> */
    private function aired(?string $seriesSlug = null): Collection
    {
        $this->aired ??= Episode::query()
            ->where('status', EpisodeStatus::Aired)
            ->get(['id', 'series_id', 'team_id', 'first_aired', 'seen', 'seen_at']);

        if ($seriesSlug === null) {
            return $this->aired;
        }

        $seriesId = $this->series()->firstWhere('slug', $seriesSlug)?->id;

        return $this->aired->where('series_id', $seriesId);
    }
}
