<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayment;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeePaymentController extends Controller
{
    /**
     * Display a listing of employee payments.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = EmployeePayment::with(['employee:id,name,email', 'approvedBy:id,name']);
        
        // If user role is 'user', only show their own payments
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ]);
    }

    /**
     * Store a newly created employee payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_type' => 'required|in:salary,bonus,overtime,allowance',
            'amount' => 'required|numeric|min:0',
            'payment_period' => 'required|string|max:100',
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        
        // Only admin and finance can create employee payments
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $payment = EmployeePayment::create([
            'user_id' => $request->user_id,
            'payment_type' => $request->payment_type,
            'amount' => $request->amount,
            'payment_period' => $request->payment_period,
            'payment_date' => $request->payment_date,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        $payment->load(['employee:id,name,email', 'approvedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Employee payment created successfully',
            'data' => $payment
        ], 201);
    }

    /**
     * Display the specified employee payment.
     */
    public function show(Request $request, EmployeePayment $employeePayment)
    {
        $user = $request->user();
        
        // Users can only see their own payments
        if ($user->role === 'user' && $employeePayment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $employeePayment->load(['employee:id,name,email', 'approvedBy:id,name']);
        
        return response()->json([
            'success' => true,
            'data' => $employeePayment
        ]);
    }

    /**
     * Update the specified employee payment.
     */
    public function update(Request $request, EmployeePayment $employeePayment)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_type' => 'required|in:salary,bonus,overtime,allowance',
            'amount' => 'required|numeric|min:0',
            'payment_period' => 'required|string|max:100',
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        
        // Only admin and finance can update employee payments
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $employeePayment->update([
            'user_id' => $request->user_id,
            'payment_type' => $request->payment_type,
            'amount' => $request->amount,
            'payment_period' => $request->payment_period,
            'payment_date' => $request->payment_date,
            'description' => $request->description,
        ]);

        $employeePayment->load(['employee:id,name,email', 'approvedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Employee payment updated successfully',
            'data' => $employeePayment
        ]);
    }

    /**
     * Remove the specified employee payment.
     */
    public function destroy(Request $request, EmployeePayment $employeePayment)
    {
        $user = $request->user();
        
        // Only admin and finance can delete employee payments
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $employeePayment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee payment deleted successfully'
        ]);
    }

    /**
     * Get employees for dropdown.
     */
    public function employees()
    {
        $employees = User::select('id', 'name', 'email')
            ->where('role', 'user')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Approve employee payment.
     */
    public function approve(Request $request, EmployeePayment $employeePayment)
    {
        $user = $request->user();
        
        // Only admin and finance can approve payments
        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $employeePayment->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee payment approved successfully',
            'data' => $employeePayment
        ]);
    }
}
