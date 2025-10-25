<?php

namespace Database\Seeders;

use App\Models\TransactionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpaskorGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin/finance user to be the creator
        $creator = User::whereIn('role', ['admin', 'finance'])->first();
        
        if (!$creator) {
            $creator = User::first();
        }

        // Check if Simpaskor group already exists
        $simpaskor = TransactionGroup::where('name', 'Simpaskor')->first();

        if (!$simpaskor) {
            TransactionGroup::create([
                'name' => 'Simpaskor',
                'description' => 'Kelompok transaksi Simpaskor dengan manajemen tim Hayabusa',
                'type' => 'universal',
                'color' => '#10B981', // Green color for Simpaskor
                'is_active' => true,
                'created_by' => $creator->id,
            ]);

            $this->command->info('✅ Kelompok Simpaskor berhasil dibuat');
        } else {
            $this->command->info('ℹ️  Kelompok Simpaskor sudah ada');
        }

        // Create default other groups if needed
        $defaultGroups = [
            [
                'name' => 'Keuangan Pribadi',
                'description' => 'Kelompok untuk transaksi keuangan pribadi',
                'type' => 'universal',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Proyek Freelance',
                'description' => 'Kelompok untuk transaksi proyek freelance',
                'type' => 'universal',
                'color' => '#8B5CF6',
            ],
        ];

        foreach ($defaultGroups as $groupData) {
            $exists = TransactionGroup::where('name', $groupData['name'])->exists();
            
            if (!$exists) {
                TransactionGroup::create([
                    ...$groupData,
                    'is_active' => true,
                    'created_by' => $creator->id,
                ]);
                
                $this->command->info("✅ Kelompok {$groupData['name']} berhasil dibuat");
            }
        }
    }
}
