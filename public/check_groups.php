<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TransactionGroup;

echo "=== TRANSACTION GROUPS ===\n\n";

$groups = TransactionGroup::all(['id', 'name', 'type', 'created_by', 'is_active']);

if ($groups->isEmpty()) {
    echo "No transaction groups found.\n";
} else {
    echo "Total: " . $groups->count() . " groups\n\n";
    foreach ($groups as $group) {
        echo "ID: {$group->id} | Name: {$group->name} | Type: {$group->type} | Active: " . ($group->is_active ? 'Yes' : 'No') . " | Created by: {$group->created_by}\n";
    }
}
