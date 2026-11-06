<?php

namespace App\Import\Wikipedia;

use Carbon\CarbonImmutable;

/**
 * Liest Datumsangaben aus Wikipedia-Zellen:
 * {{DatumZelle|1970-11-29}}, „29. Nov. 1970“, „29. November 1970“ oder „29.11.1970“.
 */
final class GermanDate
{
    private const MONTHS = [
        'jan' => 1, 'januar' => 1, 'jänner' => 1, 'jän' => 1,
        'feb' => 2, 'februar' => 2,
        'mär' => 3, 'mrz' => 3, 'märz' => 3,
        'apr' => 4, 'april' => 4,
        'mai' => 5,
        'jun' => 6, 'juni' => 6,
        'jul' => 7, 'juli' => 7,
        'aug' => 8, 'august' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'okt' => 10, 'oktober' => 10,
        'nov' => 11, 'november' => 11,
        'dez' => 12, 'dezember' => 12,
    ];

    /**
     * @return CarbonImmutable|null null, wenn die Zelle leer ist oder kein gültiges Datum enthält
     */
    public static function parse(string $cell): ?CarbonImmutable
    {
        if (preg_match('/\{\{\s*DatumZelle\s*\|\s*(\d{4})-(\d{1,2})-(\d{1,2})/i', $cell, $m)) {
            return self::make((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        $text = Wikitext::toPlainText($cell);

        if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/', $text, $m)) {
            return self::make((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/(\d{1,2})\.\s*([[:alpha:]]+)\.?\s+(\d{4})/u', $text, $m)) {
            $month = self::MONTHS[mb_strtolower($m[2])] ?? null;

            return $month === null ? null : self::make((int) $m[3], $month, (int) $m[1]);
        }

        return null;
    }

    /** Prüft, ob die Zelle Inhalt hat, der nach einem Datum aussieht, aber nicht gelesen werden konnte. */
    public static function isUnreadable(string $cell): bool
    {
        return self::parse($cell) === null && preg_match('/\d/', Wikitext::toPlainText($cell)) === 1;
    }

    private static function make(int $year, int $month, int $day): ?CarbonImmutable
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }
}
