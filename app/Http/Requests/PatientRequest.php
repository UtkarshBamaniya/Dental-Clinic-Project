<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Patient form request updated for the new dental_patients architecture.
 *
 * Old reference removed: exists:branches,id (branches table removed).
 * New patient model fields: first_name, last_name, mobile, gender, etc.
 */
class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['nullable', 'string', 'max:100'],
            'gender'        => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'mobile'        => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'address'       => ['nullable', 'string'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'pincode'       => ['nullable', 'string', 'max:10'],
            'occupation'    => ['nullable', 'string', 'max:100'],
            'referred_by'   => ['nullable', 'string', 'max:255'],
            'status'        => ['nullable', 'in:active,inactive'],
        ];
    }
}
