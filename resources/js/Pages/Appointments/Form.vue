<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy-js';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    branches: Array,
    patients: Array,
    doctorOptions: Array,
    specialties: Array,
    appointmentTypes: { type: Array, default: () => [] },
    bookingDraft: Object,
    routeName: { type: String, default: 'appointments' },
});

const emit = defineEmits(['saved', 'fetch-data']);
const toast = useToast();

const showAppointmentModal = ref(Boolean(props.bookingDraft));
const editingAppointment = ref(null);

const defaultBilling = () => ({
    estimated_amount: 0,
    paid_amount: 0,
    discount: 0,
    payment_status: 'unpaid',
});

const form = useForm({
    inquiry_id: props.bookingDraft?.inquiry_id ?? null,
    branch_id: props.bookingDraft?.branch_id ?? props.branches[0]?.id ?? null,
    patient_id: props.bookingDraft?.patient_id ?? null,
    patient_name: props.bookingDraft?.patient_name ?? '',
    phone: props.bookingDraft?.phone ?? '',
    email: props.bookingDraft?.email ?? '',
    doctor_profile_id: null,
    appointment_type_id: null,
    parent_appointment_id: null,
    appointment_date: '',
    start_time: '10:00',
    end_time: '10:30',
    specialty: props.specialties?.includes(props.bookingDraft?.specialty)
        ? props.bookingDraft.specialty
        : props.specialties?.[0] ?? 'Orthodontics',
    treatment_name: props.bookingDraft?.treatment_name ?? '',
    status: 'booked',
    notes: props.bookingDraft?.notes ?? '',
    billing: defaultBilling(),
});

function resetForm() {
    form.reset(
        'appointment_date',
        'treatment_name',
        'notes',
        'patient_name',
        'phone',
        'email',
        'inquiry_id',
        'patient_id',
        'doctor_profile_id',
        'appointment_type_id',
        'parent_appointment_id',
    );
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
    form.billing = defaultBilling();
    editingAppointment.value = null;
}

function openCreateModal() {
    resetForm();
    showAppointmentModal.value = true;
}

function submit() {
    if (editingAppointment.value) {
        form.put(route(`${props.routeName}.update`, editingAppointment.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetForm();
                showAppointmentModal.value = false;
                emit('fetch-data');
                emit('saved');
            },
        });

        return;
    }

    form.post(route(`${props.routeName}.store`), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showAppointmentModal.value = false;
            emit('fetch-data');
            emit('saved');
        },
    });
}

// ── Edit modal ────────────────────────────────────────────────────────────────
function openEditModal(apt) {
    editingAppointment.value = apt;
    form.branch_id = apt.branch_id;
    form.patient_id = apt.patient_id;
    form.patient_name = apt.patient?.name ?? '';
    form.phone = apt.patient?.phone ?? '';
    form.email = apt.patient?.email ?? '';
    form.inquiry_id = null;
    form.doctor_profile_id = apt.doctor_profile_id;
    form.appointment_type_id = apt.appointment_type_id ?? null;
    form.parent_appointment_id = apt.parent_appointment_id ?? null;
    form.appointment_date = apt.appointment_date;
    form.start_time = apt.start_time;
    form.end_time = apt.end_time;
    form.specialty = apt.specialty;
    form.treatment_name = apt.treatment_name;
    form.status = apt.status;
    form.notes = apt.notes ?? '';
    form.billing = {
        estimated_amount: Number(apt.billing?.estimated_amount ?? 0),
        paid_amount: Number(apt.billing?.paid_amount ?? 0),
        discount: Number(apt.billing?.discount ?? 0),
        payment_status: apt.billing?.payment_status ?? 'unpaid',
    };
    form.clearErrors();
    showAppointmentModal.value = true;
}

watch(() => form.patient_id, (newVal) => {
    if (newVal) {
        const patient = props.patients.find(p => p.id === newVal);
        if (patient) {
            form.patient_name = patient.name || '';
            form.phone = patient.phone || '';
            form.email = patient.email || '';
        }
    } else if (!editingAppointment.value) {
        form.patient_name = '';
        form.phone = '';
        form.email = '';
    }
});

const openNew = openCreateModal;

const openEdit = async (id) => {
    try {
        const { data } = await axios.get(route(`${props.routeName}.edit`, id), {
            headers: { Accept: 'application/json' },
        });
        openEditModal(data);
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load appointment data.', life: 3000 });
    }
};

const openFollowUp = (apt) => {
    resetForm();
    form.branch_id = apt.branch_id;
    form.patient_id = apt.patient_id;
    form.patient_name = apt.patient_name ?? '';
    form.phone = apt.patient_phone ?? '';
    form.email = apt.patient_email ?? '';
    form.specialty = apt.specialty;
    form.parent_appointment_id = apt.id;
    form.status = 'booked';
    showAppointmentModal.value = true;
};

const paymentStatusOptions = [
    { label: 'Unpaid', value: 'unpaid' },
    { label: 'Partial', value: 'partial' },
    { label: 'Paid', value: 'paid' },
];

defineExpose({ openCreateModal, openEditModal, openNew, openEdit, openFollowUp });
</script>

