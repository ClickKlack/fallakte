<?php

namespace App\Console;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

/**
 * Plant den automatischen Wikipedia-Import ein.
 * Zeitpunkt und Zeitzone kommen aus der Konfiguration (IMPORT_SCHEDULE, IMPORT_TIMEZONE).
 */
class ImportSchedule
{
    public static function register(Schedule $schedule): void
    {
        $expression = trim((string) config('services.wikipedia.import_schedule'));

        if ($expression === '') {
            return;
        }

        // Ein ungültiger Ausdruck darf andere Artisan-Befehle (z. B. Migrationen) nicht blockieren
        if (! CronExpression::isValidExpression($expression)) {
            Log::warning('Ungültiger Cron-Ausdruck in IMPORT_SCHEDULE – automatischer Import ist deaktiviert.', [
                'expression' => $expression,
            ]);

            return;
        }

        $schedule->command('episodes:import')
            ->cron($expression)
            ->timezone(config('services.wikipedia.import_timezone') ?: config('app.timezone'))
            ->description('Automatischer Wikipedia-Import');
    }
}
