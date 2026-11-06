<?php

namespace Database\Seeders;

use App\Models\Series;
use Illuminate\Database\Seeder;

/**
 * Legt die beiden Reihen an. Mehrfach ausführbar, ohne Duplikate zu erzeugen.
 */
class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        Series::updateOrCreate(['slug' => Series::TATORT], ['name' => 'Tatort']);
        Series::updateOrCreate(['slug' => Series::POLIZEIRUF], ['name' => 'Polizeiruf 110']);
    }
}
