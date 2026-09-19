<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PayrollRecord model. branch_id column retained but no FK relationship (branches table removed).
 */
class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'salary_month',
        'gross_salary',
        'bonus',
        'deductions',
        'net_salary',
        'payment_status',
        'paid_on',
    ];

    protected $casts = [
        'salary_month' => 'date:Y-m',
        'gross_salary' => 'decimal:2',
        'bonus'        => 'decimal:2',
        'deductions'   => 'decimal:2',
        'net_salary'   => 'decimal:2',
        'paid_on'      => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}

