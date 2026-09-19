<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function medicalHistory()
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function toothRecords()
    {
        return $this->hasMany(PatientToothRecord::class);
    }
}
