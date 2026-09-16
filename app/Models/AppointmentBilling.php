<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentBilling extends Model
{
    protected $table = 'dental_appointment_billings';

    protected $fillable = [
        'appointment_id',
        'consultation_fee',
        'treatment_amount',
        'discount',
        'grand_total',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'remarks',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'treatment_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'billing_id');
    }
}
