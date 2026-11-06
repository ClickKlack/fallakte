<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filme ohne offizielle Folgennummer (z. B. als Einzelfilm gesendete Polizeiruf-Produktionen):
 * number wird optional, sort_key bestimmt die Reihenfolge und die Ersatznummer ("59a").
 * sort_key = Folgennummer * 100, für Filme ohne Nummer: vorherige Nummer * 100 + laufender Index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->change();
            $table->unsignedInteger('sort_key')->default(0)->after('number');
            $table->index(['series_id', 'sort_key']);
        });

        DB::table('episodes')->whereNotNull('number')->update(['sort_key' => DB::raw('number * 100')]);
    }

    public function down(): void
    {
        DB::table('episodes')->whereNull('number')->delete();

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex(['series_id', 'sort_key']);
            $table->dropColumn('sort_key');
            $table->unsignedInteger('number')->nullable(false)->change();
        });
    }
};
