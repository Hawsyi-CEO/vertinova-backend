<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HayabusaPayment extends Model
{
    protected $fillable = [
        'hayabusa_user_id',
        'transaction_id',
        'transaction_group_id',
        'amount',
        'payment_date',
        'period',
        'description',
        'status',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
    ];

    // Relasi ke User Hayabusa
    public function hayabusaUser()
    {
        return $this->belongsTo(User::class, 'hayabusa_user_id');
    }

    // Relasi ke Transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Relasi ke Transaction Group (Simpaskor)
    public function transactionGroup()
    {
        return $this->belongsTo(TransactionGroup::class);
    }

    // Relasi ke Finance yang membayar
    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Scope untuk filter by Hayabusa user
    public function scopeForHayabusa($query, $userId)
    {
        return $query->where('hayabusa_user_id', $userId);
    }

    // Scope untuk filter by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk filter by periode
    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }
}
