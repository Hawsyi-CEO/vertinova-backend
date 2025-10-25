<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Laravel\Sanctum\Sanctum;

echo "=== TESTING TRANSACTION GROUPS API ===\n\n";

// Get first user
$user = User::find(2); // User ID 2 dari hasil sebelumnya

if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User: {$user->name} ({$user->email})\n";
echo "Role: {$user->role}\n\n";

// Create token
$token = $user->createToken('test-token')->plainTextToken;
echo "Token created: {$token}\n\n";

echo "Test with this curl command:\n\n";
echo "curl -X GET \"http://localhost:8000/api/transaction-groups/options\" \\\n";
echo "     -H \"Accept: application/json\" \\\n";
echo "     -H \"Authorization: Bearer {$token}\"\n\n";

// Cleanup token
$user->tokens()->delete();
echo "\nToken cleaned up after showing command.\n";
