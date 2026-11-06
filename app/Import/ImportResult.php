<?php

namespace App\Import;

/**
 * Zusammenfassung eines Importlaufs für eine Reihe.
 */
class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    /** Folgen, bei denen mindestens ein geschütztes Feld von Wikipedia abweicht und beibehalten wurde. */
    public int $skipped = 0;

    public int $teamsCreated = 0;

    public int $promoted = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> Hinweise, z. B. bewusst übersprungene Zeilen */
    public array $notices = [];

    /** @var list<string> lesbare Beschreibung jeder Änderung */
    public array $changes = [];

    public function __construct(
        public readonly string $seriesName,
        public readonly bool $dryRun = false,
    ) {}
}
