<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const props = defineProps({
    branches: Array,
    patients: Array,
    doctorOptions: Array,
    specialties: Array,
    bookingDraft: Object,
});

const emit = defineEmits(['saved']);

const showCreateModal = ref(Boolean(props.bookingDraft));
const form = useForm({
    inquiry_id: props.bookingDraft?.inquiry_id ?? null,
    branch_id: props.bookingDraft?.branch_id ?? props.branches[0]?.id ?? null,
    patient_id: props.bookingDraft?.patient_id ?? null,
    patient_name: props.bookingDraft?.patient_name ?? '',
    phone: props.bookingDraft?.phone ?? '',
    email: props.bookingDraft?.email ?? '',
    doctor_profile_id: null,
    appointment_date: '',
    start_time: '10:00',
    end_time: '10:30',
    specialty: props.specialties?.includes(props.bookingDraft?.specialty)
        ? props.bookingDraft.specialty
        : props.specialties?.[0] ?? 'Orthodontics',
    treatment_name: props.bookingDraft?.treatment_name ?? '',
    status: 'booked',
    visit_type: 'consultation',
    estimated_amount: 0,
    paid_amount: 0,
    notes: props.bookingDraft?.notes ?? '',
});

function resetForm() {
    form.reset('appointment_date', 'treatment_name', 'notes', 'patient_name', 'phone', 'email', 'inquiry_id', 'patient_id', 'doctor_profile_id');
    form.clearErrors();
    form.branch_id = props.bookingDraft?.branch_id ?? props.branches[0]?.id ?? null;
    form.patient_id = props.bookingDraft?.patient_id ?? null;
    form.patient_name = props.bookingDraft?.patient_name ?? '';
    form.phone = props.bookingDraft?.phone ?? '';
    form.email = props.bookingDraft?.email ?? '';
    form.inquiry_id = props.bookingDraft?.inquiry_id ?? null;
    form.specialty = props.specialties?.includes(props.bookingDraft?.specialty)
        ? props.bookingDraft.specialty
        : props.specialties?.[0] ?? 'Orthodontics';
    form.treatment_name = props.bookingDraft?.treatment_name ?? '';
    form.notes = props.bookingDraft?.notes ?? '';
    form.start_time = '10:00';
    form.end_time = '10:30';
    form.status = 'booked';
    form.visit_type = 'consultation';
    form.estimated_amount = 0;
    form.paid_amount = 0;
}

function openCreateModal() {
    resetForm();
    showCreateModal.value = true;
}

function submit() {
    form.post(route('appointments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
            emit('saved');
        },
    });
}

// ── Edit modal ────────────────────────────────────────────────────────────────
const showEditModal = ref(false);
const editingAppointment = ref(null);
const editForm = useForm({
    branch_id: null, patient_id: null, doctor_profile_id: null,
    appointment_date: '', start_time: '', end_time: '',
    specialty: '', treatment_name: '', status: '', visit_type: '',
    estimated_amount: 0, paid_amount: 0, notes: '',
});

function openEditModal(apt) {
    editingAppointment.value = apt;
    editForm.branch_id = apt.branch_id;
    editForm.patient_id = apt.patient_id;
    editForm.doctor_profile_id = apt.doctor_profile_id;
    editForm.appointment_date = apt.appointment_date;
    editForm.start_time = apt.start_time;
    editForm.end_time = apt.end_time;
    editForm.specialty = apt.specialty;
    editForm.treatment_name = apt.treatment_name;
    editForm.status = apt.status;
    editForm.visit_type = apt.visit_type;
    editForm.estimated_amount = Number(apt.estimated_amount ?? 0);
    editForm.paid_amount = Number(apt.paid_amount ?? 0);
    editForm.notes = apt.notes ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    editForm.put(route('appointments.update', editingAppointment.value.id), {
        preserveScroll: true,
        onSuccess: () => { 
            showEditModal.value = false; 
            editingAppointment.value = null; 
            emit('saved'); 
        },
    });
}

defineExpose({ openCreateModal, openEditModal });
</script>

