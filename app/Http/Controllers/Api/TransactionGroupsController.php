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

        // Show all active groups (universal system - no type filtering)
        $query = TransactionGroup::where('is_active', true);

        $groups = $query->with(['transactions' => function($query) {
            $query->latest()->take(5); // Latest 5 transactions for preview
        }])->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'color' => $group->color,
                'created_by' => $group->created_by,
                'is_active' => $group->is_active,
                'is_simpaskor' => $group->name === 'Simpaskor', // Flag for Simpaskor group
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
                'statistics' => [
                    'total_income' => $group->transactions()->where('type', 'income')->sum('amount'),
                    'total_expense' => $group->transactions()->where('type', 'expense')->sum('amount'),
                    'transaction_count' => $group->transactions()->count(),
                    'last_transaction' => $group->transactions()->latest()->first()?->created_at,
                ],
                'recent_transactions' => $group->transactions
            ];
        });

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
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $user = Auth::user();

            // Create universal transaction group (no type restriction)
            $group = TransactionGroup::create([
                'created_by' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => 'universal', // Set to universal for new system
                'color' => $request->color ?? '#3B82F6',
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction group created successfully',
                'data' => $group
            ], 201);
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

            // Get all active universal groups (no type filtering)
            $query = TransactionGroup::where('is_active', true);
            
            $groups = $query->select('id', 'name', 'color', 'description')
                ->orderBy('name')
                ->get();
            
            // If no groups found, create default universal groups
            if ($groups->isEmpty()) {
                $defaultGroups = [
                    ['created_by' => $user->id, 'name' => 'Keuangan Pribadi', 'type' => 'universal', 'color' => '#3B82F6', 'is_active' => true, 'description' => 'Kelompok untuk transaksi keuangan pribadi'],
                    ['created_by' => $user->id, 'name' => 'Proyek Freelance', 'type' => 'universal', 'color' => '#10B981', 'is_active' => true, 'description' => 'Kelompok untuk transaksi proyek freelance'],
                    ['created_by' => $user->id, 'name' => 'Usaha Sampingan', 'type' => 'universal', 'color' => '#F59E0B', 'is_active' => true, 'description' => 'Kelompok untuk transaksi usaha sampingan'],
                ];
                
                foreach ($defaultGroups as $group) {
                    TransactionGroup::create($group);
                }
                
                // Re-query after creating defaults
                $groups = TransactionGroup::where('is_active', true)
                    ->select('id', 'name', 'color', 'description')
                    ->orderBy('name')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
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
        
        // For now, allow access to all groups (can add permissions later)
        // if ($transactionGroup->created_by !== $user->id && $user->role !== 'admin') {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        // Load group with its transactions and statistics
        $transactionGroup->load(['transactions' => function($query) {
            $query->latest();
        }]);

        $data = [
            'id' => $transactionGroup->id,
            'name' => $transactionGroup->name,
            'description' => $transactionGroup->description,
            'color' => $transactionGroup->color,
            'created_by' => $transactionGroup->created_by,
            'is_active' => $transactionGroup->is_active,
            'created_at' => $transactionGroup->created_at,
            'updated_at' => $transactionGroup->updated_at,
            'statistics' => [
                'total_income' => $transactionGroup->transactions()->where('type', 'income')->sum('amount'),
                'total_expense' => $transactionGroup->transactions()->where('type', 'expense')->sum('amount'),
                'net_amount' => $transactionGroup->transactions()->where('type', 'income')->sum('amount') - $transactionGroup->transactions()->where('type', 'expense')->sum('amount'),
                'transaction_count' => $transactionGroup->transactions()->count(),
                'last_transaction' => $transactionGroup->transactions()->latest()->first()?->created_at,
            ],
            'transactions' => $transactionGroup->transactions
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function update(Request $request, TransactionGroup $transactionGroup)
    {
        try {
            $user = Auth::user();
            
            // Protect Simpaskor name from being changed
            if ($transactionGroup->name === 'Simpaskor' && $request->name !== 'Simpaskor') {
                return response()->json([
                    'error' => 'Nama kelompok Simpaskor tidak dapat diubah'
                ], 403);
            }
            
            // Check if user owns this group or is admin
            if ($transactionGroup->created_by !== $user->id && $user->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $transactionGroup->update([
                'name' => $request->name,
                'description' => $request->description,
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
            
            // Protect Simpaskor from deletion
            if ($transactionGroup->name === 'Simpaskor') {
                return response()->json([
                    'error' => 'Kelompok Simpaskor tidak dapat dihapus karena merupakan kelompok sistem'
                ], 403);
            }
            
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
