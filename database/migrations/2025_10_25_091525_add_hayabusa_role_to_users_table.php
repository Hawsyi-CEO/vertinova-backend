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
        // Ubah enum role untuk menambahkan 'hayabusa'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'finance', 'user', 'hayabusa') NOT NULL DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum sebelumnya
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'finance', 'user') NOT NULL DEFAULT 'user'");
    }
};
