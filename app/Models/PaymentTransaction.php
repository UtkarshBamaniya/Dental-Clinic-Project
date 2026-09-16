<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'dental_payment_transactions';

    protected $fillable = [
        'appointment_id',
        'billing_id',
        'payment_date',
        'amount',
        'payment_mode',
        'transaction_reference',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function billing()
    {
        return $this->belongsTo(AppointmentBilling::class, 'billing_id');
    }
}
