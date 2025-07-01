<?php

use App\Models\ModelType;
use App\Enums\AssetStatus;
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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('asset_tag_no')->unique();
            $table->string('serial_no')->unique();
            $table->foreignIdFor(ModelType::class);
            $table->foreignIdFor(CategoryType::class);
            $table->string('status')->default(AssetStatus::ACTIVE->value);
            $table->integer('qty')->nullable();
            $table->string('location')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
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