<template>
    <!-- ─── Create / Edit Dialog ────────────────────────────────────────────── -->
    <Dialog
        v-model:visible="showAppointmentModal"
        maximizable
        modal
        :header="editingAppointment ? 'Edit Appointment' : 'Add Appointment'"
        :style="{ width: '62rem' }"
    >
        <form class="form-grid" @submit.prevent="submit">

            <!-- Top Controls -->
            <div class="grid gap-4 md:grid-cols-2 mb-4">
                <div class="form-field">
                    <label class="field-label">Branch<span class="field-label__required">*</span></label>
                    <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" />
                    <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Existing Patient</label>
                    <Select v-model="form.patient_id" :options="patients" optionLabel="name" optionValue="id" showClear filter placeholder="Select a patient" />
                    <small v-if="form.errors.patient_id" class="field-error">{{ form.errors.patient_id }}</small>
                </div>
            </div>

            <!-- Patient Details Card -->
            <div class="card !mb-4 p-4 border border-slate-200 rounded-xl bg-white shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2">Patient Details</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Patient Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.patient_name" />
                        <small v-if="form.errors.patient_name" class="field-error">{{ form.errors.patient_name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Phone<span class="field-label__required">*</span></label>
                        <InputText v-model="form.phone" />
                        <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Email</label>
                        <InputText v-model="form.email" type="email" />
                        <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
                    </div>
                </div>
            </div>

            <!-- Appointment Details Card -->
            <div class="card !mb-4 p-4 border border-slate-200 rounded-xl bg-white shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2">Appointment Details</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Specialty<span class="field-label__required">*</span></label>
                        <Select v-model="form.specialty" :options="specialties" />
                        <small v-if="form.errors.specialty" class="field-error">{{ form.errors.specialty }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Appointment Type</label>
                        <Select
                            v-model="form.appointment_type_id"
                            :options="appointmentTypes"
                            optionLabel="name"
                            optionValue="id"
                            showClear
                            placeholder="Select type"
                        />
                        <small v-if="form.errors.appointment_type_id" class="field-error">{{ form.errors.appointment_type_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Doctor</label>
                        <Select v-model="form.doctor_profile_id" :options="doctorOptions" optionLabel="label" optionValue="id" showClear />
                        <small v-if="form.errors.doctor_profile_id" class="field-error">{{ form.errors.doctor_profile_id }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3 mt-4">
                    <div class="form-field">
                        <label class="field-label">Appointment Date<span class="field-label__required">*</span></label>
                        <InputText v-model="form.appointment_date" type="date" />
                        <small v-if="form.errors.appointment_date" class="field-error">{{ form.errors.appointment_date }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Start Time<span class="field-label__required">*</span></label>
                        <InputText v-model="form.start_time" type="time" />
                        <small v-if="form.errors.start_time" class="field-error">{{ form.errors.start_time }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">End Time<span class="field-label__required">*</span></label>
                        <InputText v-model="form.end_time" type="time" />
                        <small v-if="form.errors.end_time" class="field-error">{{ form.errors.end_time }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 mt-4">
                    <div class="form-field">
                        <label class="field-label">Treatment / Purpose<span class="field-label__required">*</span></label>
                        <InputText v-model="form.treatment_name" />
                        <small v-if="form.errors.treatment_name" class="field-error">{{ form.errors.treatment_name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Status<span class="field-label__required">*</span></label>
                        <Select v-model="form.status" :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']" />
                        <small v-if="form.errors.status" class="field-error">{{ form.errors.status }}</small>
                    </div>
                </div>

                <!-- Follow-up badge -->
                <div v-if="form.parent_appointment_id" class="mt-4 flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-700">
                    <i class="pi pi-history text-blue-500" />
                    This is a follow-up appointment (linked to appointment #{{ form.parent_appointment_id }})
                </div>
            </div>

            <!-- Billing Details Card -->
            <div class="card !mb-4 p-4 border border-slate-200 rounded-xl bg-white shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2">Billing Details & Notes</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Estimated Amount</label>
                        <InputNumber v-model="form.billing.estimated_amount" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="form.errors['billing.estimated_amount']" class="field-error">{{ form.errors['billing.estimated_amount'] }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Paid Amount</label>
                        <InputNumber v-model="form.billing.paid_amount" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="form.errors['billing.paid_amount']" class="field-error">{{ form.errors['billing.paid_amount'] }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 mt-4">
                    <div class="form-field">
                        <label class="field-label">Discount</label>
                        <InputNumber v-model="form.billing.discount" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="form.errors['billing.discount']" class="field-error">{{ form.errors['billing.discount'] }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Payment Status</label>
                        <Select
                            v-model="form.billing.payment_status"
                            :options="paymentStatusOptions"
                            optionLabel="label"
                            optionValue="value"
                        />
                        <small v-if="form.errors['billing.payment_status']" class="field-error">{{ form.errors['billing.payment_status'] }}</small>
                    </div>
                </div>

                <div class="form-field mt-4">
                    <label class="field-label">Notes</label>
                    <Textarea v-model="form.notes" rows="3" />
                    <small v-if="form.errors.notes" class="field-error">{{ form.errors.notes }}</small>
                </div>
            </div>

            <div class="rounded-[20px] bg-slate-50 px-4 py-3 text-sm text-slate-600 mb-4">
                If no existing patient is selected, the system creates one from the patient details entered here.
            </div>

            <div class="flex justify-end gap-3">
                <Button type="button" label="Cancel" severity="secondary" outlined @click="showAppointmentModal = false" />
                <Button type="submit" :label="editingAppointment ? 'Save Changes' : 'Save Appointment'" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
