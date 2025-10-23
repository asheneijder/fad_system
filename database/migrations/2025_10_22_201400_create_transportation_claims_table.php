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
        Schema::create('transportation_claims', function (Blueprint $table) {
            $table->id();

            // User and approver relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');

            // Claim basic information
            $table->date('claim_date');
            $table->enum('transport_type', [
                'grab', 'taxi', 'mrt', 'lrt', 'ecl', 'bus', 'toll', 'parking', 'other',
            ])->default('other');
            $table->text('purpose');

            // Route information
            $table->string('from_location');
            $table->string('to_location');
            $table->decimal('distance_km', 8, 2)->nullable()->comment('Distance in kilometers');

            // Pricing information
            $table->enum('currency', ['MYR', 'USD'])->default('MYR');
            $table->decimal('rate_per_km', 8, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);

            // Trip details
            $table->enum('trip_type', ['one_way', 'round_trip'])->default('one_way');
            $table->integer('number_of_trips')->default(1);

            // Additional information
            $table->string('receipt_number')->nullable();
            $table->text('remarks')->nullable();

            // Status and approval
            $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected', 'paid'])->default('draft');
            $table->timestamp('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->index('approver_id');
            $table->index('status');
            $table->index('claim_date');
            $table->index('transport_type');
            $table->index('from_location');
            $table->index('to_location');
            $table->index('created_at');

            // Composite indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['claim_date', 'transport_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportation_claims');
    }
};
