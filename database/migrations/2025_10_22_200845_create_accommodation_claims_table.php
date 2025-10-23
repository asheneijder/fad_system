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
        Schema::create('accommodation_claims', function (Blueprint $table) {
            $table->id();

            // User and approver relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');

            // Hotel and stay information
            $table->string('hotel_name');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('number_of_nights')->default(1);

            // Pricing information
            $table->enum('currency', ['MYR', 'USD', 'SGD'])->default('MYR');
            $table->decimal('rate_per_night', 10, 2)->default(0);

            // Tax and service charges (in percentage)
            $table->decimal('tax_percentage', 5, 2)->default(0)->comment('Tax percentage (e.g., 6.00 for 6%)');
            $table->decimal('service_charge_percentage', 5, 2)->default(0)->comment('Service charge percentage (e.g., 10.00 for 10%)');

            // Calculated amounts
            $table->decimal('subtotal_amount', 12, 2)->default(0)->comment('rate_per_night × number_of_nights');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('Calculated tax amount');
            $table->decimal('service_charge_amount', 10, 2)->default(0)->comment('Calculated service charge amount');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('subtotal + tax + service_charge');

            // Claim details
            $table->text('purpose');
            $table->string('destination_city');
            $table->string('destination_country')->default('Malaysia');
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
            $table->index('check_in_date');
            $table->index('check_out_date');
            $table->index('hotel_name');
            $table->index('destination_city');
            $table->index('created_at');

            // Composite indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['check_in_date', 'check_out_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_claims');
    }
};
