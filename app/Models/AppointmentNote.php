<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentNote extends Model
{
    protected $table = 'dental_appointment_notes';

    protected $fillable = [
        'appointment_id',
        'note_type',
        'notes',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
