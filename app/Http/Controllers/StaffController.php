<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Masters/Users/Index', [
            'users'       => User::query()->with(['roleRecord', 'doctor'])->orderBy('name')->get(),
            'roles'       => Role::query()->orderBy('name')->get(['id', 'code', 'name', 'is_system']),
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'branch_id'      => ['nullable', 'integer'],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'role_id'        => ['required', 'exists:roles,id'],
            'job_title'      => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric'],
            'specialty'      => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            ...$validated,
            'password' => 'password',
            'status'   => true,
        ]);

        $this->syncDoctor($user, $validated['specialty'] ?? null);

        return redirect()->route('masters.users.index')->with('success', 'Staff member created with default password: password');
    }

    public function update(User $user): RedirectResponse
    {
        $validated = request()->validate([
            'branch_id'      => ['nullable', 'integer'],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'          => ['nullable', 'string', 'max:20'],
            'role_id'        => ['required', 'exists:roles,id'],
            'job_title'      => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric'],
            'specialty'      => ['nullable', 'string', 'max:100'],
        ]);

        $user->update([
            ...$validated,
        ]);

        $this->syncDoctor($user->refresh(), $validated['specialty'] ?? null);

        return redirect()->route('masters.users.index')->with('success', 'Staff member updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('masters.users.index')->with('success', 'Staff member archived.');
    }

    /**
     * Sync the dental_doctors record when a user has the 'doctor' role.
     * Uses the new dental_doctors table (Doctor model), not the removed doctor_profiles.
     */
    protected function syncDoctor(User $user, ?string $specialization): void
    {
        $role = Role::query()->find($user->role_id);

        if (! $role || $role->code !== 'doctor') {
            return;
        }

        $seq = Doctor::query()->withTrashed()->count() + 1;

        Doctor::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'doctor_code'      => 'DOC-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'first_name'       => explode(' ', $user->name)[0] ?? $user->name,
                'last_name'        => implode(' ', array_slice(explode(' ', $user->name), 1)) ?: null,
                'mobile'           => $user->phone ?? '',
                'email'            => $user->email,
                'specialization'   => $specialization ?: 'General Dentistry',
                'consultation_fee' => 600,
                'status'           => 'active',
            ],
        );
    }
}
