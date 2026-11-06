<?php

namespace App\Http\Controllers;

use App\Import\ParserRegistry;
use App\Models\Episode;
use App\Services\Statistics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Statistik-Dashboard; optional auf eine Reihe eingeschränkt (?series=…).
 */
class StatisticsController extends Controller
{
    public function __invoke(Request $request, Statistics $statistics, ParserRegistry $parsers): Response
    {
        // Fällige angekündigte Folgen zählen ab ihrem Sendetag mit
        Episode::promoteAiredAnnouncements();

        $series = in_array($request->query('series'), $parsers->slugs(), true) ? $request->query('series') : null;

        return Inertia::render('Dashboard', [
            'series' => $series,
            'totals' => $statistics->totals(),
            'byAirYear' => $statistics->byAirYear($series),
            'seenPerYear' => $statistics->seenPerYear($series),
            'seenWithoutDate' => $statistics->seenWithoutDate($series),
            'teams' => $statistics->teams(seriesSlug: $series),
            'imports' => $statistics->lastImports(),
        ]);
    }
}
