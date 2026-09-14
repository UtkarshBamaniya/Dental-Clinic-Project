<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'                  => ['required', 'exists:branches,id'],
            'patient_id'                 => ['nullable', 'exists:patients,id'],
            'doctor_profile_id'          => ['nullable', 'exists:doctor_profiles,id'],
            'inquiry_id'                 => ['nullable', 'exists:inquiries,id'],
            'appointment_type_id'        => ['nullable', 'exists:appointment_types,id'],
            'parent_appointment_id'      => ['nullable', 'exists:appointments,id'],
            'patient_name'               => ['required', 'string', 'max:255'],
            'phone'                      => ['required', 'string', 'max:20'],
            'email'                      => ['nullable', 'email', 'max:255'],
            'appointment_date'           => ['required', 'date'],
            'start_time'                 => ['required'],
            'end_time'                   => ['required'],
            'specialty'                  => ['required', 'string', 'max:100'],
            'treatment_name'             => ['required', 'string', 'max:255'],
            'status'                     => ['required', 'string', 'max:50'],
            'notes'                      => ['nullable', 'string'],
            // Nested billing fields
            'billing'                    => ['nullable', 'array'],
            'billing.estimated_amount'   => ['nullable', 'numeric', 'min:0'],
            'billing.paid_amount'        => ['nullable', 'numeric', 'min:0'],
            'billing.discount'           => ['nullable', 'numeric', 'min:0'],
            'billing.payment_status'     => ['nullable', 'string', 'in:unpaid,partial,paid'],
        ];
    }
}
