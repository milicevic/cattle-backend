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
        Schema::create('cattle_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->decimal('weight', 8, 2)->nullable(); // Weight in kg
            $table->integer('heart_rate')->nullable(); // Beats per minute
            $table->decimal('temperature', 4, 2)->nullable(); // Body temperature in Celsius
            $table->integer('respiration_rate')->nullable(); // Breaths per minute
            $table->text('notes')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('animal_id');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cattle_vitals');
    }
};
