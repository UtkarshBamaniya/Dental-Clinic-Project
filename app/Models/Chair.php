<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chair extends Model
{
    protected $table = 'dental_chairs';

    protected $fillable = [
        'chair_name',
        'chair_number',
        'status',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
