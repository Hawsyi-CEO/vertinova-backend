<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum type to include 'universal'
        DB::statement("ALTER TABLE transaction_groups MODIFY COLUMN type ENUM('income', 'expense', 'universal') NOT NULL");
        
        // Remove unique constraint on name+type since universal groups should allow duplicate names
        Schema::table('transaction_groups', function (Blueprint $table) {
            $table->dropUnique(['name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'universal' from enum
        DB::statement("ALTER TABLE transaction_groups MODIFY COLUMN type ENUM('income', 'expense') NOT NULL");
        
        // Re-add unique constraint
        Schema::table('transaction_groups', function (Blueprint $table) {
            $table->unique(['name', 'type']);
        });
    }
};
