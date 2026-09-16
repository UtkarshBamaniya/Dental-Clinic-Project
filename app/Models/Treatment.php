<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends Model
{
    use SoftDeletes;

    protected $table = 'dental_treatments';

    protected $fillable = [
        'treatment_code',
        'name',
        'description',
        'default_price',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function appointmentTreatments()
    {
        return $this->hasMany(AppointmentTreatment::class);
    }
}
