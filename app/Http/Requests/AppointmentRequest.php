<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Legacy appointment request used by PatientAppointmentController.
 *
 * Updated to reference dental_* tables only.
 * Old references removed: branches, patients (old table), doctor_profiles, inquiries, appointments (old).
 */
class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'             => ['nullable', 'exists:dental_patients,id'],
            'doctor_id'              => ['nullable', 'exists:dental_doctors,id'],
            'chair_id'               => ['nullable', 'exists:dental_chairs,id'],
            'appointment_type_id'    => ['nullable', 'exists:dental_appointment_types,id'],
            'previous_appointment_id' => ['nullable', 'exists:dental_appointments,id'],
            'appointment_date'       => ['required', 'date'],
            'appointment_time'       => ['nullable'],
            'visit_type'             => ['nullable', 'string', 'max:50'],
            'priority'               => ['nullable', 'string', 'in:normal,high,emergency'],
            'status'                 => ['nullable', 'string', 'max:50'],
            'notes'                  => ['nullable', 'string'],
            // Nested billing fields
            'billing'                    => ['nullable', 'array'],
            'billing.discount'           => ['nullable', 'numeric', 'min:0'],
            'billing.paid_amount'        => ['nullable', 'numeric', 'min:0'],
            'billing.payment_status'     => ['nullable', 'string', 'in:unpaid,partial,paid'],
        ];
    }
}
