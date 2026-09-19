<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Core appointment fields ────────────────────────────────────────
            // patient_id: not changeable through normal update
            // appointment_no: never changeable

            'doctor_id'               => ['nullable', 'integer', 'exists:dental_doctors,id'],
            'chair_id'                => ['nullable', 'integer', 'exists:dental_chairs,id'],
            'appointment_type_id'     => ['nullable', 'integer', 'exists:dental_appointment_types,id'],
            'appointment_date'        => ['sometimes', 'date'],
            'appointment_time'        => ['sometimes', 'date_format:H:i,H:i:s'],
            'visit_type'              => ['sometimes', 'string', Rule::in(['First Visit', 'Follow-up', 'Emergency'])],
            'chief_complaint'         => ['nullable', 'string', 'max:2000'],
            'problem_area'            => ['nullable', 'string', 'max:100'],
            'tooth_no'                => ['nullable', 'string', 'max:100'],
            'priority'                => ['nullable', 'string', Rule::in(['normal', 'high', 'emergency'])],
            'status'                  => ['nullable', 'string', Rule::in([
                'scheduled', 'confirmed', 'waiting', 'in_progress',
                'completed', 'cancelled', 'no_show', 'rescheduled',
            ])],
            'notes'                   => ['nullable', 'string', 'max:5000'],

            // ── Examination (optional block) ───────────────────────────────────
            'examination'             => ['nullable', 'array'],
            'examination.symptoms'    => ['nullable', 'string', 'max:2000'],
            'examination.diagnosis'   => ['nullable', 'string', 'max:2000'],
            'examination.observations'=> ['nullable', 'string', 'max:2000'],
            'examination.doctor_notes'=> ['nullable', 'string', 'max:2000'],

            // ── Treatments (optional array) ────────────────────────────────────
            'treatments'                   => ['nullable', 'array'],
            'treatments.*.treatment_id'    => ['required_with:treatments', 'integer', 'exists:dental_treatments,id'],
            'treatments.*.tooth_no'        => ['nullable', 'string', 'max:10'],
            'treatments.*.quantity'        => ['nullable', 'integer', 'min:1'],
            'treatments.*.unit_price'      => ['nullable', 'numeric', 'min:0'],
            // total_amount is intentionally NOT trusted from the frontend
            'treatments.*.status'          => ['nullable', 'string', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'treatments.*.remarks'         => ['nullable', 'string', 'max:1000'],

            // ── Billing overrides (optional) ───────────────────────────────────
            // grand_total, paid_amount, balance_amount: NOT accepted — computed server-side
            'billing'                 => ['nullable', 'array'],
            'billing.consultation_fee'=> ['nullable', 'numeric', 'min:0'],
            'consultation_fee'        => ['nullable', 'numeric', 'min:0'], // top-level alias
            'billing.discount'        => ['nullable', 'numeric', 'min:0'],
            'billing.remarks'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.date'       => 'Appointment date must be a valid date.',
            'appointment_time.date_format'=> 'Appointment time must be in HH:MM or HH:MM:SS format.',
            'visit_type.in'               => 'Visit type must be one of: First Visit, Follow-up, Emergency.',
            'priority.in'                 => 'Priority must be one of: normal, high, emergency.',
            'status.in'                   => 'Status must be one of the allowed appointment statuses.',
            'payment_mode.in'             => 'Payment mode must be: cash, upi, card, or bank_transfer.',
        ];
    }
}
