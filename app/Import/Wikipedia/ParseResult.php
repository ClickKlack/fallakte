<?php

namespace App\Import\Wikipedia;

/**
 * Ergebnis eines Parser-Laufs: gelesene Folgen, bewusst übersprungene Zeilen und Fehler.
 */
final readonly class ParseResult
{
    /**
     * @param  list<ParsedEpisode>  $episodes
     * @param  list<string>  $skipped  Begründungen für übersprungene Zeilen
     * @param  list<string>  $errors  Zeilen oder Strukturen, die nicht gelesen werden konnten
     */
    public function __construct(
        public array $episodes,
        public array $skipped = [],
        public array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
