<?php

namespace App\Import;

use App\Import\Wikipedia\EpisodeListParser;
use App\Import\Wikipedia\ParsedEpisode;
use App\Import\Wikipedia\WikipediaClient;
use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Importiert die Episodenliste einer Reihe aus Wikipedia.
 *
 * - Abgleich über (series_id, number), bei Filmen ohne Nummer bzw. als Rückfall über wikipedia_ref
 * - Manuell geänderte Felder (manual_fields) werden nie überschrieben
 * - seen und seen_at werden nie angefasst
 * - Im Probelauf (dry run) wird alles in einer Transaktion ausgeführt und zurückgerollt
 */
class EpisodeImporter
{
    public function __construct(
        private readonly WikipediaClient $client,
        private readonly TeamResolver $teams,
    ) {}

    public function import(Series $series, EpisodeListParser $parser, bool $dryRun = false): ImportResult
    {
        $result = new ImportResult($series->name, $dryRun);
        $this->teams->reset();
        $run = $dryRun ? null : ImportRun::create(['series_id' => $series->id, 'started_at' => now()]);

        try {
            $page = $this->client->fetchWikitext($parser->pageTitle());
            $parsed = $parser->parse($page['wikitext']);

            $result->errors = $parsed->errors;
            $result->notices = $parsed->skipped;

            if ($parsed->episodes === []) {
                $result->errors[] = 'Keine Folgen gefunden – Import abgebrochen, es wurde nichts geändert.';
            } else {
                $this->apply($series, $parsed->episodes, $result, $dryRun);
            }
        } catch (Throwable $e) {
            $result->errors[] = $e->getMessage();
        }

        $run?->update([
            'finished_at' => now(),
            'created_count' => $result->created,
            'updated_count' => $result->updated,
            'skipped_count' => $result->skipped,
            'error_count' => count($result->errors),
            'errors' => $result->errors === [] ? null : implode("\n", $result->errors),
        ]);

        return $result;
    }

    /**
     * @param  list<ParsedEpisode>  $parsedEpisodes
     */
    private function apply(Series $series, array $parsedEpisodes, ImportResult $result, bool $dryRun): void
    {
        DB::beginTransaction();

        try {
            $teamsBefore = $this->teams->createdTeams;
            $existing = Episode::query()->where('series_id', $series->id)->get();
            $indexAfter = [];

            foreach ($parsedEpisodes as $parsed) {
                $sortKey = $parsed->number !== null
                    ? $parsed->number * Episode::SORT_STEP
                    : $parsed->positionAfter * Episode::SORT_STEP + ($indexAfter[$parsed->positionAfter] = ($indexAfter[$parsed->positionAfter] ?? 0) + 1);

                if ($parsed->number === null && $parsed->wikipediaRef === null) {
                    $result->errors[] = "„{$parsed->title}“: weder Folgennummer noch Artikel-Link, nicht zuordenbar";

                    continue;
                }

                try {
                    DB::transaction(fn () => $this->applyEpisode($series, $parsed, $sortKey, $existing, $result));
                } catch (Throwable $e) {
                    $result->errors[] = "„{$parsed->title}“: ".$e->getMessage();
                }
            }

            $result->promoted = Episode::promoteAiredAnnouncements();
            $result->teamsCreated = $this->teams->createdTeams - $teamsBefore;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $dryRun ? DB::rollBack() : DB::commit();
    }

    /**
     * @param  Collection<int, Episode>  $existing
     */
    private function applyEpisode(Series $series, ParsedEpisode $parsed, int $sortKey, Collection $existing, ImportResult $result): void
    {
        $episode = $this->findExisting($existing, $parsed);
        $label = ($parsed->number ?? 'ohne Nr.').' „'.$parsed->title.'“';

        $incoming = [
            'number' => $parsed->number,
            'title' => $parsed->title,
            'first_aired' => $parsed->firstAired?->toDateString(),
            'broadcaster' => $parsed->broadcaster,
            'team_id' => $this->teams->resolve($series, $parsed->teamRaw),
        ];

        if ($episode === null) {
            $episode = Episode::create([
                'series_id' => $series->id,
                ...$incoming,
                'sort_key' => $sortKey,
                'wikipedia_ref' => $parsed->wikipediaRef,
            ]);
            $existing->push($episode);
            $result->created++;
            $result->changes[] = "neu: {$label}";

            return;
        }

        $changed = [];
        $protectedDiffers = false;

        foreach ($incoming as $field => $value) {
            $current = $field === 'first_aired' ? $episode->first_aired?->toDateString() : $episode->{$field};

            if ($current === $value) {
                continue;
            }

            if ($episode->isProtected($field)) {
                $protectedDiffers = true;

                continue;
            }

            $episode->{$field} = $value;
            $changed[] = $field;
        }

        // Nicht schützbare, rein technische Felder
        if ($parsed->wikipediaRef !== null && $episode->wikipedia_ref !== $parsed->wikipediaRef) {
            $episode->wikipedia_ref = $parsed->wikipediaRef;
            $changed[] = 'wikipedia_ref';
        }
        if ($parsed->number === null && $episode->number === null && $episode->sort_key !== $sortKey) {
            $episode->sort_key = $sortKey;
            $changed[] = 'sort_key';
        }

        if ($protectedDiffers) {
            $result->skipped++;
            $result->changes[] = "geschützt (manuelle Korrektur beibehalten): {$label}";
        }

        if ($changed !== []) {
            $episode->save();
            $result->updated++;
            $result->changes[] = "aktualisiert ({$this->describe($changed)}): {$label}";
        }
    }

    /**
     * Sucht die passende vorhandene Folge: zuerst über die Nummer, sonst über den Artikel-Link.
     * Der Artikel-Link greift nur bei Folgen ohne Nummer oder mit manuell geänderter Nummer –
     * mehrere Folgen können auf denselben Artikel verlinken (z. B. beide Teile einer Doppelfolge).
     *
     * @param  Collection<int, Episode>  $existing
     */
    private function findExisting(Collection $existing, ParsedEpisode $parsed): ?Episode
    {
        if ($parsed->number !== null) {
            $byNumber = $existing->first(fn (Episode $e) => $e->number === $parsed->number);
            if ($byNumber !== null) {
                return $byNumber;
            }
        }

        if ($parsed->wikipediaRef === null) {
            return null;
        }

        return $existing->first(fn (Episode $e) => $e->wikipedia_ref === $parsed->wikipediaRef
            && ($e->number === null || $e->isProtected('number')));
    }

    /** @param list<string> $fields */
    private function describe(array $fields): string
    {
        $names = [
            'number' => 'Nr.', 'title' => 'Titel', 'first_aired' => 'Datum', 'broadcaster' => 'Sender', 'team_id' => 'Team',
            'wikipedia_ref' => 'Quelle', 'sort_key' => 'Position',
        ];

        return implode(', ', array_map(fn ($f) => $names[$f] ?? $f, $fields));
    }
}
