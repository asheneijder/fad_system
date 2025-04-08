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
        Schema::table('users', function (Blueprint $table) {
            $table->string('graph_id')->nullable()->after('remember_token');
            $table->string('display_name')->nullable()->after('graph_id');
            $table->string('surname')->nullable()->after('display_name');
            $table->string('mail')->nullable()->after('surname');
            $table->string('given_name')->nullable()->after('mail');
            $table->string('job_title')->nullable()->after('given_name');
            $table->string('department')->nullable()->after('job_title');
            $table->string('office_location')->nullable()->after('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'graph_id',
                'display_name',
                'surname',
                'mail',
                'given_name',
                'job_title',
                'department',
                'office_location',
            ]);
        });
    }
};
