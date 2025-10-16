<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

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
            'transaction_group_id' => 'nullable|exists:transaction_groups,id',
            'employee_payment_id' => 'nullable|exists:employee_payments,id',
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
        
        // Only admin and finance can create transactions
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
            'user_id' => $request->user_id ?? $user->id,
            'created_by' => $user->id,
            'notes' => $request->notes,
        ]);

        $transaction->load(['user', 'createdBy', 'transactionGroup', 'employeePayment']);

        return response()->json($transaction, 201);
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
        $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Only admin and finance can update transactions
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->update([
            'description' => $request->description,
            'type' => $request->type,
            'amount' => $request->amount,
            'date' => $request->date,
            'category' => $request->category,
            'user_id' => $request->user_id ?? $transaction->user_id,
            'notes' => $request->notes,
        ]);

        $transaction->load(['user', 'createdBy']);
        return response()->json($transaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        
        // Only admin and finance can delete transactions
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
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
