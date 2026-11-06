<?php

namespace App\Http\Controllers;

use App\Import\ParserRegistry;
use App\Models\ImportRun;
use App\Models\Series;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Über-Seite: Zweck, Widmung, Datenquelle mit Quellenangabe, Datenstand, Technik und Version.
 * Bewusst ohne Hosting-Informationen, Pfade oder persönliche Kontaktdaten.
 */
class AboutController extends Controller
{
    private const REPOSITORY = 'https://github.com/ClickKlack/fallakte';

    public function __invoke(ParserRegistry $parsers): Response
    {
        $series = Series::query()
            ->whereIn('slug', $parsers->slugs())
            ->withCount(['episodes', 'teams'])
            ->orderBy('id')
            ->get()
            ->map(function (Series $series) use ($parsers) {
                $lastRun = ImportRun::query()->where('series_id', $series->id)
                    ->whereNotNull('finished_at')
                    ->latest('finished_at')
                    ->first();

                return [
                    'slug' => $series->slug,
                    'name' => $series->name,
                    'episodes' => $series->episodes_count,
                    'teams' => $series->teams_count,
                    'last_import' => $lastRun?->finished_at?->toIso8601String(),
                    'source_title' => $parsers->for($series->slug)->pageTitle(),
                    'source_url' => 'https://de.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $parsers->for($series->slug)->pageTitle())),
                ];
            });

        return Inertia::render('About', [
            'version' => config('app.version'),
            'laravel' => app()->version(),
            'repository' => self::REPOSITORY,
            'series' => $series,
        ]);
    }
}
