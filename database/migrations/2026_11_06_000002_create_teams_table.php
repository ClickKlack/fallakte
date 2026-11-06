<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('investigators')->nullable();
            $table->timestamps();
        });

        Schema::create('team_aliases', function (Blueprint $table) {
            $table->id();
            // Redundant zu teams.series_id, damit die Eindeutigkeit je Serie per Index abgesichert ist.
            $table->foreignId('series_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('raw_value');

            $table->unique(['series_id', 'raw_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_aliases');
        Schema::dropIfExists('teams');
    }
};
