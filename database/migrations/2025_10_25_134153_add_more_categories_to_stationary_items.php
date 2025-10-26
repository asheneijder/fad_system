<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE stationary_items MODIFY COLUMN category ENUM('writing', 'paper', 'desk', 'filing', 'computer', 'mailing', 'cleaning', 'other', 'fastening', 'stamping', 'pantry', 'tools', 'adhesive')");
    }

    public function down()
    {
        DB::statement("ALTER TABLE stationary_items MODIFY COLUMN category ENUM('writing', 'paper', 'desk', 'filing', 'computer', 'mailing', 'cleaning', 'other')");
    }
};
