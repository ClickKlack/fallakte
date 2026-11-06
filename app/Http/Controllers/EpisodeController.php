<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEpisodeSeenRequest;
use App\Http\Resources\EpisodeListResource;
use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use App\Queries\EpisodeFilters;
use App\Queries\EpisodeListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EpisodeController extends Controller
{
    /**
     * Folgenliste (Startseite) mit Filtern, Sortierung und Paginierung.
     */
    public function index(Request $request, EpisodeListQuery $query): Response
    {
        // Angekündigte Folgen, deren Datum erreicht ist, werden beim Anzeigen freigeschaltet.
        Episode::promoteAiredAnnouncements();

        $series = Series::query()->orderBy('id')->get(['id', 'name', 'slug']);
        $filters = EpisodeFilters::fromArray($request->query(), $series->pluck('slug')->all());

        return Inertia::render('Episodes/Index', [
            'episodes' => EpisodeListResource::collection($query->paginate($filters)),
            'filters' => $filters->toArray(),
            'options' => [
                'series' => $series->map(fn (Series $s) => ['slug' => $s->slug, 'name' => $s->name]),
                'teams' => Team::query()
                    ->join('series', 'series.id', '=', 'teams.series_id')
                    ->orderBy('teams.name')
                    ->get(['teams.id', 'teams.name', 'series.slug as series_slug']),
                'statuses' => EpisodeFilters::STATUSES,
                'sorts' => EpisodeFilters::SORTS,
                'per_page' => EpisodeFilters::PER_PAGE,
                'years' => $this->yearRange(),
            ],
        ]);
    }

    /**
     * Setzt oder entfernt den Haken „gesehen“. Entfernen löscht auch das Datum.
     */
    public function updateSeen(UpdateEpisodeSeenRequest $request, Episode $episode): RedirectResponse|JsonResponse
    {
        $seen = $request->boolean('seen');

        $episode->update([
            'seen' => $seen,
            'seen_at' => $seen ? $request->validated('seen_at') : null,
        ]);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'seen' => $episode->seen,
                'seen_at' => $episode->seen_at?->toDateString(),
            ]);
        }

        return back();
    }

    /**
     * Erstes und letztes Ausstrahlungsjahr für die Jahresfilter.
     *
     * @return array{min: int, max: int}
     */
    private function yearRange(): array
    {
        $min = Episode::query()->min('first_aired');
        $max = Episode::query()->max('first_aired');

        return [
            'min' => $min ? (int) substr((string) $min, 0, 4) : EpisodeFilters::MIN_YEAR,
            'max' => $max ? (int) substr((string) $max, 0, 4) : (int) date('Y'),
        ];
    }
}
