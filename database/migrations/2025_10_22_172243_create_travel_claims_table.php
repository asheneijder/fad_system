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
        Schema::create('travel_claims', function (Blueprint $table) {
            $table->id();

            // User and approver relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');

            // Vehicle information
            $table->enum('vehicle_type', ['car', 'motorcycle', 'bicycle', 'other'])->default('car');
            $table->string('registration_plate_number')->nullable();
            $table->integer('cubic_capacity')->nullable()->comment('Engine CC for vehicles');

            // Travel details
            $table->date('date_of_travel');
            $table->date('end_date_of_travel')->nullable();
            $table->boolean('is_multiple_days')->default(false);
            $table->string('travel_from');
            $table->string('travel_to');
            $table->text('purpose');

            // Claim calculation
            $table->decimal('total_distance', 8, 2)->default(0);
            $table->decimal('rate_per_km', 8, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            // Status and approval
            $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->timestamp('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('claim_date')->nullable()->comment('When the claim was submitted');

            // Timestamps
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->index('approver_id');
            $table->index('status');
            $table->index('date_of_travel');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_claims');
    }
};
