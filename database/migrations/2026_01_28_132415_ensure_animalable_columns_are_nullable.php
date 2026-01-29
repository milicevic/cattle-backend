<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure animalable_id and animalable_type are nullable
        // This is necessary for horses and sheep which don't use animalable relationships
        DB::statement('ALTER TABLE `animals` MODIFY `animalable_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `animals` MODIFY `animalable_type` VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot safely make these non-nullable without data loss
        // If needed, you would need to handle existing null values first
    }
};
