<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set animalable_id and animalable_type to null for horses and sheep
        // since they don't use animalable relationships
        DB::table('animals')
            ->whereIn('species', ['horse', 'sheep'])
            ->update([
                'animalable_id' => null,
                'animalable_type' => null,
            ]);

        // Also fix any animals that reference deleted classes
        DB::table('animals')
            ->whereIn('animalable_type', [
                'App\\Models\\HorseAnimalable',
                'App\\Models\\SheepAnimalable',
            ])
            ->update([
                'animalable_id' => null,
                'animalable_type' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't know what the original values were
    }
};
