<?php

namespace App\Import\Wikipedia;

/**
 * Leitet aus der Ermittler-Spalte den Rohwert für die Team-Zuordnung ab.
 *
 * - Angezeigter Linktext statt Linkziel („[[Lena Odenthal|Odenthal und Stern]]“ → „Odenthal und Stern“)
 * - Angaben in Klammern (Gastauftritte, Nebenfiguren) werden entfernt
 * - Bei Crossover-Folgen (mehrere Teams per Zeilenumbruch) zählt das erstgenannte Team
 */
final class TeamName
{
    public static function fromCell(string $cell): ?string
    {
        $cell = Wikitext::stripRefs(Wikitext::stripComments($cell));

        foreach (Wikitext::splitLines($cell) as $part) {
            $name = Wikitext::toPlainText($part);
            $name = self::stripParentheses($name);
            $name = trim($name, " \t,;/–-");
            $name = Wikitext::normalizeWhitespace($name);

            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private static function stripParentheses(string $text): string
    {
        do {
            $text = preg_replace('/\s*\([^()]*\)/u', '', $text, -1, $count) ?? $text;
        } while ($count > 0);

        return $text;
    }
}
