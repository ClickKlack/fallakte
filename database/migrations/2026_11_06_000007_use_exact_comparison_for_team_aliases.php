<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rohwerte der Team-Aliase exakt vergleichen (Groß-/Kleinschreibung und Akzente zählen).
 * MariaDB/MySQL vergleichen mit utf8mb4_unicode_ci sonst „Sahin“ und „Şahin“ als gleich – SQLite nicht.
 * So verhalten sich beide Datenbanken gleich, und Schreibweisen werden nur bewusst zusammengeführt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return; // SQLite vergleicht ohnehin exakt
        }

        Schema::table('team_aliases', function (Blueprint $table) {
            $table->string('raw_value')->collation('utf8mb4_bin')->change();
        });
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('team_aliases', function (Blueprint $table) {
            $table->string('raw_value')->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
