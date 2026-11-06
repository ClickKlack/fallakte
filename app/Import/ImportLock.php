<?php

namespace App\Import;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

/**
 * Gemeinsame Sperre für Importe: Es läuft immer nur ein Import gleichzeitig –
 * egal ob per Artisan-Befehl (SSH) oder über den Knopf in der App.
 */
final class ImportLock
{
    public const NAME = 'fallakte:episodes-import';

    /** Sicherheitsnetz: Bricht ein Import hart ab, wird die Sperre spätestens nach dieser Zeit frei. */
    public const SECONDS = 900;

    public static function make(): Lock
    {
        return Cache::lock(self::NAME, self::SECONDS);
    }
}
