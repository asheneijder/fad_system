<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stationary_items 
            MODIFY COLUMN category 
            ENUM('writing', 'paper', 'desk', 'filing', 'computer', 'mailing', 'cleaning', 'photostat_paper', 'printed_paper', 'stationaries', 'office_supplies', 'other') 
            NOT NULL DEFAULT 'writing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE stationary_items 
            MODIFY COLUMN category 
            ENUM('writing', 'paper', 'desk', 'filing', 'computer', 'mailing', 'cleaning', 'other') 
            NOT NULL DEFAULT 'writing'");
    }
};
