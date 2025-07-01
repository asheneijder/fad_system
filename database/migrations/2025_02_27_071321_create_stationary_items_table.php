<?php

use App\Models\CategoryType;
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
        Schema::create('stationary_items', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignIdFor(CategoryType::class)->constrained()->cascadeOnDelete();
            $table->string('unit');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
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
