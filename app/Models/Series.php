<?php

namespace App\Models;

use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Krimireihe (Tatort, Polizeiruf 110).
 */
#[Fillable(['name', 'slug'])]
class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory;

    public const TATORT = 'tatort';

    public const POLIZEIRUF = 'polizeiruf-110';

    public $timestamps = false;

    /** @return HasMany<Team, $this> */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /** @return HasMany<Episode, $this> */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    /** @return HasMany<ImportRun, $this> */
    public function importRuns(): HasMany
    {
        return $this->hasMany(ImportRun::class);
    }
}
