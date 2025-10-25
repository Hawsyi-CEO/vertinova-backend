<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\HayabusaPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaction::with(['user:id,name,email', 'createdBy:id,name', 'transactionGroup:id,name,color', 'employeePayment:id,employee_name,amount']);
        
        // If user role is 'user', only show their own transactions
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }
        
        // Filter by transaction group if provided
        if ($request->has('transaction_group_id') && $request->transaction_group_id) {
            $query->where('transaction_group_id', $request->transaction_group_id);
        }
        
        // Filter by type if provided
        if ($request->has('type') && $request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        // Filter by date range if provided
        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('expense_category', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $limit = $request->get('limit');
        if ($limit) {
            $transactions = $query->orderBy('created_at', 'desc')->limit($limit)->get();
            return response()->json([
                'success' => true,
                'data' => $transactions,
                'meta' => null
            ]);
        } else {
            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);
            return response()->json([
                'success' => true,
                'data' => $transactions->items(),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ]
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'expense_category' => 'nullable|string|max:100',
            'expense_subcategory' => 'nullable|string|max:100',
            'transaction_group_id' => 'required|exists:transaction_groups,id',
            'employee_payment_id' => 'nullable|exists:employee_payments,id',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'hayabusa_user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validasi tambahan untuk Pembayaran Hayabusa
        if ($request->expense_category === 'Pembayaran Hayabusa') {
            if (!$request->hayabusa_user_id) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['hayabusa_user_id' => ['Hayabusa harus dipilih']]
                ], 422);
            }

            // Verifikasi bahwa user yang dipilih adalah Hayabusa
            $hayabusaUser = User::find($request->hayabusa_user_id);
            if (!$hayabusaUser || $hayabusaUser->role !== 'hayabusa') {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['hayabusa_user_id' => ['User yang dipilih bukan Hayabusa']]
                ], 422);
            }
        }

        $user = $request->user();
        
        // Users with role 'user' can create transactions but only for themselves
        if ($user->role === 'user') {
            // User can only create transactions for themselves
            $userId = $user->id;
        } else {
            // Admin and finance can specify user_id or default to themselves
            $userId = $request->user_id ?? $user->id;
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::create([
                'description' => $request->description,
                'type' => $request->type,
                'amount' => $request->amount,
                'date' => $request->date,
                'category' => $request->category,
                'expense_category' => $request->expense_category,
                'expense_subcategory' => $request->expense_subcategory,
                'transaction_group_id' => $request->transaction_group_id,
                'employee_payment_id' => $request->employee_payment_id,
                'user_id' => $userId,
                'created_by' => $user->id,
                'notes' => $request->notes,
            ]);

            // Jika kategori adalah Pembayaran Hayabusa, buat record HayabusaPayment
            if ($request->expense_category === 'Pembayaran Hayabusa' && $request->hayabusa_user_id) {
                // Extract period from description or notes, or use date
                $period = date('F Y', strtotime($request->date)); // Default: "January 2025"
                
                HayabusaPayment::create([
                    'hayabusa_user_id' => $request->hayabusa_user_id,
                    'transaction_id' => $transaction->id,
                    'transaction_group_id' => $request->transaction_group_id,
                    'amount' => $request->amount,
                    'payment_date' => $request->date,
                    'period' => $period,
                    'description' => $request->notes ?? $request->description,
                    'status' => 'paid', // Langsung paid karena sudah dibuat transaksinya
                    'paid_by' => $user->id,
                    'paid_at' => now(),
                ]);
            }

            DB::commit();

            $transaction->load(['user', 'createdBy', 'transactionGroup', 'employeePayment']);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        
        // Users can only see their own transactions
        if ($user->role === 'user' && $transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->load(['user', 'createdBy']);
        return response()->json($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'expense_category' => 'nullable|string|max:100',
            'transaction_group_id' => 'required|exists:transaction_groups,id',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Users can only edit their own transactions
        if ($user->role === 'user' && $transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // For users with role 'user', they can only update to themselves
        if ($user->role === 'user') {
            $userId = $user->id;
        } else {
            // Admin and finance can specify user_id or keep existing
            $userId = $request->user_id ?? $transaction->user_id;
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'description' => $request->description,
                'type' => $request->type,
                'amount' => $request->amount,
                'date' => $request->date,
                'category' => $request->category,
                'expense_category' => $request->expense_category,
                'transaction_group_id' => $request->transaction_group_id,
                'user_id' => $userId,
                'notes' => $request->notes,
            ]);

            // Update hayabusa_payment jika transaksi ini terkait dengan pembayaran hayabusa
            $hayabusaPayment = HayabusaPayment::where('transaction_id', $transaction->id)->first();
            if ($hayabusaPayment) {
                $hayabusaPayment->update([
                    'amount' => $request->amount,
                    'payment_date' => $request->date,
                    'transaction_group_id' => $request->transaction_group_id,
                ]);
            }

            DB::commit();
            
            $transaction->load(['user', 'createdBy', 'transactionGroup']);
            
            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        
        // Users can only delete their own transactions
        if ($user->role === 'user' && $transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Admin and finance can delete any transactions
        if (!in_array($user->role, ['admin', 'finance', 'user'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // Hapus hayabusa_payment jika transaksi ini terkait dengan pembayaran hayabusa
            HayabusaPayment::where('transaction_id', $transaction->id)->delete();
            
            // Hapus transaksi
            $transaction->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $query = Transaction::query();
        
        // Filter by user role
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }
        
        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $transactionCount = $query->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'transaction_count' => $transactionCount
            ]
        ]);
    }

    /**
     * Get monthly/yearly reports
     */
    public function reports(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type', 'monthly'); // monthly or yearly
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        $query = Transaction::with(['user:id,name', 'transactionGroup:id,name,color']);
        
        // Filter by user role
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }
        
        // Apply date filters
        if ($type === 'monthly') {
            $query->whereYear('date', $year)->whereMonth('date', $month);
        } else {
            $query->whereYear('date', $year);
        }
        
        // Get individual transactions for table display
        $transactions = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        
        // Get aggregated data for charts/summary
        $aggregateQuery = Transaction::query();
        if ($user->role === 'user') {
            $aggregateQuery->where('user_id', $user->id);
        }
        
        if ($type === 'monthly') {
            $aggregateQuery->whereYear('date', $year)->whereMonth('date', $month);
            
            $chartData = $aggregateQuery->selectRaw('
                DAY(date) as period,
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense,
                COUNT(*) as transaction_count
            ')
            ->groupBy('period')
            ->orderBy('period')
            ->get();
            
        } else {
            $aggregateQuery->whereYear('date', $year);
            
            $chartData = $aggregateQuery->selectRaw('
                MONTH(date) as period,
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense,
                COUNT(*) as transaction_count
            ')
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        }
        
        // Calculate totals
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        
        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->date,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type,
                        'user' => $transaction->user ? $transaction->user->name : 'Unknown',
                        'category' => $transaction->transactionGroup ? $transaction->transactionGroup->name : 'Tidak ada kategori',
                        'category_color' => $transaction->transactionGroup ? $transaction->transactionGroup->color : '#64748b'
                    ];
                }),
                'chart_data' => $chartData,
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'balance' => $totalIncome - $totalExpense,
                    'transaction_count' => $transactions->count(),
                    'period' => $type === 'monthly' ? "$year-$month" : $year,
                    'period_type' => $type
                ]
            ]
        ]);
    }
}
