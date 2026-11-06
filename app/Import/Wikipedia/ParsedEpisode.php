<?php

namespace App\Import\Wikipedia;

use Carbon\CarbonImmutable;

/**
 * Eine aus Wikipedia gelesene Folge.
 */
final readonly class ParsedEpisode
{
    /**
     * @param  int|null  $number  offizielle Folgennummer; null bei Filmen ohne Nummer
     * @param  int|null  $positionAfter  bei Filmen ohne Nummer: Nummer der vorangehenden Folge
     * @param  string|null  $wikipediaRef  Ziel des Titel-Links (Artikelname), stabiler Schlüssel
     * @param  string|null  $teamRaw  Rohwert der Ermittler-Spalte (bereinigt, ohne Gastauftritte)
     */
    public function __construct(
        public ?int $number,
        public string $title,
        public ?CarbonImmutable $firstAired,
        public ?string $teamRaw,
        public ?string $wikipediaRef,
        public bool $fromAnnouncedSection = false,
        public ?int $positionAfter = null,
    ) {}
}
