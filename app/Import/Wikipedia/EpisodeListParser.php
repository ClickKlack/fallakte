<?php

namespace App\Import\Wikipedia;

/**
 * Gemeinsame Schnittstelle der Parser je Serie.
 */
interface EpisodeListParser
{
    /** Slug der Serie, für die dieser Parser zuständig ist. */
    public function seriesSlug(): string;

    /** Titel der Wikipedia-Seite mit der Episodenliste. */
    public function pageTitle(): string;

    /** Liest alle Folgen aus dem Wikitext der Seite. */
    public function parse(string $wikitext): ParseResult;
}
