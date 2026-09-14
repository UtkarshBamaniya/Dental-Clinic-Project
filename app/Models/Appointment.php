<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'patient_id',
        'doctor_profile_id',
        'booked_by',
        'appointment_type_id',
        'parent_appointment_id',
        'appointment_date',
        'start_time',
        'end_time',
        'specialty',
        'treatment_name',
        'status',
        'notes',
    ];

    protected $casts = [
        //
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function billing(): HasOne
    {
        return $this->hasOne(AppointmentBilling::class);
    }

    public function parentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_appointment_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(Appointment::class, 'parent_appointment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
