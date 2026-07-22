<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
