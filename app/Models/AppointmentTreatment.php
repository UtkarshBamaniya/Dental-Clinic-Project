<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentTreatment extends Model
{
    protected $table = 'dental_appointment_treatments';

    protected $fillable = [
        'appointment_id',
        'treatment_id',
        'tooth_no',
        'quantity',
        'unit_price',
        'total_amount',
        'status',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class, 'treatment_id');
    }
}
