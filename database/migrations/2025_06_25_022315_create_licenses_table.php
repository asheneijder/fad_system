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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_name');
            $table->string('product_key')->unique();
            $table->date('expiration_date')->nullable();
            $table->string('licensed_email')->nullable();
            $table->string('licensed_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->integer('min_qty')->default(0);
            $table->integer('total_qty')->default(0);
            $table->integer('available_qty')->default(0);
            $table->boolean('status')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
