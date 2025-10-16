<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        
        if ($users->count() > 0) {
            // Sample income transactions
            \App\Models\Transaction::create([
                'description' => 'Penjualan Produk A',
                'type' => 'income',
                'amount' => 5000000,
                'date' => '2025-10-01',
                'category' => 'Penjualan',
                'user_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'notes' => 'Penjualan produk A bulan Oktober'
            ]);
            
            \App\Models\Transaction::create([
                'description' => 'Konsultasi Jasa',
                'type' => 'income',
                'amount' => 2500000,
                'date' => '2025-10-02',
                'category' => 'Jasa',
                'user_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'notes' => 'Konsultasi IT untuk klien'
            ]);
            
            // Sample expense transactions
            \App\Models\Transaction::create([
                'description' => 'Pembelian Bahan Baku',
                'type' => 'expense',
                'amount' => 1500000,
                'date' => '2025-10-03',
                'category' => 'Operasional',
                'user_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'notes' => 'Bahan baku untuk produksi'
            ]);
            
            \App\Models\Transaction::create([
                'description' => 'Gaji Karyawan',
                'type' => 'expense',
                'amount' => 3000000,
                'date' => '2025-10-05',
                'category' => 'Gaji',
                'user_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'notes' => 'Gaji karyawan bulan Oktober'
            ]);
            
            \App\Models\Transaction::create([
                'description' => 'Sewa Kantor',
                'type' => 'expense',
                'amount' => 2000000,
                'date' => '2025-10-01',
                'category' => 'Sewa',
                'user_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'notes' => 'Sewa kantor bulan Oktober'
            ]);
        }
    }
}
