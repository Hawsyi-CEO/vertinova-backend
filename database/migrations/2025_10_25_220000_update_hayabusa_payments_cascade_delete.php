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
        Schema::table('hayabusa_payments', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['transaction_id']);
            
            // Add foreign key with cascade delete
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade'); // Hapus hayabusa_payment jika transaction dihapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hayabusa_payments', function (Blueprint $table) {
            // Revert to set null
            $table->dropForeign(['transaction_id']);
            
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('set null');
        });
    }
};
