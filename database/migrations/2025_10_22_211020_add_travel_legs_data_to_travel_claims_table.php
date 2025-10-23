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
        Schema::table('travel_claims', function (Blueprint $table) {
            $table->json('travel_legs_data')->nullable()->after('total_cost')->comment('JSON data for multiple travel legs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_claims', function (Blueprint $table) {
            $table->dropColumn('travel_legs_data');
        });
    }
};
