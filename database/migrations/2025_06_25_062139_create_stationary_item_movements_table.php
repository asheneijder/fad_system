<?php

use App\Models\StationaryItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stationary_item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StationaryItem::class)->constrained()->cascadeOnDelete();
            $table->date('movement_date');
            $table->integer('brought_forward')->default(0);
            $table->integer('in')->default(0);
            $table->integer('out')->default(0);
            $table->integer('closing_balance')->default(0);
            $table->timestamps();
            $table->unique(['stationary_item_id', 'movement_date'], 'item_date_unique'); 
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
