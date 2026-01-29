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
        Schema::create('inseminations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cow_id')->constrained('cows')->onDelete('cascade');
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->date('insemination_date');
            $table->enum('status', ['pending', 'confirmed', 'failed', 'needs_repeat'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cow_id', 'insemination_date']);
            $table->index(['animal_id', 'insemination_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inseminations');
    }
};
