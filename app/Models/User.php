<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\PayrollRecord;
use App\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'role_id',
        'job_title',
        'status',
        'monthly_salary',
        'branch_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id')->withTrashed();
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'branch_admin'], true);
    }

    public function isAccountsTeam(): bool
    {
        return in_array($this->role, ['super_admin', 'branch_admin', 'accountant', 'hr'], true);
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function getRoleAttribute($value): ?string
    {
        return $this->roleRecord?->code ?? $value;
    }

    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = $value;

        if ($value === null || $value === '') {
            return;
        }

        $role = Role::query()->withTrashed()->where('code', $value)->first();

        if ($role) {
            $this->attributes['role_id'] = $role->id;
        }
    }

    public function setRoleIdAttribute($value): void
    {
        $this->attributes['role_id'] = $value;

        if ($value === null || $value === '') {
            return;
        }

        $role = Role::query()->withTrashed()->find($value);

        if ($role) {
            $this->attributes['role'] = $role->code;
        }
    }
}
