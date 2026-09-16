<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tooth extends Model
{
    protected $table = 'dental_teeth';

    protected $fillable = [
        'tooth_no',
        'tooth_name',
        'tooth_type',
        'quadrant',
    ];

    public function patientToothRecords()
    {
        return $this->hasMany(PatientToothRecord::class);
    }
}
