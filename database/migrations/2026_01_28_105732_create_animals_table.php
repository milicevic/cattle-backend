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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('tag_number');
            $table->unsignedBigInteger('animalable_id')->nullable();
            $table->string('animalable_type')->nullable();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->enum('species', ['cattle', 'horse', 'sheep']);
            $table->string('type'); // Bull, Cow, Steer, Heifer, Stallion, Gelding, Mare, Filly, ram, wether, ewe, ewe_lamb
            $table->string('name')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth')->nullable();
            $table->foreignId('mother_id')->nullable()->constrained('animals')->onDelete('set null');
            $table->foreignId('father_id')->nullable()->constrained('animals')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['farm_id', 'tag_number']);
            $table->index(['animalable_id', 'animalable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
