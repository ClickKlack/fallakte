<?php

namespace App\Queries;

/**
 * Filter, Sortierung und Seitengröße der Folgenliste, gelesen aus dem URL-Query.
 * Ungültige Werte werden ignoriert (statt einer Fehlermeldung), damit manipulierte
 * oder veraltete Links immer eine sinnvolle Liste zeigen.
 */
final readonly class EpisodeFilters
{
    public const STATUSES = ['all', 'seen', 'unseen', 'announced'];

    public const SORTS = ['number', 'date', 'title', 'team'];

    public const PER_PAGE = [25, 50, 100];

    public const MIN_YEAR = 1970;

    public const MAX_YEAR = 2100;

    /**
     * @param  list<string>  $allowedSeries  gültige Serien-Slugs
     */
    public function __construct(
        public ?string $series = null,
        public ?int $team = null,
        public ?string $broadcaster = null,
        public string $status = 'all',
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $search = null,
        public string $sort = 'number',
        public string $direction = 'asc',
        public int $perPage = 50,
    ) {}

    /**
     * @param  array<string, mixed>  $query  Rohwerte aus dem URL-Query
     * @param  list<string>  $allowedSeries  gültige Serien-Slugs
     */
    public static function fromArray(array $query, array $allowedSeries): self
    {
        $string = fn (string $key) => is_string($query[$key] ?? null) ? trim($query[$key]) : null;
        $int = fn (string $key) => is_string($query[$key] ?? null) && ctype_digit($query[$key]) ? (int) $query[$key] : null;
        $year = fn (string $key) => ($y = $int($key)) !== null && $y >= self::MIN_YEAR && $y <= self::MAX_YEAR ? $y : null;

        $yearFrom = $year('year_from');
        $yearTo = $year('year_to');
        if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
            [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
        }

        $broadcaster = $string('broadcaster');
        $broadcaster = $broadcaster === null || $broadcaster === '' ? null : mb_substr($broadcaster, 0, 50);

        $search = $string('q');
        $search = $search === null || $search === '' ? null : mb_substr($search, 0, 100);

        return new self(
            series: in_array($string('series'), $allowedSeries, true) ? $string('series') : null,
            team: ($team = $int('team')) !== null && $team > 0 ? $team : null,
            broadcaster: $broadcaster,
            status: in_array($string('status'), self::STATUSES, true) ? $string('status') : 'all',
            yearFrom: $yearFrom,
            yearTo: $yearTo,
            search: $search,
            sort: in_array($string('sort'), self::SORTS, true) ? $string('sort') : 'number',
            direction: $string('direction') === 'desc' ? 'desc' : 'asc',
            perPage: in_array($int('per_page'), self::PER_PAGE, true) ? $int('per_page') : 50,
        );
    }

    /**
     * Für das Frontend (Zustand der Filterleiste) und für Paginierungslinks.
     *
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'series' => $this->series,
            'team' => $this->team,
            'broadcaster' => $this->broadcaster,
            'status' => $this->status,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'q' => $this->search,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ];
    }
}
