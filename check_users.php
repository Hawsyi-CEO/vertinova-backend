<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Check existing users
echo "=== EXISTING USERS ===\n";
$users = User::all(['id', 'name', 'email', 'role']);
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$user->role}\n";
}

echo "\n=== PASSWORD VERIFICATION ===\n";
$adminUser = User::where('email', 'admin@vertinova.com')->first();
if ($adminUser) {
    echo "Admin user found: {$adminUser->name}\n";
    echo "Password hash: " . substr($adminUser->password, 0, 20) . "...\n";
    
    // Test password
    $testPassword = 'admin123';
    $isValid = Hash::check($testPassword, $adminUser->password);
    echo "Password '{$testPassword}' is " . ($isValid ? 'VALID' : 'INVALID') . "\n";
    
    // Try different password
    $testPassword2 = 'password';
    $isValid2 = Hash::check($testPassword2, $adminUser->password);
    echo "Password '{$testPassword2}' is " . ($isValid2 ? 'VALID' : 'INVALID') . "\n";
} else {
    echo "Admin user NOT FOUND!\n";
}

echo "\n=== CREATING NEW ADMIN USER ===\n";
try {
    $newAdmin = User::updateOrCreate(
        ['email' => 'admin@test.com'],
        [
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]
    );
    echo "Test admin created: {$newAdmin->email} / password123\n";
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}

echo "Done!\n";