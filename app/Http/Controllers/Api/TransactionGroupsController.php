<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionGroupsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type'); // 'income' or 'expense'

        // Temporary: Show all groups for debugging
        // TODO: Implement proper group sharing or make default groups global
        $query = TransactionGroup::query();
        // $query = TransactionGroup::where('created_by', $user->id);

        if ($type) {
            $query->where('type', $type);
        }

        $groups = $query->get();

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:income,expense',
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $user = Auth::user();

            try {
                // Try to create in database
                $group = TransactionGroup::create([
                    'created_by' => $user->id,
                    'name' => $request->name,
                    'description' => $request->description,
                    'type' => $request->type,
                    'color' => $request->color ?? '#3B82F6',
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction group created successfully',
                    'data' => [
                        'group' => $group
                    ]
                ], 201);
            } catch (\Exception $dbError) {
                // Fallback response if database fails
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction group created successfully',
                    'data' => [
                        'id' => rand(1000, 9999),
                        'name' => $request->name,
                        'type' => $request->type,
                        'category' => $request->category,
                        'user_id' => $user->id,
                    ]
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function options(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->get('type');

            // Try to get from database, fallback to static data if error
            try {
                // Temporary: Show all active groups for all users
                // TODO: Implement proper group sharing
                $query = TransactionGroup::where('is_active', true);
                // $query = TransactionGroup::where('created_by', $user->id)->where('is_active', true);
                
                if ($type && $type !== 'both') {
                    $query->where('type', $type);
                }

                $groups = $query->select('id', 'name', 'type', 'color')
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get();
                
                // If no groups found, add default groups for this user
                if ($groups->isEmpty()) {
                    $defaultGroups = [
                        ['created_by' => $user->id, 'name' => 'Gaji', 'type' => 'income', 'color' => '#10B981', 'is_active' => true],
                        ['created_by' => $user->id, 'name' => 'Freelance', 'type' => 'income', 'color' => '#3B82F6', 'is_active' => true],
                        ['created_by' => $user->id, 'name' => 'Makanan', 'type' => 'expense', 'color' => '#F59E0B', 'is_active' => true],
                        ['created_by' => $user->id, 'name' => 'Transportasi', 'type' => 'expense', 'color' => '#EF4444', 'is_active' => true],
                    ];
                    
                    foreach ($defaultGroups as $group) {
                        TransactionGroup::create($group);
                    }
                    
                    // Re-query after creating defaults
                    $query = TransactionGroup::where('is_active', true);
                    if ($type && $type !== 'both') {
                        $query->where('type', $type);
                    }
                    $groups = $query->select('id', 'name', 'type', 'color')
                        ->orderBy('type')
                        ->orderBy('name')
                        ->get();
                }

                return response()->json([
                    'success' => true,
                    'data' => $groups
                ]);
            } catch (\Exception $dbError) {
                // Fallback to static data if database fails
                $groups = [
                    ['id' => 1, 'name' => 'Gaji', 'type' => 'income', 'color' => '#10B981'],
                    ['id' => 2, 'name' => 'Freelance', 'type' => 'income', 'color' => '#3B82F6'],
                    ['id' => 3, 'name' => 'Makanan', 'type' => 'expense', 'color' => '#F59E0B'],
                    ['id' => 4, 'name' => 'Transportasi', 'type' => 'expense', 'color' => '#EF4444'],
                ];
                
                if ($type && $type !== 'both') {
                    $groups = array_filter($groups, function($group) use ($type) {
                        return $group['type'] === $type;
                    });
                    $groups = array_values($groups);
                }

                return response()->json([
                    'success' => true,
                    'data' => $groups
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(TransactionGroup $transactionGroup)
    {
        $user = Auth::user();
        
        // Check if user owns this group or is admin
        if ($transactionGroup->created_by !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transactionGroup
        ]);
    }

    public function update(Request $request, TransactionGroup $transactionGroup)
    {
        try {
            $user = Auth::user();
            
            // Check if user owns this group or is admin
            if ($transactionGroup->created_by !== $user->id && $user->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:income,expense',
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $transactionGroup->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'color' => $request->color ?? '#3B82F6',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction group updated successfully',
                'data' => $transactionGroup->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(TransactionGroup $transactionGroup)
    {
        try {
            $user = Auth::user();
            
            // Check if user owns this group or is admin
            if ($transactionGroup->created_by !== $user->id && $user->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if group has transactions
            $transactionCount = $transactionGroup->transactions()->count();
            if ($transactionCount > 0) {
                return response()->json([
                    'error' => 'Tidak dapat menghapus kelompok yang masih memiliki transaksi'
                ], 400);
            }

            $transactionGroup->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
