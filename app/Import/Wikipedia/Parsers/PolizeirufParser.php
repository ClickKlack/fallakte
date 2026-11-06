<?php

namespace App\Import\Wikipedia\Parsers;

use App\Models\Series;

/**
 * Parser für die „Liste der Polizeiruf-110-Folgen“.
 * Die Abschnitte „Folgen außerhalb der Reihe“ und „Verbotene Folgen“ werden nicht gelesen.
 * Gesendete Filme ohne Folgennummer werden mit ihrer Position übernommen.
 */
final class PolizeirufParser extends EpisodeTableParser
{
    public function seriesSlug(): string
    {
        return Series::POLIZEIRUF;
    }

    public function pageTitle(): string
    {
        return 'Liste der Polizeiruf-110-Folgen';
    }

    protected function sections(): array
    {
        return [
            'Gesendete Folgen' => false,
            'Noch nicht ausgestrahlte Folgen' => true,
        ];
    }
}
