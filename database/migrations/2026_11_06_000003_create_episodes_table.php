<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->date('first_aired')->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            // Als String statt DB-Enum, damit die Migration auf SQLite und MariaDB gleich funktioniert.
            $table->string('status', 20);
            $table->boolean('seen')->default(false);
            $table->date('seen_at')->nullable();
            $table->string('wikipedia_ref')->nullable();
            // Default [] wird im Model gesetzt (JSON-Defaults sind nicht auf allen Datenbanken erlaubt).
            $table->json('manual_fields');
            $table->timestamps();

            $table->unique(['series_id', 'number']);
            $table->index('first_aired');
            $table->index(['status', 'seen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
