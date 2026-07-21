<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Staff/Index', [
            'staff' => User::query()->with('branch')->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'roles' => ['super_admin', 'branch_admin', 'receptionist', 'doctor', 'hr', 'accountant'],
            'specialties' => ['Orthodontics', 'Root Canal', 'Implants', 'Pediatric Dentistry', 'Cosmetic Dentistry', 'General Dentistry'],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric'],
            'specialty' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            ...$validated,
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        if ($validated['role'] === 'doctor') {
            DoctorProfile::query()->create([
                'user_id' => $user->id,
                'branch_id' => $validated['branch_id'],
                'specialty' => $validated['specialty'] ?? 'General Dentistry',
                'room_number' => 'R-'.str_pad((string) ($user->id % 10 ?: 1), 2, '0', STR_PAD_LEFT),
                'consultation_fee' => 600,
                'theme_color' => '#0f766e',
                'is_available' => true,
            ]);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member created with default password: password');
    }
}
