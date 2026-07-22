<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DoctorProfile;
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
            'users' => User::query()->with(['branch', 'roleRecord', 'doctorProfile'])->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'code', 'name', 'is_system']),
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric'],
            'specialty' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            ...$validated,
            'password' => 'password',
            'status' => true,
        ]);

        $this->syncDoctorProfile($user, $validated['specialty'] ?? null);

        return redirect()->route('masters.users.index')->with('success', 'Staff member created with default password: password');
    }

    public function update(User $user): RedirectResponse
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric'],
            'specialty' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update([
            ...$validated,
        ]);

        $this->syncDoctorProfile($user->refresh(), $validated['specialty'] ?? null);

        return redirect()->route('masters.users.index')->with('success', 'Staff member updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('masters.users.index')->with('success', 'Staff member archived.');
    }

    protected function syncDoctorProfile(User $user, ?string $specialty): void
    {
        $role = Role::query()->find($user->role_id);

        if (! $role || $role->code !== 'doctor') {
            return;
        }

        DoctorProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'branch_id' => $user->branch_id,
                'specialty' => $specialty ?: 'General Dentistry',
                'room_number' => 'R-'.str_pad((string) ($user->id % 10 ?: 1), 2, '0', STR_PAD_LEFT),
                'consultation_fee' => 600,
                'theme_color' => '#0f766e',
                'is_available' => true,
            ],
        );
    }
}
