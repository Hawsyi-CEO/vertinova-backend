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
        Schema::create('hayabusa_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hayabusa_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('transaction_group_id')->constrained('transaction_groups')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('period'); // e.g., "Oktober 2025"
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Index untuk query cepat
            $table->index(['hayabusa_user_id', 'payment_date']);
            $table->index(['transaction_group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hayabusa_payments');
    }
};
