<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleMasterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Masters/Roles/Index', [
            'roles' => Role::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'code' => ['required', 'string', 'max:50', 'unique:roles,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Role::query()->create([
            ...$validated,
            'is_system' => false,
        ]);

        return redirect()->route('masters.roles.index')->with('success', 'Role created.');
    }

    public function update(Role $role): RedirectResponse
    {
        $validated = request()->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('roles', 'code')->ignore($role->id)],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($role->is_system) {
            $validated['code'] = $role->code;
        }

        $role->update($validated);

        return redirect()->route('masters.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be removed.');

        $role->delete();

        return redirect()->route('masters.roles.index')->with('success', 'Role archived.');
    }
}
