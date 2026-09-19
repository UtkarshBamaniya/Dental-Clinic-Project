<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dental_appointments';

    protected $fillable = [
        'appointment_no',
        'patient_id',
        'doctor_id',
        'chair_id',
        'appointment_type_id',
        'appointment_date',
        'appointment_time',
        'visit_type',
        'chief_complaint',
        'problem_area',
        'tooth_no',
        'priority',
        'status',
        'previous_appointment_id',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i:s',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function chair()
    {
        return $this->belongsTo(Chair::class, 'chair_id');
    }

    public function appointmentType()
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function previousAppointment()
    {
        return $this->belongsTo(Appointment::class, 'previous_appointment_id');
    }

    public function followUpAppointments()
    {
        return $this->hasMany(Appointment::class, 'previous_appointment_id');
    }

    public function examination()
    {
        return $this->hasOne(AppointmentExamination::class);
    }

    public function treatments()
    {
        return $this->hasMany(AppointmentTreatment::class);
    }

    public function billing()
    {
        return $this->hasOne(AppointmentBilling::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function notes()
    {
        return $this->hasMany(AppointmentNote::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
