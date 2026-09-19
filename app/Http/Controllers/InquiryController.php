<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Inquiry controller (LEGACY)
 *
 * The inquiries table has been removed from the database.
 * This controller has been neutralized to prevent crashes.
 */
class InquiryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inquiries/Index', [
            'inquiries' => [],
            'staff'     => [],
            'sources'   => [],
            'filters'   => [
                'from_date' => null,
                'to_date'   => null,
            ],
        ]);
    }

    public function store()
    {
        return redirect()->route('inquiries.index')->with('success', 'Legacy inquiry module is disabled.');
    }

    public function markConverted($id)
    {
        return redirect()->route('inquiries.index')->with('success', 'Legacy inquiry module is disabled.');
    }

    public function update($id)
    {
        return redirect()->route('inquiries.index')->with('success', 'Legacy inquiry module is disabled.');
    }

    public function destroy($id)
    {
        return redirect()->route('inquiries.index')->with('success', 'Legacy inquiry module is disabled.');
    }
}

