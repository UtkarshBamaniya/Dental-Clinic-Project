<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for a PatientMedicalHistory record.
 */
class PatientMedicalHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'blood_group'               => $this->blood_group,
            'current_medicine'          => $this->current_medicine,
            'previous_dental_treatment' => $this->previous_dental_treatment,
            'other_notes'               => $this->other_notes,
            'updated_at'                => $this->updated_at?->toDateTimeString(),
        ];
    }
}
