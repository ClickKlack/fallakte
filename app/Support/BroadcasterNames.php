<?php

namespace App\Support;

/**
 * Zerlegt Senderangaben in einzelne Sender.
 * Koproduktionen stehen in Wikipedia als „SWR/SRF“, „NDR und MDR“ oder
 * „Fernsehen der DDR (1974); MDR (2011)“.
 */
final class BroadcasterNames
{
    /**
     * @return list<string>
     */
    public static function split(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:\/|;|\bund\b)\s*/u', $value) ?: [];
        $names = [];

        foreach ($parts as $part) {
            $name = trim(preg_replace('/\s*\([^)]*\)/u', '', $part) ?? $part);

            if ($name !== '' && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Alle einzelnen Sender aus einer Liste von Senderangaben, alphabetisch sortiert.
     *
     * @param  iterable<string|null>  $values
     * @return list<string>
     */
    public static function distinct(iterable $values): array
    {
        $names = [];

        foreach ($values as $value) {
            foreach (self::split($value) as $name) {
                $names[$name] = true;
            }
        }

        $names = array_keys($names);
        sort($names, SORT_LOCALE_STRING);

        return $names;
    }

    /**
     * LIKE-Muster (Escape-Zeichen "!"), die einen Sender als Teil einer Angabe finden:
     * allein, am Anfang, am Ende oder in der Mitte einer Koproduktion.
     *
     * @return list<string>
     */
    public static function likePatterns(string $name): array
    {
        $n = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $name);

        return [
            "{$n}/%", "%/{$n}", "%/{$n}/%",
            "{$n} und %", "% und {$n}",
            "{$n} (%", "%; {$n}", "%; {$n} (%",
        ];
    }
}
