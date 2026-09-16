<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentExamination extends Model
{
    protected $table = 'dental_appointment_examinations';

    protected $fillable = [
        'appointment_id',
        'symptoms',
        'diagnosis',
        'observations',
        'doctor_notes',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
