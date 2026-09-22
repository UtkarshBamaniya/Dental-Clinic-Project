<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates query parameters for the Appointment Master list endpoint.
 *
 * GET /appointments?search=&date=&date_from=&date_to=&doctor_id=&chair_id=
 *     &appointment_type_id=&visit_type=&priority=&status=&payment_status=
 *     &sort_by=&sort_order=&per_page=&page=
 */
class AppointmentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Text search ───────────────────────────────────────────────────
            'search'              => ['nullable', 'string', 'max:255'],

            // ── Date filters ──────────────────────────────────────────────────
            'date'                => ['nullable', 'date'],
            'date_from'           => ['nullable', 'date'],
            'date_to'             => ['nullable', 'date', 'after_or_equal:date_from'],

            // ── Relationship filters ──────────────────────────────────────────
            'doctor_id'           => ['nullable', 'integer', 'exists:dental_doctors,id'],
            'chair_id'            => ['nullable', 'integer', 'exists:dental_chairs,id'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:dental_appointment_types,id'],

            // ── Enumerated filters ────────────────────────────────────────────
            'visit_type'     => ['nullable', 'string', Rule::in(['First Visit', 'Follow-up', 'Emergency'])],
            'priority'       => ['nullable', 'string', Rule::in(['normal', 'high', 'emergency'])],
            'status'         => ['nullable', 'string', Rule::in([
                'scheduled', 'confirmed', 'waiting', 'in_progress',
                'completed', 'cancelled', 'no_show', 'rescheduled',
            ])],
            'payment_status' => ['nullable', 'string', Rule::in(['unpaid', 'partial', 'paid'])],

            // ── Sorting ───────────────────────────────────────────────────────
            'sort_by'    => ['nullable', 'string', Rule::in([
                'appointment_no', 'appointment_date', 'appointment_time',
                'created_at', 'updated_at',
            ])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],

            // ── Pagination ────────────────────────────────────────────────────
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date'              => 'The date must be a valid date (Y-m-d).',
            'date_from.date'         => 'The from date must be a valid date (Y-m-d).',
            'date_to.date'           => 'The to date must be a valid date (Y-m-d).',
            'date_to.after_or_equal' => 'The to date must be on or after the from date.',
            'doctor_id.exists'       => 'The selected doctor does not exist.',
            'chair_id.exists'        => 'The selected chair does not exist.',
            'appointment_type_id.exists' => 'The selected appointment type does not exist.',
            'visit_type.in'          => 'Visit type must be: First Visit, Follow-up, or Emergency.',
            'priority.in'            => 'Priority must be: normal, high, or emergency.',
            'status.in'              => 'Status must be one of the allowed appointment statuses.',
            'payment_status.in'      => 'Payment status must be: unpaid, partial, or paid.',
            'sort_by.in'             => 'Sort field must be one of: appointment_no, appointment_date, appointment_time, created_at, updated_at.',
            'sort_order.in'          => 'Sort order must be asc or desc.',
            'per_page.max'           => 'Results per page cannot exceed 100.',
        ];
    }
}
