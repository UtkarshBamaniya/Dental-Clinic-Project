<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    protected $table = 'dental_patient_medical_histories';

    protected $fillable = [
        'patient_id',
        'blood_group',
        'current_medicine',
        'previous_dental_treatment',
        'other_notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
