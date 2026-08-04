<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'     => ['required', 'exists:branches,id'],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'gender'        => ['required', 'in:Male,Female,Other'],
            'date_of_birth' => ['nullable', 'date'],
            'blood_group'   => ['nullable', 'string', 'max:10'],
            'address'       => ['nullable', 'string'],
            'allergies'     => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
        ];
    }
}
