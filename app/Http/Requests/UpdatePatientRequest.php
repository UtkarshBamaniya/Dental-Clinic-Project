<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a patient update request.
 *
 * Note: patient_code and id are intentionally NOT listed here.
 * PatientService::updatePatient() will strip them even if submitted,
 * preventing the frontend from ever overwriting the auto-generated code.
 *
 * Duplicate-mobile protection is performed inside PatientService, not here,
 * because it requires knowing the current patient's own mobile number and
 * therefore needs access to the Patient model — which belongs in the service.
 */
class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Core patient fields ───────────────────────────────────────────
            'first_name'       => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'last_name'        => ['nullable', 'string', 'max:100'],
            'gender'           => ['nullable', 'string', 'max:20'],
            'date_of_birth'    => ['nullable', 'date', 'before_or_equal:today'],
            'mobile'           => ['sometimes', 'required', 'string', 'max:20'],
            'alternate_mobile' => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:150'],
            'address'          => ['nullable', 'string'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'pincode'          => ['nullable', 'string', 'max:20'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'referred_by'      => ['nullable', 'string', 'max:150'],
            'status'           => ['nullable', 'string', 'max:20'],

            // ── Medical history (nested, optional) ───────────────────────────
            // If the key is present, we do an updateOrCreate on the history row.
            // If the key is absent, we leave existing history untouched.
            'medical_history'                          => ['nullable', 'array'],
            'medical_history.blood_group'              => ['nullable', 'string', 'max:10'],
            'medical_history.current_medicine'         => ['nullable', 'string'],
            'medical_history.previous_dental_treatment'=> ['nullable', 'string'],
            'medical_history.other_notes'              => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'                               => 'first name',
            'middle_name'                              => 'middle name',
            'last_name'                                => 'last name',
            'date_of_birth'                            => 'date of birth',
            'alternate_mobile'                         => 'alternate mobile',
            'referred_by'                              => 'referred by',
            'medical_history.blood_group'              => 'blood group',
            'medical_history.current_medicine'         => 'current medicine',
            'medical_history.previous_dental_treatment'=> 'previous dental treatment',
            'medical_history.other_notes'              => 'other notes',
        ];
    }
}
