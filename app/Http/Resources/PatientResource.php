<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for a single Patient.
 *
 * Computes full_name from first/middle/last name without extra DB columns.
 * Medical history is conditionally included when the relationship is loaded.
 */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'patient_code'     => $this->patient_code,
            'first_name'       => $this->first_name,
            'middle_name'      => $this->middle_name,
            'last_name'        => $this->last_name,
            'full_name'        => $this->getFullName(),
            'gender'           => $this->gender,
            'date_of_birth'    => $this->date_of_birth?->toDateString(),
            'mobile'           => $this->mobile,
            'alternate_mobile' => $this->alternate_mobile,
            'email'            => $this->email,
            'address'          => $this->address,
            'city'             => $this->city,
            'state'            => $this->state,
            'pincode'          => $this->pincode,
            'occupation'       => $this->occupation,
            'referred_by'      => $this->referred_by,
            'status'           => $this->status,
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),

            // Only included when the relationship has been eager-loaded
            'medical_history'  => $this->whenLoaded(
                'medicalHistory',
                fn () => $this->medicalHistory
                    ? new PatientMedicalHistoryResource($this->medicalHistory)
                    : null
            ),
        ];
    }

    /**
     * Build a clean full name from available name parts.
     * Trims extra spaces even when middle_name is null.
     */
    private function getFullName(): string
    {
        return trim(
            collect([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ])
            ->filter()          // drop null / empty
            ->implode(' ')
        );
    }
}
