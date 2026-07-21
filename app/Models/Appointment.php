<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'patient_id',
        'doctor_profile_id',
        'booked_by',
        'appointment_date',
        'start_time',
        'end_time',
        'specialty',
        'treatment_name',
        'status',
        'visit_type',
        'token_no',
        'estimated_amount',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
