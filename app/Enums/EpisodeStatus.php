<?php

namespace App\Enums;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Ausstrahlungsstatus einer Folge.
 */
enum EpisodeStatus: string
{
    case Aired = 'aired';
    case Announced = 'announced';

    /**
     * Ermittelt den Status aus dem Erstausstrahlungsdatum:
     * kein Datum oder Datum in der Zukunft → angekündigt, sonst ausgestrahlt.
     * Eine Folge, die heute ausgestrahlt wird, gilt bereits als ausgestrahlt.
     */
    public static function fromAirDate(?DateTimeInterface $firstAired, ?DateTimeInterface $today = null): self
    {
        if ($firstAired === null) {
            return self::Announced;
        }

        $today = Carbon::instance($today ?? Carbon::now())->startOfDay();

        return Carbon::instance($firstAired)->startOfDay()->greaterThan($today)
            ? self::Announced
            : self::Aired;
    }
}
