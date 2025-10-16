<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'type',
        'amount',
        'date',
        'category',
        'expense_category',
        'expense_subcategory',
        'employee_payment_id',
        'transaction_group_id',
        'user_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function transactionGroup()
    {
        return $this->belongsTo(TransactionGroup::class);
    }

    public function employeePayment()
    {
        return $this->belongsTo(EmployeePayment::class);
    }

    public function scopeAssetExpense($query)
    {
        return $query->where('expense_category', 'asset');
    }

    public function scopeOperationalExpense($query)
    {
        return $query->where('expense_category', 'operational');
    }

    public function scopeEmployeePayments($query)
    {
        return $query->whereNotNull('employee_payment_id');
    }
}
