<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayment extends Model
{
    protected $fillable = [
        'user_id',
        'payment_type',
        'amount',
        'payment_period',
        'payment_date',
        'description',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'employee_payment_id');
    }

    // Scope untuk filter by payment type
    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    // Scope untuk filter by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk filter by period
    public function scopeByPeriod($query, $period)
    {
        return $query->where('payment_period', $period);
    }
}