<template>
    <!-- ─── Create Dialog ────────────────────────────────────────────────── -->
    <Dialog v-model:visible="showCreateModal" modal header="Add Appointment" :style="{ width: '62rem' }">
        <form class="form-grid" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Branch<span class="field-label__required">*</span></label>
                    <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                    <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Existing Patient</label>
                    <Select v-model="form.patient_id" :options="patients" optionLabel="name" optionValue="id" showClear />
                    <small v-if="form.errors.patient_id" class="field-error">{{ form.errors.patient_id }}</small>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Patient Name</label>
                    <InputText v-model="form.patient_name" />
                    <small v-if="form.errors.patient_name" class="field-error">{{ form.errors.patient_name }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Phone</label>
                    <InputText v-model="form.phone" />
                    <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Email</label>
                    <InputText v-model="form.email" type="email" />
                    <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Specialty<span class="field-label__required">*</span></label>
                    <Select v-model="form.specialty" :options="specialties" required />
                    <small v-if="form.errors.specialty" class="field-error">{{ form.errors.specialty }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Doctor</label>
                    <Select v-model="form.doctor_profile_id" :options="doctorOptions" optionLabel="label" optionValue="id" showClear />
                    <small v-if="form.errors.doctor_profile_id" class="field-error">{{ form.errors.doctor_profile_id }}</small>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Appointment Date<span class="field-label__required">*</span></label>
                    <InputText v-model="form.appointment_date" type="date" required />
                    <small v-if="form.errors.appointment_date" class="field-error">{{ form.errors.appointment_date }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Start Time<span class="field-label__required">*</span></label>
                    <InputText v-model="form.start_time" type="time" required />
                    <small v-if="form.errors.start_time" class="field-error">{{ form.errors.start_time }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">End Time<span class="field-label__required">*</span></label>
                    <InputText v-model="form.end_time" type="time" required />
                    <small v-if="form.errors.end_time" class="field-error">{{ form.errors.end_time }}</small>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Treatment / Purpose<span class="field-label__required">*</span></label>
                    <InputText v-model="form.treatment_name" required />
                    <small v-if="form.errors.treatment_name" class="field-error">{{ form.errors.treatment_name }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Visit Type<span class="field-label__required">*</span></label>
                    <Select v-model="form.visit_type" :options="['consultation', 'follow_up', 'procedure']" required />
                    <small v-if="form.errors.visit_type" class="field-error">{{ form.errors.visit_type }}</small>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Status<span class="field-label__required">*</span></label>
                    <Select v-model="form.status" :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']" required />
                    <small v-if="form.errors.status" class="field-error">{{ form.errors.status }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Estimated Amount</label>
                    <InputNumber v-model="form.estimated_amount" mode="currency" currency="INR" locale="en-IN" />
                    <small v-if="form.errors.estimated_amount" class="field-error">{{ form.errors.estimated_amount }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Paid Amount</label>
                    <InputNumber v-model="form.paid_amount" mode="currency" currency="INR" locale="en-IN" />
                    <small v-if="form.errors.paid_amount" class="field-error">{{ form.errors.paid_amount }}</small>
                </div>
            </div>

            <div class="form-field">
                <label class="field-label">Notes</label>
                <Textarea v-model="form.notes" rows="3" />
                <small v-if="form.errors.notes" class="field-error">{{ form.errors.notes }}</small>
            </div>

            <div class="rounded-[20px] bg-slate-50 px-4 py-3 text-sm text-slate-600">
                If no existing patient is selected, the system creates one from the patient details entered here.
            </div>

            <div class="flex justify-end gap-3">
                <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                <Button type="submit" label="Save Appointment" :loading="form.processing" />
            </div>
        </form>
    </Dialog>

    <!-- ─── Edit Dialog ──────────────────────────────────────────────────── -->
    <Dialog v-model:visible="showEditModal" modal header="Edit Appointment" :style="{ width: '62rem' }">
        <form class="form-grid" @submit.prevent="submitEdit">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Branch<span class="field-label__required">*</span></label>
                    <Select v-model="editForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                    <small v-if="editForm.errors.branch_id" class="field-error">{{ editForm.errors.branch_id }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Doctor</label>
                    <Select v-model="editForm.doctor_profile_id" :options="doctorOptions" optionLabel="label" optionValue="id" showClear />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Appointment Date<span class="field-label__required">*</span></label>
                    <InputText v-model="editForm.appointment_date" type="date" required />
                </div>
                <div class="form-field">
                    <label class="field-label">Start Time<span class="field-label__required">*</span></label>
                    <InputText v-model="editForm.start_time" type="time" required />
                </div>
                <div class="form-field">
                    <label class="field-label">End Time<span class="field-label__required">*</span></label>
                    <InputText v-model="editForm.end_time" type="time" required />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Treatment / Purpose<span class="field-label__required">*</span></label>
                    <InputText v-model="editForm.treatment_name" required />
                </div>
                <div class="form-field">
                    <label class="field-label">Specialty<span class="field-label__required">*</span></label>
                    <Select v-model="editForm.specialty" :options="specialties" required />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Status<span class="field-label__required">*</span></label>
                    <Select v-model="editForm.status" :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']" required />
                </div>
                <div class="form-field">
                    <label class="field-label">Estimated Amount</label>
                    <InputNumber v-model="editForm.estimated_amount" mode="currency" currency="INR" locale="en-IN" />
                </div>
                <div class="form-field">
                    <label class="field-label">Paid Amount</label>
                    <InputNumber v-model="editForm.paid_amount" mode="currency" currency="INR" locale="en-IN" />
                </div>
            </div>

            <div class="form-field">
                <label class="field-label">Notes</label>
                <Textarea v-model="editForm.notes" rows="3" />
            </div>

            <div class="flex justify-end gap-3">
                <Button type="button" label="Cancel" severity="secondary" outlined @click="showEditModal = false" />
                <Button type="submit" label="Save Changes" :loading="editForm.processing" />
            </div>
        </form>
    </Dialog>
</template>
