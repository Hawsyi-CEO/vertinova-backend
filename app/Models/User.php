<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bank_name',
        'account_number',
        'account_holder_name',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function createdTransactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isFinance()
    {
        return $this->role === 'finance';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isHayabusa()
    {
        return $this->role === 'hayabusa';
    }

    public function employeePayments()
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function approvedPayments()
    {
        return $this->hasMany(EmployeePayment::class, 'approved_by');
    }

    // Hayabusa payments relationship
    public function hayabusaPayments()
    {
        return $this->hasMany(HayabusaPayment::class, 'hayabusa_user_id');
    }

    // Hayabusa payments yang sudah dibayar
    public function paidHayabusaPayments()
    {
        return $this->hasMany(HayabusaPayment::class, 'paid_by');
    }

    // Calculate total payments for a specific period
    public function getTotalPayments($period = null)
    {
        $query = $this->employeePayments()->where('status', 'paid');
        
        if ($period) {
            $query->where('payment_period', $period);
        }
        
        return $query->sum('amount');
    }

    // Get payment summary for the user
    public function getPaymentSummary()
    {
        return [
            'total_payments' => $this->employeePayments()->where('status', 'paid')->sum('amount'),
            'pending_payments' => $this->employeePayments()->where('status', 'pending')->sum('amount'),
            'this_month' => $this->getTotalPayments(date('Y-m')),
        ];
    }

    // Get Hayabusa payment summary
    public function getHayabusaPaymentSummary()
    {
        if (!$this->isHayabusa()) {
            return null;
        }

        return [
            'total_income' => $this->hayabusaPayments()->where('status', 'paid')->sum('amount'),
            'pending_income' => $this->hayabusaPayments()->where('status', 'pending')->sum('amount'),
            'this_month' => $this->hayabusaPayments()
                ->where('status', 'paid')
                ->whereYear('payment_date', date('Y'))
                ->whereMonth('payment_date', date('m'))
                ->sum('amount'),
        ];
    }
}
