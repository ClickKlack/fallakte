<?php

namespace App\Backup;

use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Erstellt Backups des Datenbestands.
 * - SQLite: vollständiges, konsistentes Abbild der Datenbank (inkl. Logins), ohne Sitzungen und Cache
 * - JSON und Excel: Reihen, Teams (mit Schreibweisen), Folgen und Importläufe – ohne Logins
 */
class BackupExporter
{
    /** Tabellen, die in der SQLite-Kopie geleert werden (flüchtig bzw. sicherheitsrelevant ohne Nutzen). */
    private const TRANSIENT_TABLES = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function sqliteAvailable(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Konsistente Kopie der SQLite-Datenbank in eine temporäre Datei (VACUUM INTO arbeitet auch
     * während laufender Zugriffe sicher). Rückgabe: Pfad der Kopie – der Aufrufer löscht sie.
     */
    public function sqlite(): string
    {
        if (! $this->sqliteAvailable()) {
            throw new RuntimeException('Das SQLite-Backup ist nur mit einer SQLite-Datenbank möglich.');
        }

        if (DB::transactionLevel() > 0) {
            throw new RuntimeException('Das SQLite-Backup kann nicht innerhalb einer offenen Transaktion erstellt werden.');
        }

        $path = $this->temporaryPath('sqlite');
        @unlink($path); // VACUUM INTO verlangt eine noch nicht vorhandene Datei
        DB::statement('VACUUM INTO ?', [$path]);

        $copy = new PDO('sqlite:'.$path);
        $copy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $existing = $copy->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
        foreach (array_intersect(self::TRANSIENT_TABLES, $existing) as $table) {
            $copy->exec("DELETE FROM \"{$table}\"");
        }
        $copy->exec('VACUUM');
        $copy = null;

        return $path;
    }

    /**
     * Alle Fallakte-Daten als Array (Grundlage für JSON und Excel) – ohne Logins.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $series = Series::query()->orderBy('id')->get();
        $seriesSlug = $series->pluck('slug', 'id');

        return [
            'app' => config('app.name'),
            'version' => config('app.version'),
            'exported_at' => now()->toIso8601String(),
            'counts' => [
                'series' => $series->count(),
                'teams' => Team::count(),
                'episodes' => Episode::count(),
                'import_runs' => ImportRun::count(),
            ],
            'series' => $series->map(fn (Series $s) => ['id' => $s->id, 'slug' => $s->slug, 'name' => $s->name])->all(),
            'teams' => Team::query()->with('aliases:id,team_id,raw_value')->orderBy('series_id')->orderBy('name')->get()
                ->map(fn (Team $t) => [
                    'id' => $t->id,
                    'series' => $seriesSlug[$t->series_id] ?? null,
                    'name' => $t->name,
                    'city' => $t->city,
                    'investigators' => $t->investigators,
                    'aliases' => $t->aliases->pluck('raw_value')->sort()->values()->all(),
                ])->all(),
            'episodes' => Episode::query()->orderBy('series_id')->orderBy('sort_key')->get()
                ->map(fn (Episode $e) => [
                    'id' => $e->id,
                    'series' => $seriesSlug[$e->series_id] ?? null,
                    'number' => $e->number,
                    'display_number' => $e->display_number,
                    'sort_key' => $e->sort_key,
                    'title' => $e->title,
                    'first_aired' => $e->first_aired?->toDateString(),
                    'broadcaster' => $e->broadcaster,
                    'team_id' => $e->team_id,
                    'status' => $e->status->value,
                    'seen' => $e->seen,
                    'seen_at' => $e->seen_at?->toDateString(),
                    'wikipedia_ref' => $e->wikipedia_ref,
                    'manual_fields' => $e->manual_fields,
                ])->all(),
            'import_runs' => ImportRun::query()->orderBy('started_at')->get()
                ->map(fn (ImportRun $r) => [
                    'series' => $seriesSlug[$r->series_id] ?? null,
                    'started_at' => $r->started_at?->toIso8601String(),
                    'finished_at' => $r->finished_at?->toIso8601String(),
                    'created' => $r->created_count,
                    'updated' => $r->updated_count,
                    'skipped' => $r->skipped_count,
                    'errors' => $r->error_count,
                    'error_text' => $r->errors,
                ])->all(),
        ];
    }

    public function json(): string
    {
        return json_encode($this->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** Excel-Datei mit den Blättern Folgen, Teams und Importe. Rückgabe: Pfad – der Aufrufer löscht sie. */
    public function xlsx(): string
    {
        $data = $this->data();
        $seriesName = collect($data['series'])->pluck('name', 'slug');
        $teamName = collect($data['teams'])->pluck('name', 'id');
        $date = fn (?string $value) => $value ? Carbon::parse($value)->startOfDay() : null;
        $fields = __('backup.fields');

        $episodes = array_map(fn (array $e) => [
            $seriesName[$e['series']] ?? $e['series'],
            $e['number'] ?? $e['display_number'], // Zahl (sortierbar); nur Ersatznummern wie „59a“ als Text
            $e['title'],
            $date($e['first_aired']),
            $e['broadcaster'],
            $teamName[$e['team_id']] ?? null,
            __('backup.status.'.$e['status']),
            $e['seen'] ? __('backup.yes') : __('backup.no'),
            $date($e['seen_at']),
            implode(', ', array_map(fn ($f) => $fields[$f] ?? $f, $e['manual_fields'])),
        ], $data['episodes']);

        $episodeCounts = collect($data['episodes'])->groupBy('team_id');
        $teams = array_map(function (array $t) use ($seriesName, $episodeCounts) {
            $episodes = $episodeCounts->get($t['id'], collect())->where('status', 'aired');

            return [
                $seriesName[$t['series']] ?? $t['series'],
                $t['name'],
                $t['city'],
                $t['investigators'],
                $episodes->count(),
                $episodes->where('seen', true)->count(),
                implode(' · ', $t['aliases']),
            ];
        }, $data['teams']);

        $imports = array_map(fn (array $r) => [
            $seriesName[$r['series']] ?? $r['series'],
            $r['started_at'] ? Carbon::parse($r['started_at'])->timezone(config('app.timezone')) : null,
            $r['created'], $r['updated'], $r['skipped'], $r['errors'], $r['error_text'],
        ], array_reverse($data['import_runs']));

        $path = $this->temporaryPath('xlsx');

        (new XlsxWriter)
            ->addSheet(__('backup.sheets.episodes'), __('backup.columns.episodes'), $episodes, [16, 8, 42, 14, 14, 30, 14, 9, 14, 30])
            ->addSheet(__('backup.sheets.teams'), __('backup.columns.teams'), $teams, [16, 36, 18, 36, 18, 10, 50])
            ->addSheet(__('backup.sheets.imports'), __('backup.columns.imports'), $imports, [16, 18, 8, 13, 22, 8, 60])
            ->save($path);

        return $path;
    }

    /** Dateiname mit Datum, z. B. fallakte-backup-2026-11-07.json */
    public function filename(string $extension): string
    {
        return 'fallakte-backup-'.now()->format('Y-m-d').'.'.$extension;
    }

    private function temporaryPath(string $extension): string
    {
        $directory = storage_path('app/private');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Ordner für Backups konnte nicht angelegt werden: {$directory}");
        }

        // Reste abgebrochener Downloads aufräumen
        foreach (glob($directory.'/backup-*') ?: [] as $old) {
            if (filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }

        return $directory.'/backup-'.bin2hex(random_bytes(8)).'.'.$extension;
    }
}
