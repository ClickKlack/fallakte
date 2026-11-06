<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Befüllt die Datenbank mit den Stammdaten.
     * Bewusst ohne Testbenutzer: Benutzer werden per `php artisan user:create` angelegt.
     */
    public function run(): void
    {
        $this->call(SeriesSeeder::class);
    }
}
