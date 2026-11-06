<?php

namespace App\Import\Wikipedia;

/**
 * Eine Tabelle aus Wikitext mit Kopfzeile, Datenzeilen und dem Abschnitt, in dem sie steht.
 */
final readonly class WikitextTable
{
    /**
     * @param  list<string>  $headers  bereinigte Spaltenüberschriften
     * @param  list<list<string>>  $rows  Zellen als roher Wikitext
     */
    public function __construct(
        public ?string $section,
        public array $headers,
        public array $rows,
    ) {}

    /**
     * Findet alle Tabellen im Wikitext.
     *
     * @return list<self>
     */
    public static function extractAll(string $wikitext): array
    {
        $lines = preg_split('/\R/', Wikitext::stripComments($wikitext)) ?: [];
        $tables = [];
        $section = null;
        $buffer = null;
        $depth = 0;

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            if ($buffer === null && preg_match('/^(=+)\s*(.*?)\s*\1\s*$/', $trimmed, $match)) {
                $section = Wikitext::toPlainText($match[2]);

                continue;
            }

            if (str_starts_with($trimmed, '{|')) {
                $depth++;
                $buffer ??= [];
                if ($depth === 1) {
                    continue;
                }
            }

            if ($buffer === null) {
                continue;
            }

            if (str_starts_with($trimmed, '|}')) {
                $depth--;
                if ($depth === 0) {
                    $tables[] = self::fromLines($section, $buffer);
                    $buffer = null;

                    continue;
                }
            }

            $buffer[] = $line;
        }

        return $tables;
    }

    /**
     * @param  list<string>  $lines  Zeilen zwischen "{|" und "|}"
     */
    private static function fromLines(?string $section, array $lines): self
    {
        $headers = [];
        $rows = [];
        $cells = [];
        $isHeaderRow = false;

        $flush = function () use (&$cells, &$rows, &$headers, &$isHeaderRow) {
            $cells = array_map(trim(...), $cells);
            if ($isHeaderRow && $headers === []) {
                $headers = array_map(Wikitext::toPlainText(...), $cells);
            } elseif ($cells !== [] && array_filter($cells, fn ($c) => trim($c) !== '') !== []) {
                $rows[] = $cells;
            }
            $cells = [];
            $isHeaderRow = false;
        };

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            if (str_starts_with($trimmed, '|-')) {
                $flush();

                continue;
            }

            if (str_starts_with($trimmed, '|+')) {
                continue; // Tabellenüberschrift
            }

            if (str_starts_with($trimmed, '!')) {
                $isHeaderRow = true;
                foreach (Wikitext::splitTopLevel(substr($trimmed, 1), '!!') as $cell) {
                    $cells[] = self::stripCellAttributes($cell);
                }

                continue;
            }

            if (str_starts_with($trimmed, '|')) {
                foreach (Wikitext::splitTopLevel(substr($trimmed, 1), '||') as $cell) {
                    $cells[] = self::stripCellAttributes($cell);
                }

                continue;
            }

            // Fortsetzung einer mehrzeiligen Zelle
            if ($cells !== []) {
                $cells[array_key_last($cells)] .= "\n".$line;
            }
        }

        $flush();

        return new self($section, $headers, $rows);
    }

    /**
     * Entfernt Zellattribute wie `style="…" |` vor dem eigentlichen Inhalt.
     * Wie in MediaWiki gilt alles vor dem ersten einzelnen "|" (außerhalb von Links/Vorlagen)
     * als Attributbereich – auch wenn er leer ist (`||[[…]]` am Zeilenanfang).
     */
    private static function stripCellAttributes(string $cell): string
    {
        $parts = Wikitext::splitTopLevel($cell, '|');

        if (count($parts) > 1) {
            array_shift($parts);
            $cell = implode('|', $parts);
        }

        return trim($cell);
    }

    /**
     * Index der ersten Spalte, deren Überschrift auf das Muster passt.
     */
    public function columnIndex(string $pattern): ?int
    {
        foreach ($this->headers as $index => $header) {
            if (preg_match($pattern, $header)) {
                return $index;
            }
        }

        return null;
    }
}
