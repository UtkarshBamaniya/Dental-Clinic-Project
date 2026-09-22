<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for a single Appointment.
 *
 * Used for both the list endpoint (lightweight) and the detail endpoint (full).
 *
 * Relationships are included only when they have been eager-loaded,
 * so the same resource class works efficiently for both contexts:
 *
 *   List:   patient, doctor, chair, appointmentType, billing
 *   Detail: all of the above + examination, treatments, paymentTransactions,
 *           notes, prescriptions, previousAppointment, followUpAppointments
 */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'appointment_no'   => $this->appointment_no,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'appointment_time' => $this->formatTime($this->appointment_time),
            'visit_type'       => $this->visit_type,
            'chief_complaint'  => $this->chief_complaint,
            'problem_area'     => $this->problem_area,
            'tooth_no'         => $this->tooth_no,
            'priority'         => $this->priority,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),

            // ── Related: Patient ──────────────────────────────────────────────
            'patient' => $this->whenLoaded('patient', function () {
                $p = $this->patient;
                return $p ? [
                    'id'           => $p->id,
                    'patient_code' => $p->patient_code,
                    'full_name'    => $this->buildFullName($p->first_name, $p->middle_name, $p->last_name),
                    'mobile'       => $p->mobile,
                    'gender'       => $p->gender,
                ] : null;
            }),

            // ── Related: Doctor ───────────────────────────────────────────────
            'doctor' => $this->whenLoaded('doctor', function () {
                $d = $this->doctor;
                return $d ? [
                    'id'          => $d->id,
                    'doctor_code' => $d->doctor_code,
                    'full_name'   => $this->buildDoctorName($d->first_name, $d->last_name),
                ] : null;
            }),

            // ── Related: Chair ────────────────────────────────────────────────
            'chair' => $this->whenLoaded('chair', function () {
                $c = $this->chair;
                return $c ? [
                    'id'           => $c->id,
                    'chair_name'   => $c->chair_name,
                    'chair_number' => $c->chair_number,
                ] : null;
            }),

            // ── Related: Appointment Type ─────────────────────────────────────
            'appointment_type' => $this->whenLoaded('appointmentType', function () {
                $t = $this->appointmentType;
                return $t ? [
                    'id'   => $t->id,
                    'name' => $t->name,
                ] : null;
            }),

            // ── Related: Billing (list + detail) ─────────────────────────────
            'billing' => $this->whenLoaded('billing', function () {
                $b = $this->billing;
                return $b ? [
                    'id'               => $b->id,
                    'consultation_fee' => (float) $b->consultation_fee,
                    'treatment_amount' => (float) $b->treatment_amount,
                    'discount'         => (float) $b->discount,
                    'grand_total'      => (float) $b->grand_total,
                    'paid_amount'      => (float) $b->paid_amount,
                    'balance_amount'   => (float) $b->balance_amount,
                    'payment_status'   => $b->payment_status,
                    'remarks'          => $b->remarks,
                ] : null;
            }),

            // ── Related: Examination (detail only) ───────────────────────────
            'examination' => $this->whenLoaded('examination', function () {
                $e = $this->examination;
                return $e ? [
                    'id'           => $e->id,
                    'symptoms'     => $e->symptoms,
                    'diagnosis'    => $e->diagnosis,
                    'observations' => $e->observations,
                    'doctor_notes' => $e->doctor_notes,
                ] : null;
            }),

            // ── Related: Treatments (detail only) ────────────────────────────
            'treatments' => $this->whenLoaded('treatments', function () {
                return $this->treatments->map(function ($at) {
                    return [
                        'id'           => $at->id,
                        'treatment_id' => $at->treatment_id,
                        'treatment'    => $at->relationLoaded('treatment') && $at->treatment ? [
                            'id'   => $at->treatment->id,
                            'name' => $at->treatment->name,
                        ] : null,
                        'tooth_no'     => $at->tooth_no,
                        'quantity'     => (int) $at->quantity,
                        'unit_price'   => (float) $at->unit_price,
                        'total_amount' => (float) $at->total_amount,
                        'status'       => $at->status,
                        'remarks'      => $at->remarks,
                    ];
                });
            }),

            // ── Related: Payment Transactions (detail only) ───────────────────
            'payment_transactions' => $this->whenLoaded('paymentTransactions', function () {
                return $this->paymentTransactions->map(function ($pt) {
                    return [
                        'id'                    => $pt->id,
                        'payment_date'          => $pt->payment_date?->toDateString(),
                        'amount'                => (float) $pt->amount,
                        'payment_mode'          => $pt->payment_mode,
                        'transaction_reference' => $pt->transaction_reference,
                        'remarks'               => $pt->remarks,
                    ];
                });
            }),

            // ── Related: Notes (detail only) ─────────────────────────────────
            // NOTE: Relationship is named appointmentNotes() (not notes()) on the
            // Appointment model to avoid collision with the notes text column.
            'appointment_notes' => $this->whenLoaded('appointmentNotes', function () {
                return $this->getRelation('appointmentNotes')->map(function ($n) {
                    return [
                        'id'        => $n->id,
                        'note_type' => $n->note_type,
                        'notes'     => $n->notes,
                    ];
                });
            }),

            // ── Related: Prescriptions (detail only) ─────────────────────────
            'prescriptions' => $this->whenLoaded('prescriptions', function () {
                return $this->prescriptions->map(function ($rx) {
                    return [
                        'id'            => $rx->id,
                        'medicine_name' => $rx->medicine_name,
                        'dosage'        => $rx->dosage,
                        'frequency'     => $rx->frequency,
                        'duration'      => $rx->duration,
                        'instructions'  => $rx->instructions,
                    ];
                });
            }),

            // ── Related: Previous Appointment (shallow, detail only) ──────────
            'previous_appointment' => $this->whenLoaded('previousAppointment', function () {
                $prev = $this->previousAppointment;
                return $prev ? [
                    'id'               => $prev->id,
                    'appointment_no'   => $prev->appointment_no,
                    'appointment_date' => $prev->appointment_date?->toDateString(),
                    'status'           => $prev->status,
                ] : null;
            }),

            // ── Related: Follow-up Appointments (shallow, detail only) ────────
            'follow_up_appointments' => $this->whenLoaded('followUpAppointments', function () {
                return $this->followUpAppointments->map(function ($fu) {
                    return [
                        'id'               => $fu->id,
                        'appointment_no'   => $fu->appointment_no,
                        'appointment_date' => $fu->appointment_date?->toDateString(),
                        'status'           => $fu->status,
                    ];
                });
            }),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build patient full name without extra spaces.
     */
    private function buildFullName(?string $first, ?string $middle, ?string $last): string
    {
        return trim(
            collect([$first, $middle, $last])
                ->filter()
                ->implode(' ')
        );
    }

    /**
     * Build doctor display name: "Dr. Priya Mehta".
     */
    private function buildDoctorName(?string $first, ?string $last): string
    {
        $name = trim(collect([$first, $last])->filter()->implode(' '));
        return $name ? 'Dr. ' . $name : '';
    }

    /**
     * Normalise appointment_time to H:i:s string.
     * The model casts it as a datetime; we just want the time portion.
     */
    private function formatTime(mixed $time): ?string
    {
        if ($time === null) {
            return null;
        }
        // If already a Carbon/DateTime, format it
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        return (string) $time;
    }
}
