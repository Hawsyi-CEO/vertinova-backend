<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@vertinova.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@vertinova.com',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        // Create finance user
        User::updateOrCreate(
            ['email' => 'finance@vertinova.com'],
            [
                'name' => 'Finance Manager',
                'email' => 'finance@vertinova.com',
                'password' => bcrypt('finance123'),
                'role' => 'finance',
            ]
        );

        // Create regular user
        User::updateOrCreate(
            ['email' => 'user@vertinova.com'],
            [
                'name' => 'Regular User',
                'email' => 'user@vertinova.com',
                'password' => bcrypt('user123'),
                'role' => 'user',
            ]
        );
    }
}
