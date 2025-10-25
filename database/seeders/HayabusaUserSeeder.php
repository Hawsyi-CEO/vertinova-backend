<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HayabusaUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hayabusaUsers = [
            [
                'name' => 'Obi',
                'email' => 'obi@simpaskor.id',
                'password' => Hash::make('hayabusa123'),
                'role' => 'hayabusa',
            ],
            [
                'name' => 'Lucky',
                'email' => 'lucky@simpaskor.id',
                'password' => Hash::make('hayabusa123'),
                'role' => 'hayabusa',
            ],
            [
                'name' => 'Guntur',
                'email' => 'guntur@simpaskor.id',
                'password' => Hash::make('hayabusa123'),
                'role' => 'hayabusa',
            ],
            [
                'name' => 'Rizal',
                'email' => 'rizal@simpaskor.id',
                'password' => Hash::make('hayabusa123'),
                'role' => 'hayabusa',
            ],
            [
                'name' => 'Wanda',
                'email' => 'wanda@simpaskor.id',
                'password' => Hash::make('hayabusa123'),
                'role' => 'hayabusa',
            ],
        ];

        foreach ($hayabusaUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Hayabusa users seeded successfully!');
    }
}
