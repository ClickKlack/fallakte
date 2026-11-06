<?php

namespace App\Support;

/**
 * Liest die App-Version (Semantic Versioning, MAJOR.MINOR.PATCH) aus der Datei VERSION.
 * Fehlt die Datei oder ist der Inhalt ungültig, gilt „dev“.
 */
final class AppVersion
{
    public static function fromFile(string $file): string
    {
        $version = is_file($file) ? trim((string) file_get_contents($file)) : '';

        return preg_match('/^\d+\.\d+\.\d+$/', $version) ? $version : 'dev';
    }
}
