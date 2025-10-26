<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_allowances', function (Blueprint $table) {
            $table->id();

            // User and approver relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');

            // Claim details
            $table->date('claim_date');
            $table->enum('allowance_type', ['full_day', 'breakfast', 'lunch', 'dinner'])->default('full_day');
            $table->enum('currency', ['MYR', 'USD'])->default('MYR');
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('claim_percentage', 5, 2)->default(0); // 100.00, 20.00, 40.00, etc.
            $table->decimal('claim_amount', 10, 2)->default(0);

            // Additional information
            $table->string('destination');
            $table->text('purpose');

            // Status and approval
            $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected', 'paid'])->default('draft');
            $table->timestamp('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('approver_id');
            $table->index('status');
            $table->index('claim_date');
            $table->index('allowance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_allowances');
    }
};
