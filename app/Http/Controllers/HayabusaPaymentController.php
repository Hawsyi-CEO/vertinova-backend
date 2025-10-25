<?php

namespace App\Http\Controllers;

use App\Models\HayabusaPayment;
use App\Models\Transaction;
use App\Models\TransactionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HayabusaPaymentController extends Controller
{
    /**
     * Display a listing of payments for Hayabusa user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Jika user adalah Hayabusa, hanya tampilkan payment miliknya
        if ($user->role === 'hayabusa') {
            $payments = HayabusaPayment::with(['transaction', 'transactionGroup', 'paidByUser'])
                ->forHayabusa($user->id)
                ->orderBy('payment_date', 'desc')
                ->paginate(20);
        }
        // Jika finance atau admin, tampilkan semua
        else if (in_array($user->role, ['finance', 'admin'])) {
            $payments = HayabusaPayment::with(['hayabusaUser', 'transaction', 'transactionGroup', 'paidByUser'])
                ->orderBy('payment_date', 'desc')
                ->paginate(20);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($payments);
    }

    /**
     * Store a newly created payment (Finance only)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['finance', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'hayabusa_user_id' => 'required|exists:users,id',
            'transaction_group_id' => 'required|exists:transaction_groups,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'period' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,paid,cancelled',
        ]);

        try {
            DB::beginTransaction();

            // Verifikasi bahwa hayabusa_user adalah user dengan role hayabusa
            $hayabusaUser = \App\Models\User::findOrFail($validated['hayabusa_user_id']);
            if ($hayabusaUser->role !== 'hayabusa') {
                return response()->json(['message' => 'User yang dipilih bukan Hayabusa'], 400);
            }

            // Buat transaction pengeluaran untuk Simpaskor
            $transaction = Transaction::create([
                'transaction_group_id' => $validated['transaction_group_id'],
                'type' => 'keluar',
                'amount' => $validated['amount'],
                'date' => $validated['payment_date'],
                'description' => "Honor Hayabusa: " . $hayabusaUser->name . " - " . $validated['period'],
                'created_by' => $user->id,
            ]);

            // Buat Hayabusa payment record
            $payment = HayabusaPayment::create([
                'hayabusa_user_id' => $validated['hayabusa_user_id'],
                'transaction_id' => $transaction->id,
                'transaction_group_id' => $validated['transaction_group_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'period' => $validated['period'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'paid_by' => $user->id,
                'paid_at' => isset($validated['status']) && $validated['status'] === 'paid' ? now() : null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran berhasil dibuat',
                'data' => $payment->load(['hayabusaUser', 'transaction', 'transactionGroup'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show($id)
    {
        $user = Auth::user();
        $payment = HayabusaPayment::with(['hayabusaUser', 'transaction', 'transactionGroup', 'paidByUser'])->findOrFail($id);

        // Hayabusa hanya bisa lihat payment miliknya
        if ($user->role === 'hayabusa' && $payment->hayabusa_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($payment);
    }

    /**
     * Update payment status (Finance only)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['finance', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $payment = HayabusaPayment::findOrFail($id);
        
        $payment->status = $validated['status'];
        if ($validated['status'] === 'paid' && !$payment->paid_at) {
            $payment->paid_at = now();
        }
        $payment->save();

        return response()->json([
            'message' => 'Status pembayaran berhasil diupdate',
            'data' => $payment->load(['hayabusaUser', 'transaction', 'transactionGroup'])
        ]);
    }

    /**
     * Get payment statistics for Hayabusa user
     */
    public function statistics()
    {
        $user = Auth::user();

        if ($user->role !== 'hayabusa') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $totalIncome = HayabusaPayment::forHayabusa($user->id)
            ->byStatus('paid')
            ->sum('amount');

        $pendingPayments = HayabusaPayment::forHayabusa($user->id)
            ->byStatus('pending')
            ->count();

        $recentPayments = HayabusaPayment::with(['transactionGroup'])
            ->forHayabusa($user->id)
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_income' => $totalIncome,
            'pending_payments' => $pendingPayments,
            'recent_payments' => $recentPayments,
        ]);
    }

    /**
     * Get list of Hayabusa users (Finance only)
     */
    public function getHayabusaUsers()
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['finance', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $hayabusaUsers = \App\Models\User::where('role', 'hayabusa')
            ->select('id', 'name', 'email', 'bank_name', 'account_number', 'account_holder_name')
            ->get();

        return response()->json($hayabusaUsers);
    }
}
