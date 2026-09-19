<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dental_doctors';

    protected $fillable = [
        'user_id',
        'doctor_code',
        'first_name',
        'last_name',
        'mobile',
        'email',
        'specialization',
        'consultation_fee',
        'status',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
