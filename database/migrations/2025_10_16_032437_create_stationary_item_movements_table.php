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
        Schema::create('stationary_item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stationary_item_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'return']);
            $table->integer('quantity');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->text('notes')->nullable();
            $table->date('movement_date');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['stationary_item_id', 'movement_date']);
            $table->index('type');
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stationary_item_movements');
    }
};
