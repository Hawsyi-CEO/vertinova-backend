<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        \App\Models\User::create([
            'name' => 'Admin Vertinova',
            'email' => 'admin@vertinova.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create finance user
        \App\Models\User::create([
            'name' => 'Finance Manager',
            'email' => 'finance@vertinova.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'finance',
        ]);

        // Create regular user
        \App\Models\User::create([
            'name' => 'User Demo',
            'email' => 'user@vertinova.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'user',
        ]);
    }
}
