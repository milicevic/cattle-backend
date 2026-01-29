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
        Schema::table('cows', function (Blueprint $table) {
            $table->date('last_insemination_date')->nullable()->after('last_calving_date');
            $table->date('expected_calving_date')->nullable()->after('last_insemination_date');
            $table->date('actual_calving_date')->nullable()->after('expected_calving_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cows', function (Blueprint $table) {
            $table->dropColumn(['last_insemination_date', 'expected_calving_date', 'actual_calving_date']);
        });
    }
};
