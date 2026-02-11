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
        Schema::table('inseminations', function (Blueprint $table) {
            $table->foreignId('bull_id')->nullable()->after('animal_id')->constrained('bulls')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inseminations', function (Blueprint $table) {
            $table->dropForeign(['bull_id']);
        });
    }
};
