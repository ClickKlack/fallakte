<?php

namespace App\Http\Controllers;

use App\Actions\MergeTeams;
use App\Enums\EpisodeStatus;
use App\Import\ParserRegistry;
use App\Models\Series;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Team-Übersicht mit Fortschritt, Bearbeiten und Zusammenführen.
 */
class TeamController extends Controller
{
    public function index(ParserRegistry $parsers): Response
    {
        $aired = fn (Builder $q) => $q->where('status', EpisodeStatus::Aired);

        $teams = Team::query()
            ->with(['series:id,name,slug', 'aliases:id,team_id,raw_value'])
            ->withCount([
                'episodes as episodes_count',
                'episodes as aired_count' => $aired,
                'episodes as seen_count' => fn (Builder $q) => $aired($q)->where('seen', true),
            ])
            ->withMin(['episodes as first_aired' => $aired], 'first_aired')
            ->withMax(['episodes as last_aired' => $aired], 'first_aired')
            ->orderBy('name')
            ->get();

        return Inertia::render('Teams/Index', [
            'teams' => $teams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'city' => $team->city,
                'investigators' => $team->investigators,
                'series' => ['id' => $team->series->id, 'slug' => $team->series->slug, 'name' => $team->series->name],
                'episodes_count' => $team->episodes_count,
                'aired_count' => $team->aired_count,
                'seen_count' => $team->seen_count,
                'first_year' => $team->first_aired ? (int) substr((string) $team->first_aired, 0, 4) : null,
                'last_year' => $team->last_aired ? (int) substr((string) $team->last_aired, 0, 4) : null,
                'aliases' => $team->aliases->pluck('raw_value')->sort()->values(),
            ]),
            'series' => Series::query()->whereIn('slug', $parsers->slugs())->orderBy('id')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'investigators' => ['nullable', 'string', 'max:255'],
        ], attributes: __('teams.attributes'));

        $team->update(array_map(fn ($v) => is_string($v) ? (trim($v) ?: null) : $v, $data));

        return back()->with('success', __('teams.saved', ['name' => $team->name]));
    }

    public function merge(Request $request, Team $team, MergeTeams $merge): RedirectResponse
    {
        $request->validate([
            'target_id' => ['required', 'integer', Rule::exists(Team::class, 'id')],
        ], attributes: __('teams.attributes'));

        $target = Team::findOrFail($request->integer('target_id'));

        try {
            $result = $merge($team, $target);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['target_id' => $e->getMessage()]);
        }

        return back()->with('success', trans_choice('teams.merged', $result['episodes'], [
            'source' => $team->name,
            'target' => $target->name,
            'episodes' => $result['episodes'],
        ]));
    }
}
