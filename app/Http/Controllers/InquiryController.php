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
        $fromDate = request('from_date');
        $toDate   = request('to_date');

        $query = Inquiry::query()
            ->with(['branch', 'assignee', 'patient'])
            ->latest();

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return Inertia::render('Inquiries/Index', [
            'inquiries' => $query->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()
                ->whereIn('role', ['super_admin', 'branch_admin', 'receptionist'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'sources' => ['Walk-in', 'Website', 'WhatsApp', 'Instagram', 'Referral', 'Call'],
            'filters' => [
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ],
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

    public function update(Inquiry $inquiry)
    {
        $validated = request()->validate([
            'branch_id'          => ['required', 'exists:branches,id'],
            'assigned_to'        => ['nullable', 'exists:users,id'],
            'name'               => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:255'],
            'source'             => ['required', 'string', 'max:100'],
            'treatment_interest' => ['required', 'string', 'max:255'],
            'status'             => ['required', 'string', 'max:50'],
            'priority'           => ['required', 'string', 'max:50'],
            'next_follow_up_at'  => ['nullable', 'date'],
            'notes'              => ['nullable', 'string'],
        ]);

        $inquiry->update($validated);

        return redirect()->route('inquiries.index')->with('success', 'Inquiry updated.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('inquiries.index')->with('success', 'Inquiry deleted.');
    }
}
