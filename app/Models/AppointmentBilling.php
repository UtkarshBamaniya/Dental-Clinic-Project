<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'estimated_amount',
        'paid_amount',
        'discount',
        'payment_status',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
