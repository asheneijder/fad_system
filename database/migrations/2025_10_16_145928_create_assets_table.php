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
            $table->string('asset_name');
            $table->string('asset_tag_no')->unique();
            $table->string('serial_no')->nullable()->unique();
            $table->foreignId('model_type_id')->constrained('model_types')->onDelete('cascade');
            $table->foreignId('category_type_id')->constrained('categories')->onDelete('cascade');
            $table->enum('status', ['active', 'available', 'assigned', 'maintenance', 'retired'])->default('active');
            $table->integer('qty')->default(1);
            $table->string('location');
            $table->string('location_2')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->integer('estimated_life')->default(0); // in years
            $table->integer('estimated_life_days')->default(0); // in days
            $table->date('fully_depreciated_date')->nullable();
            $table->decimal('depreciation_cost', 15, 2)->default(0);
            $table->date('last_sighting_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('asset_tag_no');
            $table->index('serial_no');
            $table->index('status');
            $table->index('model_type_id');
            $table->index('category_type_id');
            $table->index('assigned_to');
            $table->index('purchase_date');
            $table->index('updated_by');
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
