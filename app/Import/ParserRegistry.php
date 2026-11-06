<?php

namespace App\Import;

use App\Import\Wikipedia\EpisodeListParser;
use InvalidArgumentException;

/**
 * Verzeichnis der Parser je Serie (Schlüssel: Serien-Slug).
 */
class ParserRegistry
{
    /** @var array<string, EpisodeListParser> */
    private array $parsers = [];

    /**
     * @param  iterable<EpisodeListParser>  $parsers
     */
    public function __construct(iterable $parsers)
    {
        foreach ($parsers as $parser) {
            $this->parsers[$parser->seriesSlug()] = $parser;
        }
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_keys($this->parsers);
    }

    public function for(string $slug): EpisodeListParser
    {
        return $this->parsers[$slug] ?? throw new InvalidArgumentException("Kein Parser für die Reihe „{$slug}“.");
    }
}
