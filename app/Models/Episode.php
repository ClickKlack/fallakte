<?php

namespace App\Models;

use App\Enums\EpisodeStatus;
use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Einzelne Folge einer Reihe.
 *
 * @property EpisodeStatus $status
 * @property list<string> $manual_fields
 */
#[Fillable([
    'series_id', 'number', 'title', 'first_aired', 'team_id',
    'seen', 'seen_at', 'wikipedia_ref', 'manual_fields',
])]
class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    /**
     * Standardwerte neuer Folgen.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'seen' => false,
        'manual_fields' => '[]',
    ];

    /**
     * Attribute, die gecastet werden.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'first_aired' => 'date',
            'status' => EpisodeStatus::class,
            'seen' => 'boolean',
            'seen_at' => 'date',
            'manual_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Der Status ergibt sich immer aus dem Ausstrahlungsdatum.
        static::saving(function (Episode $episode) {
            $episode->status = EpisodeStatus::fromAirDate($episode->first_aired);
        });
    }

    /**
     * Setzt angekündigte Folgen, deren Ausstrahlungsdatum erreicht ist, auf "ausgestrahlt".
     *
     * @return int Anzahl aktualisierter Folgen
     */
    public static function promoteAiredAnnouncements(): int
    {
        return static::query()
            ->where('status', EpisodeStatus::Announced)
            ->whereNotNull('first_aired')
            ->whereDate('first_aired', '<=', today())
            ->update(['status' => EpisodeStatus::Aired, 'updated_at' => now()]);
    }

    /** @param Builder<Episode> $query */
    public function scopeAired(Builder $query): void
    {
        $query->where('status', EpisodeStatus::Aired);
    }

    /** @param Builder<Episode> $query */
    public function scopeAnnounced(Builder $query): void
    {
        $query->where('status', EpisodeStatus::Announced);
    }

    /** @return BelongsTo<Series, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
