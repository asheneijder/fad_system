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
        Schema::create('stationary_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['writing', 'paper', 'desk', 'filing', 'computer', 'mailing', 'cleaning', 'other'])->default('writing');
            $table->enum('unit', ['pcs', 'boxes', 'packs', 'reams', 'sets', 'bottles', 'rolls', 'units'])->default('pcs');
            $table->string('sku')->nullable()->unique();
            $table->integer('min_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('location')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('current_stock');
            $table->index('supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stationary_items');
    }
};
