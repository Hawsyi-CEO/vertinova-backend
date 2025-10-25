<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateHayabusaBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing Hayabusa users with sample bank account data
        DB::table('users')
            ->where('email', 'obi@simpaskor.id')
            ->update([
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => 'Obi'
            ]);

        echo "✅ Updated bank account for Obi\n";
    }
}
