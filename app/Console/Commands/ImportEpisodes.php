<?php

namespace App\Console\Commands;

use App\Import\EpisodeImporter;
use App\Import\ImportLock;
use App\Import\ImportResult;
use App\Import\ParserRegistry;
use App\Models\Series;
use Illuminate\Console\Command;

/**
 * Importiert die Episodenlisten aus Wikipedia.
 */
class ImportEpisodes extends Command
{
    protected $signature = 'episodes:import
        {series? : Slug der Reihe (tatort, polizeiruf-110); ohne Angabe beide}
        {--dry-run : Nur anzeigen, was sich ändern würde, nichts speichern}';

    protected $description = 'Importiert bzw. aktualisiert die Folgen aus der deutschsprachigen Wikipedia';

    public function handle(EpisodeImporter $importer, ParserRegistry $parsers): int
    {
        $lock = ImportLock::make();

        if (! $lock->get()) {
            $this->error('Es läuft bereits ein Import. Bitte später erneut versuchen.');

            return self::FAILURE;
        }

        try {
            return $this->importAll($importer, $parsers);
        } finally {
            $lock->release();
        }
    }

    private function importAll(EpisodeImporter $importer, ParserRegistry $parsers): int
    {
        $slugs = $this->argument('series') !== null ? [$this->argument('series')] : $parsers->slugs();
        $dryRun = (bool) $this->option('dry-run');
        $success = true;

        foreach ($slugs as $slug) {
            if (! in_array($slug, $parsers->slugs(), true)) {
                $this->error("Unbekannte Reihe „{$slug}“. Erlaubt: ".implode(', ', $parsers->slugs()));

                return self::FAILURE;
            }

            $series = Series::where('slug', $slug)->first();
            if ($series === null) {
                $this->error("Reihe „{$slug}“ fehlt in der Datenbank. Bitte zuerst `php artisan db:seed` ausführen.");

                return self::FAILURE;
            }

            $result = $importer->import($series, $parsers->for($slug), $dryRun);
            $this->report($result);
            $success = $success && $result->errors === [];
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }

    private function report(ImportResult $result): void
    {
        $this->newLine();
        $this->info(($result->dryRun ? '[Probelauf] ' : '').$result->seriesName);

        if ($result->dryRun || $this->output->isVerbose()) {
            foreach ($result->changes as $change) {
                $this->line("  {$change}");
            }
        }

        foreach ($result->notices as $notice) {
            $this->comment("  Hinweis: {$notice}");
        }

        foreach ($result->errors as $error) {
            $this->error("  Fehler: {$error}");
        }

        $this->table(
            ['Neu', 'Aktualisiert', 'Übersprungen (geschützt)', 'Neue Teams', 'Jetzt ausgestrahlt', 'Fehler'],
            [[$result->created, $result->updated, $result->skipped, $result->teamsCreated, $result->promoted, count($result->errors)]],
        );

        if ($result->dryRun) {
            $this->comment('Probelauf: Es wurde nichts gespeichert.');
        }
    }
}
