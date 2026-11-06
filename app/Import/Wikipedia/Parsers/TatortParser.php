<?php

namespace App\Import\Wikipedia\Parsers;

use App\Models\Series;

/**
 * Parser für die „Liste der Tatort-Folgen“.
 * Die ORF-eigenen Produktionen (Nummern wie „168a“) werden bewusst nicht gelesen.
 */
final class TatortParser extends EpisodeTableParser
{
    public function seriesSlug(): string
    {
        return Series::TATORT;
    }

    public function pageTitle(): string
    {
        return 'Liste der Tatort-Folgen';
    }

    protected function sections(): array
    {
        return [
            'Ausgestrahlte Folgen' => false,
            'Bisher noch nicht im Fernsehen ausgestrahlte Folgen' => true,
        ];
    }
}
