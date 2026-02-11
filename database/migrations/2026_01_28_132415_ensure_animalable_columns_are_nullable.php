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
        // Ensure animalable_id and animalable_type are nullable
        // This is necessary for horses and sheep which don't use animalable relationships
        Schema::table('animals', function (Blueprint $table) {
            $table->unsignedBigInteger('animalable_id')->nullable()->change();
            $table->string('animalable_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot safely make these non-nullable without data loss
        // If needed, you would need to handle existing null values first
        Schema::table('animals', function (Blueprint $table) {
            $table->unsignedBigInteger('animalable_id')->nullable(false)->change();
            $table->string('animalable_type')->nullable(false)->change();
        });
    }
};
