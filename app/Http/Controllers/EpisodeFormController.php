<?php

namespace App\Http\Controllers;

use App\Http\Requests\EpisodeFormRequest;
use App\Import\ParserRegistry;
use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use App\Support\BroadcasterNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Folge bearbeiten, manuell anlegen und den Schutz einzelner Felder zurücksetzen.
 */
class EpisodeFormController extends Controller
{
    public function create(Request $request, ParserRegistry $parsers): Response
    {
        return Inertia::render('Episodes/Edit', [
            'episode' => null,
            'options' => $this->options($parsers),
            'returnTo' => $this->returnTo($request),
        ]);
    }

    public function store(EpisodeFormRequest $request): RedirectResponse
    {
        $episode = new Episode($request->episodeData());
        $episode->series_id = $request->integer('series_id');
        // Manuell angelegte Folgen sind vollständig vor dem Import geschützt.
        $episode->manual_fields = Episode::PROTECTABLE_FIELDS;
        $episode->save();

        return $this->backToList($request)->with('success', __('episodes.created', ['title' => $episode->title]));
    }

    public function edit(Request $request, Episode $episode, ParserRegistry $parsers): Response
    {
        $episode->load(['series:id,name,slug', 'team:id,name']);

        return Inertia::render('Episodes/Edit', [
            'episode' => [
                'id' => $episode->id,
                'series' => ['id' => $episode->series->id, 'slug' => $episode->series->slug, 'name' => $episode->series->name],
                'number' => $episode->number,
                'display_number' => $episode->display_number,
                'title' => $episode->title,
                'first_aired' => $episode->first_aired?->toDateString(),
                'broadcaster' => $episode->broadcaster,
                'team_id' => $episode->team_id,
                'seen' => $episode->seen,
                'seen_at' => $episode->seen_at?->toDateString(),
                'status' => $episode->status->value,
                'manual_fields' => $episode->manual_fields,
                'from_wikipedia' => $episode->wikipedia_ref !== null,
            ],
            'options' => $this->options($parsers),
            'returnTo' => $this->returnTo($request),
        ]);
    }

    /**
     * Speichert Änderungen; jedes tatsächlich geänderte schützbare Feld wird in manual_fields vermerkt.
     */
    public function update(EpisodeFormRequest $request, Episode $episode): RedirectResponse
    {
        $episode->fill($request->episodeData());

        $changed = array_filter(Episode::PROTECTABLE_FIELDS, fn (string $field) => $episode->isDirty($field));
        $episode->manual_fields = array_values(array_unique([...$episode->manual_fields, ...$changed]));
        $episode->save();

        return $this->backToList($request)->with('success', __('episodes.saved', ['title' => $episode->title]));
    }

    /**
     * Hebt den Schutz eines Feldes auf („wieder von Wikipedia übernehmen“); der Wert kommt beim nächsten Import.
     */
    public function resetProtection(Episode $episode, string $field): RedirectResponse
    {
        abort_unless(in_array($field, Episode::PROTECTABLE_FIELDS, true), 404);

        $episode->manual_fields = array_values(array_diff($episode->manual_fields, [$field]));
        $episode->save();

        return back()->with('success', __('episodes.protection_reset'));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(ParserRegistry $parsers): array
    {
        $series = Series::query()->whereIn('slug', $parsers->slugs())->orderBy('id')->get(['id', 'name', 'slug']);

        return [
            'series' => $series->map(fn (Series $s) => ['id' => $s->id, 'slug' => $s->slug, 'name' => $s->name]),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name', 'series_id']),
            'broadcasters' => BroadcasterNames::distinct(
                Episode::query()->whereNotNull('broadcaster')->distinct()->pluck('broadcaster'),
            ),
        ];
    }

    /** Query-String der Liste (Filter, Sortierung, Seite), zu der nach dem Speichern zurückgekehrt wird. */
    private function returnTo(Request $request): string
    {
        $value = (string) $request->query('return_to', '');

        return str_starts_with($value, '?') && strlen($value) <= 1000 ? $value : '';
    }

    private function backToList(Request $request): RedirectResponse
    {
        $value = (string) $request->input('return_to', '');
        $query = str_starts_with($value, '?') && strlen($value) <= 1000 ? $value : '';

        // Nur Query-String an die eigene Liste anhängen – keine fremden Weiterleitungsziele
        return redirect()->to(route('episodes.index').$query);
    }
}
