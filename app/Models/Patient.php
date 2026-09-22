<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dental_patients';

    protected $fillable = [
        'patient_code',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'mobile',
        'alternate_mobile',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'occupation',
        'referred_by',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Computed full name from first, middle, and last name parts.
     * Middle name is omitted when null or empty.
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            collect([$this->first_name, $this->middle_name, $this->last_name])
                ->filter()
                ->implode(' ')
        );
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function toothRecords(): HasMany
    {
        return $this->hasMany(PatientToothRecord::class);
    }
}
