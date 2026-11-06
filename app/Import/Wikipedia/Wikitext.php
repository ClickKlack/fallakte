<?php

namespace App\Import\Wikipedia;

/**
 * Hilfsfunktionen zum Bereinigen von Wikitext.
 */
final class Wikitext
{
    /** Entfernt HTML-Kommentare (z. B. auskommentierte Vorlagenzeilen). */
    public static function stripComments(string $text): string
    {
        return preg_replace('/<!--.*?-->/s', '', $text) ?? $text;
    }

    /** Entfernt Einzelnachweise (<ref>…</ref> und <ref … />). */
    public static function stripRefs(string $text): string
    {
        $text = preg_replace('/<ref\b[^>]*\/>/is', '', $text) ?? $text;

        return preg_replace('/<ref\b[^>]*>.*?<\/ref>/is', '', $text) ?? $text;
    }

    /** Entfernt alle Vorlagen {{…}}, auch verschachtelte. */
    public static function stripTemplates(string $text): string
    {
        do {
            $text = preg_replace('/\{\{[^{}]*\}\}/s', '', $text, -1, $count) ?? $text;
        } while ($count > 0);

        return $text;
    }

    /**
     * Teilt eine Zelle an <br>-Zeilenumbrüchen in ihre Teile.
     *
     * @return list<string>
     */
    public static function splitLines(string $text): array
    {
        return preg_split('/<br\s*\/?>/i', $text) ?: [$text];
    }

    /** Liefert das Ziel des ersten internen Links, z. B. "Tatort: Messer". */
    public static function firstLinkTarget(string $text): ?string
    {
        if (! preg_match('/\[\[([^|\]]+)/', self::stripRefs($text), $match)) {
            return null;
        }

        return self::normalizeWhitespace(str_replace('_', ' ', html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** Wandelt Wikitext in reinen, lesbaren Text um. */
    public static function toPlainText(string $text): string
    {
        $text = self::stripRefs(self::stripComments($text));
        $text = self::stripTemplates($text);
        $text = preg_replace('/<nowiki>(.*?)<\/nowiki>/is', '$1', $text) ?? $text;
        // [[Ziel|Text]] → Text, [[Ziel]] → Ziel
        $text = preg_replace('/\[\[(?:[^|\]]*\|)?([^\]]*)\]\]/', '$1', $text) ?? $text;
        // Externe Links [url Text] → Text
        $text = preg_replace('/\[(?:https?:)?\/\/\S+\s+([^\]]*)\]/', '$1', $text) ?? $text;
        $text = preg_replace('/<br\s*\/?>/i', ' ', $text) ?? $text;
        $text = preg_replace('/<[^>]+>/', '', $text) ?? $text;
        $text = str_replace(["'''", "''"], '', $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::normalizeWhitespace($text);
    }

    /** Fasst Leerraum (inkl. geschützter Leerzeichen) zusammen. */
    public static function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/[\s\x{00A0}\x{202F}]+/u', ' ', $text) ?? $text);
    }

    /**
     * Teilt einen String an einem Trennzeichen, ignoriert dabei Vorkommen
     * innerhalb von [[…]] und {{…}}.
     *
     * @return list<string>
     */
    public static function splitTopLevel(string $text, string $separator): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($text);
        $sepLength = strlen($separator);

        for ($i = 0; $i < $length; $i++) {
            $pair = substr($text, $i, 2);

            if ($pair === '[[' || $pair === '{{') {
                $depth++;
                $current .= $pair;
                $i++;

                continue;
            }

            if (($pair === ']]' || $pair === '}}') && $depth > 0) {
                $depth--;
                $current .= $pair;
                $i++;

                continue;
            }

            if ($depth === 0 && substr($text, $i, $sepLength) === $separator) {
                $parts[] = $current;
                $current = '';
                $i += $sepLength - 1;

                continue;
            }

            $current .= $text[$i];
        }

        $parts[] = $current;

        return $parts;
    }
}
