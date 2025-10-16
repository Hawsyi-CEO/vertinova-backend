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
        Schema::table('transactions', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('transactions', 'expense_category')) {
                $table->string('expense_category')->nullable()->after('category');
            }
            if (!Schema::hasColumn('transactions', 'expense_subcategory')) {
                $table->string('expense_subcategory')->nullable()->after('expense_category');
            }
            if (!Schema::hasColumn('transactions', 'employee_payment_id')) {
                $table->unsignedBigInteger('employee_payment_id')->nullable()->after('expense_subcategory');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['expense_category', 'expense_subcategory', 'employee_payment_id']);
        });
    }
};
