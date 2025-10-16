<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = \App\Models\User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $adminUser = \App\Models\User::first();
        }

        // Default Income Groups
        $incomeGroups = [
            [
                'name' => 'Proyek Simpaskor',
                'description' => 'Pemasukan dari proyek sistem penilaian kinerja pegawai',
                'type' => 'income',
                'color' => '#10B981',
            ],
            [
                'name' => 'Proyek Website',
                'description' => 'Pemasukan dari pengembangan website client',
                'type' => 'income',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Konsultasi IT',
                'description' => 'Pemasukan dari jasa konsultasi teknologi informasi',
                'type' => 'income',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Maintenance & Support',
                'description' => 'Pemasukan dari layanan pemeliharaan sistem',
                'type' => 'income',
                'color' => '#F59E0B',
            ],
        ];

        // Default Expense Groups
        $expenseGroups = [
            [
                'name' => 'Operasional Kantor',
                'description' => 'Pengeluaran untuk kebutuhan operasional sehari-hari',
                'type' => 'expense',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Honor & Gaji Pegawai',
                'description' => 'Pengeluaran untuk kompensasi karyawan',
                'type' => 'expense',
                'color' => '#F97316',
            ],
            [
                'name' => 'Peralatan & Aset',
                'description' => 'Pengeluaran untuk pembelian peralatan dan aset perusahaan',
                'type' => 'expense',
                'color' => '#6366F1',
            ],
            [
                'name' => 'Marketing & Promosi',
                'description' => 'Pengeluaran untuk kegiatan pemasaran dan promosi',
                'type' => 'expense',
                'color' => '#EC4899',
            ],
        ];

        foreach ($incomeGroups as $group) {
            \App\Models\TransactionGroup::create([
                ...$group,
                'created_by' => $adminUser->id,
            ]);
        }

        foreach ($expenseGroups as $group) {
            \App\Models\TransactionGroup::create([
                ...$group,
                'created_by' => $adminUser->id,
            ]);
        }
    }
}
