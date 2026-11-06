<?php

namespace App\Models;

use Database\Factories\TeamAliasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rohwert aus Wikipedia (Ermittler-/Ort-Spalte), der einem Team zugeordnet ist.
 */
#[Fillable(['series_id', 'team_id', 'raw_value'])]
class TeamAlias extends Model
{
    /** @use HasFactory<TeamAliasFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        // Serie immer vom Team übernehmen, damit Alias und Team nie auseinanderlaufen.
        static::saving(function (TeamAlias $alias) {
            if ($alias->team_id !== null && ($alias->isDirty('team_id') || $alias->series_id === null)) {
                $alias->series_id = Team::query()->whereKey($alias->team_id)->value('series_id');
            }
        });
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Series, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
