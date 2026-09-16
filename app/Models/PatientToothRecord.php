<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientToothRecord extends Model
{
    protected $table = 'dental_patient_tooth_records';

    protected $fillable = [
        'patient_id',
        'tooth_id',
        'condition',
        'status',
        'notes',
        'recorded_date',
    ];

    protected $casts = [
        'recorded_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function tooth()
    {
        return $this->belongsTo(Tooth::class, 'tooth_id');
    }
}
