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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_tag')->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->foreignId('model_id')->constrained('model_types')->onDelete('cascade');
            $table->enum('status', ['available', 'assigned', 'maintenance', 'retired'])->default('available');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->default(0);
            $table->integer('warranty_months')->default(0);
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('asset_tag');
            $table->index('serial_number');
            $table->index('status');
            $table->index('model_id');
            $table->index('assigned_to');
            $table->index('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
