<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        
        // For admin and finance, show all transactions
        // For user, show only their own transactions
        $query = Transaction::query();
        
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }
        
        $totalIncome = $query->clone()->where('type', 'income')->sum('amount');
        $totalExpense = $query->clone()->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $totalTransactions = $query->count();
        
        return response()->json([
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'totalTransactions' => $totalTransactions,
        ]);
    }
}
