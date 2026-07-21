<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Inquiry;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inquiries/Index', [
            'inquiries' => Inquiry::query()
                ->with(['branch', 'assignee', 'patient'])
                ->latest()
                ->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()
                ->whereIn('role', ['super_admin', 'branch_admin', 'receptionist'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'sources' => ['Walk-in', 'Website', 'WhatsApp', 'Instagram', 'Referral', 'Call'],
        ]);
    }

    public function store()
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['required', 'string', 'max:100'],
            'treatment_interest' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'priority' => ['required', 'string', 'max:50'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Inquiry::query()->create($validated);

        return redirect()->route('inquiries.index')->with('success', 'Inquiry added.');
    }

    public function markConverted(Inquiry $inquiry)
    {
        $inquiry->update([
            'status' => 'converted',
        ]);

        return redirect()->route('inquiries.index')->with('success', 'Inquiry marked as converted.');
    }
}
