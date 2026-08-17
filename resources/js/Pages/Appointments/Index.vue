<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ContextMenu from 'primevue/contextmenu';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Form from './Form.vue';
import Show from './Show.vue';

const props = defineProps({
    appointments: Array,
    branches: Array,
    patients: Array,
    doctors: Array,
    specialties: Array,
    bookingDraft: Object,
    filters: Object,
});

const showCreateModal = ref(Boolean(props.bookingDraft));
const localFilters = ref({
    search: '',
    branchId: null,
    status: null,
});

const dateFrom = ref(props.filters?.from_date ? new Date(props.filters.from_date) : null);
const dateTo   = ref(props.filters?.to_date   ? new Date(props.filters.to_date)   : null);

function formatDate(date) {
    if (!date) return null;
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function generate() {
    router.get(route('appointments.index'), {
        from_date: formatDate(dateFrom.value),
        to_date:   formatDate(dateTo.value),
    }, { preserveState: true, preserveScroll: true });
}

function resetDateFilter() {
    dateFrom.value = null;
    dateTo.value   = null;
    router.get(route('appointments.index'), {}, { preserveState: true, preserveScroll: true });
}

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
        const search = localFilters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            appointment.patient?.name?.toLowerCase().includes(search) ||
            appointment.treatment_name?.toLowerCase().includes(search) ||
            appointment.doctor_profile?.user?.name?.toLowerCase().includes(search);
        const matchesBranch = !localFilters.value.branchId || appointment.branch_id === localFilters.value.branchId;
        const matchesStatus = !localFilters.value.status || appointment.status === localFilters.value.status;

        return matchesSearch && matchesBranch && matchesStatus;
    }),
);

const formRef = ref(null);

function openCreateModal() {
    formRef.value?.openCreateModal();
}


function updateStatus(id) {
    router.patch(route('appointments.status', id), { status: statusDrafts[id] }, { preserveScroll: true });
}

function openEditModal(apt) {
    formRef.value?.openEditModal(apt);
}

// ── Show dialog ───────────────────────────────────────────────────────────────
const showDetailDialog    = ref(false);
const detailAppointment   = ref(null);
function openShowDialog(apt) { detailAppointment.value = apt; showDetailDialog.value = true; }

// ── Delete ────────────────────────────────────────────────────────────────────
function deleteAppointment(apt) {
    if (!window.confirm(`Delete appointment for "${apt.patient?.name ?? apt.treatment_name}"?`)) return;
    router.delete(route('appointments.destroy', apt.id), { preserveScroll: true });
}

// ── Context menu ──────────────────────────────────────────────────────────────
const ctxMenu = ref();
const ctxRow  = ref(null);
const ctxMenuItems = computed(() => [
    { label: 'Show',   icon: 'pi pi-eye',    command: () => openShowDialog(ctxRow.value) },
    { label: 'Edit',   icon: 'pi pi-pencil', command: () => openEditModal(ctxRow.value) },
    { separator: true },
    { label: 'Delete', icon: 'pi pi-trash',  class: 'text-red-600', command: () => deleteAppointment(ctxRow.value) },
]);
function onRowContextMenu(event) {
    ctxRow.value = event.data;
    ctxMenu.value.show(event.originalEvent);
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
                    <InputText v-model="localFilters.search" placeholder="Search by patient, treatment, or doctor" />
                    <Select v-model="localFilters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                    <Select v-model="localFilters.status" :options="['booked', 'confirmed', 'completed', 'cancelled', 'no_show']" placeholder="Filter by status" showClear />
                    <DatePicker v-model="dateFrom" placeholder="From Date" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                    <DatePicker v-model="dateTo" placeholder="To Date" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                    <Button label="Generate" icon="pi pi-filter" @click="generate" />
                    <Button v-if="dateFrom || dateTo" icon="pi pi-times" severity="secondary" outlined @click="resetDateFilter" v-tooltip="'Clear date filter'" />
                </div>
                <div class="page-toolbar__actions">
                    <Button label="Add Appointment" icon="pi pi-plus" @click="openCreateModal" />
                </div>
            </div>

            <!-- Context Menu -->
            <ContextMenu ref="ctxMenu" :model="ctxMenuItems" />

            <Card class="glass-panel rounded-[28px] border-none shadow-none">
                <template #title>
                    <div class="text-sm font-medium text-slate-500">Appointment List</div>
                </template>
                <template #content>
                    <DataTable :value="filteredAppointments" stripedRows responsiveLayout="scroll" contextMenu @row-contextmenu="onRowContextMenu">
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

        <Show 
            v-model:visible="showDetailDialog" 
            :appointment="detailAppointment" 
            @edit="openEditModal" 
            @delete="deleteAppointment" 
        />

        <Form 
            ref="formRef"
            :branches="branches"
            :patients="patients"
            :doctorOptions="doctorOptions"
            :specialties="specialties"
            :bookingDraft="bookingDraft"
        />
    </AuthenticatedLayout>
</template>
