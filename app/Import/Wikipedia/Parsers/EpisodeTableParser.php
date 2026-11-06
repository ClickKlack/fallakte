<?php

namespace App\Import\Wikipedia\Parsers;

use App\Import\Wikipedia\EpisodeListParser;
use App\Import\Wikipedia\GermanDate;
use App\Import\Wikipedia\ParsedEpisode;
use App\Import\Wikipedia\ParseResult;
use App\Import\Wikipedia\TeamName;
use App\Import\Wikipedia\Wikitext;
use App\Import\Wikipedia\WikitextTable;

/**
 * Basis für Episodenlisten, deren Folgen in Wikitabellen je Abschnitt stehen.
 * Spalten werden über ihre Überschrift gefunden, nicht über die Position.
 */
abstract class EpisodeTableParser implements EpisodeListParser
{
    /**
     * Abschnitte, deren Tabellen gelesen werden: Überschrift => true, wenn es sich um angekündigte Folgen handelt.
     * Alle anderen Abschnitte werden ignoriert.
     *
     * @return array<string, bool>
     */
    abstract protected function sections(): array;

    public function parse(string $wikitext): ParseResult
    {
        $episodes = [];
        $skipped = [];
        $errors = [];
        $seenNumbers = [];
        $foundSections = [];

        foreach (WikitextTable::extractAll($wikitext) as $table) {
            if ($table->section === null || ! array_key_exists($table->section, $this->sections())) {
                continue;
            }

            $announced = $this->sections()[$table->section];
            $foundSections[$table->section] = true;

            $columns = [
                'number' => $table->columnIndex('/^(Folge|Nr\.?)$/u'),
                'title' => $table->columnIndex('/^Titel$/u'),
                'date' => $table->columnIndex('/Erstausstr/u'),
                'team' => $table->columnIndex('/^Ermittler$/u'),
            ];

            if (in_array(null, $columns, true)) {
                $missing = implode(', ', array_keys(array_filter($columns, fn ($i) => $i === null)));
                $errors[] = "Abschnitt „{$table->section}“: Spalte(n) nicht gefunden: {$missing}";

                continue;
            }

            $previousNumber = null;

            foreach ($table->rows as $cells) {
                if (count($cells) !== count($table->headers)) {
                    $errors[] = sprintf(
                        'Abschnitt „%s“: Zeile mit %d statt %d Zellen übersprungen: %s',
                        $table->section, count($cells), count($table->headers), mb_substr(Wikitext::toPlainText(implode(' | ', $cells)), 0, 120),
                    );

                    continue;
                }

                $numberText = Wikitext::toPlainText($cells[$columns['number']]);
                $title = Wikitext::toPlainText(Wikitext::splitLines($cells[$columns['title']])[0]);
                $label = $title !== '' ? "„{$title}“" : 'Zeile ohne Titel';

                if ($title === '') {
                    $errors[] = "Abschnitt „{$table->section}“: Folge {$numberText} ohne Titel";

                    continue;
                }

                $number = null;
                if (preg_match('/^\d+$/', $numberText)) {
                    $number = (int) $numberText;
                } elseif (! in_array($numberText, ['', '–', '-', '—'], true)) {
                    $errors[] = "{$label}: Folgennummer „{$numberText}“ nicht lesbar";

                    continue;
                } elseif ($announced) {
                    $skipped[] = "{$label}: angekündigt, noch ohne Folgennummer";

                    continue;
                }

                if ($number !== null) {
                    if (isset($seenNumbers[$number])) {
                        $errors[] = "{$label}: Folgennummer {$number} doppelt";

                        continue;
                    }
                    $seenNumbers[$number] = true;
                }

                $dateCell = $cells[$columns['date']];
                if (GermanDate::isUnreadable($dateCell)) {
                    $errors[] = "{$label}: Datum „".Wikitext::toPlainText($dateCell).'“ nicht lesbar, ohne Datum übernommen';
                }

                $episodes[] = new ParsedEpisode(
                    number: $number,
                    title: $title,
                    firstAired: GermanDate::parse($dateCell),
                    teamRaw: TeamName::fromCell($cells[$columns['team']]),
                    wikipediaRef: Wikitext::firstLinkTarget($cells[$columns['title']]),
                    fromAnnouncedSection: $announced,
                    positionAfter: $number === null ? $previousNumber : null,
                );

                if ($number !== null) {
                    $previousNumber = $number;
                }
            }
        }

        foreach (array_keys($this->sections()) as $section) {
            if (! isset($foundSections[$section])) {
                $errors[] = "Abschnitt „{$section}“ nicht gefunden – hat sich die Seitenstruktur geändert?";
            }
        }

        return new ParseResult($episodes, $skipped, $errors);
    }
}
