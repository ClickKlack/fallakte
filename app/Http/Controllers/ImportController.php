<?php

namespace App\Http\Controllers;

use App\Import\EpisodeImporter;
use App\Import\ImportLock;
use App\Import\ParserRegistry;
use App\Models\Series;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Manueller Import beider Reihen über die App (Knopf auf der Über-Seite).
 * Läuft direkt im Webaufruf; gesperrt gegen parallele Läufe und gebremst aus Rücksicht auf Wikipedia.
 */
class ImportController extends Controller
{
    /** Mindestabstand zwischen zwei Importen über die App. */
    public const COOLDOWN_SECONDS = 300;

    private const RATE_KEY = 'fallakte:manual-import';

    public function __invoke(EpisodeImporter $importer, ParserRegistry $parsers): RedirectResponse
    {
        if (RateLimiter::tooManyAttempts(self::RATE_KEY, 1)) {
            $minutes = (int) ceil(RateLimiter::availableIn(self::RATE_KEY) / 60);

            return back()->with('error', __('import.cooldown', ['minutes' => $minutes]));
        }

        $lock = ImportLock::make();

        if (! $lock->get()) {
            return back()->with('error', __('import.running'));
        }

        RateLimiter::hit(self::RATE_KEY, self::COOLDOWN_SECONDS);

        // Webspaces brechen lange Aufrufe sonst ggf. nach 30 Sekunden ab
        @set_time_limit(300);

        try {
            $parts = [];
            $errors = 0;

            foreach ($parsers->slugs() as $slug) {
                $series = Series::where('slug', $slug)->first();
                if ($series === null) {
                    continue;
                }

                $result = $importer->import($series, $parsers->for($slug));
                $errors += count($result->errors);
                $parts[] = __('import.series_result', [
                    'series' => $series->name,
                    'created' => $result->created,
                    'updated' => $result->updated,
                    'skipped' => $result->skipped,
                ]);
            }
        } finally {
            $lock->release();
        }

        $message = implode(' ', $parts);

        return $errors > 0
            ? back()->with('error', __('import.finished_with_errors', ['summary' => $message, 'errors' => $errors]))
            : back()->with('success', __('import.finished', ['summary' => $message]));
    }
}
