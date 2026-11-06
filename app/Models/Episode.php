<?php

namespace App\Models;

use App\Enums\EpisodeStatus;
use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Einzelne Folge einer Reihe.
 *
 * @property int|null $number offizielle Folgennummer; null bei Filmen ohne Nummer
 * @property int $sort_key Reihenfolge innerhalb der Reihe (Nummer * 100, bei Filmen ohne Nummer + Index)
 * @property-read string $display_number Folgennummer bzw. Ersatznummer wie "59a"
 * @property EpisodeStatus $status
 * @property list<string> $manual_fields
 */
#[Fillable([
    'series_id', 'number', 'sort_key', 'title', 'first_aired', 'broadcaster', 'team_id',
    'seen', 'seen_at', 'wikipedia_ref', 'manual_fields',
])]
class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    /** Felder, deren manuelle Änderung vor dem Überschreiben durch den Import geschützt ist. */
    public const PROTECTABLE_FIELDS = ['title', 'first_aired', 'broadcaster', 'team_id', 'number'];

    /** Abstand der sort_key-Werte zwischen zwei Folgennummern. */
    public const SORT_STEP = 100;

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
            'sort_key' => 'integer',
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

            if ($episode->number !== null && ($episode->isDirty('number') || ! $episode->sort_key)) {
                $episode->sort_key = $episode->number * self::SORT_STEP;
            }
        });
    }

    /** Folgennummer oder Ersatznummer für Filme ohne offizielle Nummer ("59a"). */
    protected function displayNumber(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->number !== null) {
                return (string) $this->number;
            }

            $index = $this->sort_key % self::SORT_STEP;

            return intdiv($this->sort_key, self::SORT_STEP).($index > 0 && $index <= 26 ? chr(96 + $index) : '');
        });
    }

    /** Prüft, ob ein Feld manuell geändert wurde und daher vor dem Import geschützt ist. */
    public function isProtected(string $field): bool
    {
        return in_array($field, $this->manual_fields ?? [], true);
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
