<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';

const props = defineProps({
    appointments: Array,
    branches: Array,
    patients: Array,
    doctors: Array,
    specialties: Array,
    bookingDraft: Object,
});

const showCreateModal = ref(Boolean(props.bookingDraft));
const filters = ref({
    search: '',
    branchId: null,
    status: null,
});

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
    specialty: props.specialties.includes(props.bookingDraft?.specialty)
        ? props.bookingDraft.specialty
        : props.specialties[0] ?? 'Orthodontics',
    treatment_name: props.bookingDraft?.treatment_name ?? '',
    status: 'booked',
    visit_type: 'consultation',
    estimated_amount: 0,
    paid_amount: 0,
    notes: props.bookingDraft?.notes ?? '',
});

const statusDrafts = reactive(
    props.appointments.reduce((acc, appointment) => {
        acc[appointment.id] = appointment.status;
        return acc;
    }, {}),
);

const doctorOptions = props.doctors.map((doctor) => ({
    id: doctor.id,
    label: `${doctor.user.name} | ${doctor.specialty}`,
}));

const filteredAppointments = computed(() =>
    props.appointments.filter((appointment) => {
        const search = filters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            appointment.patient?.name?.toLowerCase().includes(search) ||
            appointment.treatment_name?.toLowerCase().includes(search) ||
            appointment.doctor_profile?.user?.name?.toLowerCase().includes(search);
        const matchesBranch = !filters.value.branchId || appointment.branch_id === filters.value.branchId;
        const matchesStatus = !filters.value.status || appointment.status === filters.value.status;

        return matchesSearch && matchesBranch && matchesStatus;
    }),
);

function resetForm() {
    form.reset('appointment_date', 'treatment_name', 'notes', 'patient_name', 'phone', 'email', 'inquiry_id', 'patient_id', 'doctor_profile_id');
    form.clearErrors();
    form.branch_id = props.bookingDraft?.branch_id ?? props.branches[0]?.id ?? null;
    form.patient_id = props.bookingDraft?.patient_id ?? null;
    form.patient_name = props.bookingDraft?.patient_name ?? '';
    form.phone = props.bookingDraft?.phone ?? '';
    form.email = props.bookingDraft?.email ?? '';
    form.inquiry_id = props.bookingDraft?.inquiry_id ?? null;
    form.specialty = props.specialties.includes(props.bookingDraft?.specialty)
        ? props.bookingDraft.specialty
        : props.specialties[0] ?? 'Orthodontics';
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
        },
    });
}

function updateStatus(id) {
    router.patch(route('appointments.status', id), { status: statusDrafts[id] }, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout title="Appointments">
        <div class="space-y-6">
            <Card v-if="bookingDraft" class="glass-panel rounded-[28px] border-none shadow-none">
                <template #content>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="text-sm font-medium text-teal-700">Booking from inquiry</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ bookingDraft.patient_name }}</div>
                            <div class="text-sm text-slate-500">{{ bookingDraft.phone }} | {{ bookingDraft.treatment_name }}</div>
                        </div>
                        <Button label="Open Booking Dialog" icon="pi pi-plus" @click="openCreateModal" />
                    </div>
                </template>
            </Card>

            <div class="page-toolbar">
                <div class="page-toolbar__filters">
                    <InputText v-model="filters.search" placeholder="Search by patient, treatment, or doctor" />
                    <Select v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                    <Select v-model="filters.status" :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']" placeholder="Filter by status" showClear />
                </div>
                <div class="page-toolbar__actions">
                    <Button label="Add Appointment" icon="pi pi-plus" @click="openCreateModal" />
                </div>
            </div>

            <Card class="glass-panel rounded-[28px] border-none shadow-none">
                <template #title>
                    <div class="text-sm font-medium text-slate-500">Appointment List</div>
                </template>
                <template #content>
                    <DataTable :value="filteredAppointments" stripedRows responsiveLayout="scroll">
                        <Column field="appointment_date" header="Date" />
                        <Column field="patient.name" header="Patient" />
                        <Column field="treatment_name" header="Treatment" />
                        <Column header="Doctor">
                            <template #body="{ data }">
                                {{ data.doctor_profile?.user?.name || 'Auto assigned later' }}
                            </template>
                        </Column>
                        <Column field="token_no" header="Token" />
                        <Column header="Status">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Select
                                        v-model="statusDrafts[data.id]"
                                        :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']"
                                        class="min-w-[10rem]"
                                    />
                                    <Button icon="pi pi-check" text rounded @click="updateStatus(data.id)" />
                                </div>
                            </template>
                        </Column>
                        <Column header="Paid">
                            <template #body="{ data }">
                                <Tag :value="`Rs. ${Number(data.paid_amount).toLocaleString()}`" severity="success" rounded />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

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
    </AuthenticatedLayout>
</template>
