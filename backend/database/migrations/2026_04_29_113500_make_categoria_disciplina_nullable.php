<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En PostgreSQL, para un ENUM NOT NULL, necesitamos ALTER TABLE
        DB::statement('ALTER TABLE disciplinas ALTER COLUMN categoria_disciplina DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE disciplinas ALTER COLUMN categoria_disciplina SET NOT NULL');
    }
};
