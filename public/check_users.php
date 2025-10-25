<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== USERS ===\n\n";

$users = User::all(['id', 'name', 'email', 'role']);

if ($users->isEmpty()) {
    echo "No users found.\n";
} else {
    echo "Total: " . $users->count() . " users\n\n";
    foreach ($users as $user) {
        echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Role: {$user->role}\n";
    }
}
