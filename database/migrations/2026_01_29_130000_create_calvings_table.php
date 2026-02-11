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
        Schema::create('calvings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cow_id')->constrained('cows')->onDelete('cascade');
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->date('calving_date');
            $table->boolean('is_successful')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['cow_id', 'calving_date']);
            $table->index(['animal_id', 'calving_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calvings');
    }
};
