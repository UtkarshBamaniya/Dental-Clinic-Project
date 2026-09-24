<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActionMenu from '@/Pages/Common/DentalComponent/ActionMenu.vue';
import ColumnArrange from '@/Pages/Common/DentalComponent/ColumnArrange.vue';
import DataTable from '@/Pages/Common/DentalComponent/DataTable.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import { route } from 'ziggy-js';
import Form from './Form.vue';
import Show from './Show.vue';

const props = defineProps({
    title: { type: String, default: 'Appointments' },
    desc: { type: String, default: 'Manage appointment bookings and schedules' },
    routeName: { type: String, default: 'appointments' },
    branches: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
    doctors: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    appointmentTypes: { type: Array, default: () => [] },
    bookingDraft: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const moduleNm = 'dental_appointments';
const aug_data_table = ref(null);
const action_menu_ref = ref(null);
const form_ref = ref(null);
const show_ref = ref(null);
const statusDrafts = ref({});

const statusOptions = ['booked', 'confirmed', 'completed', 'cancelled', 'no_show'];

const paymentStatusSeverity = {
    paid: 'success',
    partial: 'warn',
    unpaid: 'danger',
};

const doctorOptions = props.doctors.map((doctor) => ({
    id: doctor.id,
    label: `${doctor.user.name} | ${doctor.specialty}`,
}));

const allColumns = ref([
    { key: 0, field: 'no', header: 'No', visible: true },
    {
        key: 1,
        field: 'appointment_date',
        header: 'Date',
        filterType: 'text',
        filterNm: 'appointment_date',
        sortable: true,
        visible: true,
    },
    {
        key: 2,
        field: 'patient.full_name',
        header: 'Patient',
        filterType: 'text',
        filterNm: 'patient',
        visible: true,
    },
    {
        key: 3,
        field: 'visit_type',
        header: 'Visit Type',
        filterType: 'text',
        filterNm: 'visit_type',
        sortable: true,
        visible: true,
    },
    {
        key: 4,
        field: 'appointment_type.name',
        header: 'Type',
        filterType: 'text',
        filterNm: 'appointment_type_id',
        visible: true,
    },
    {
        key: 5,
        field: 'doctor.full_name',
        header: 'Doctor',
        filterType: 'text',
        filterNm: 'doctor',
        visible: true,
    },
    {
        key: 6,
        field: 'status',
        header: 'Status',
        filterType: 'select',
        filterNm: 'status',
        filterOptions: statusOptions,
        sortable: true,
        visible: true,
    },
    {
        key: 7,
        field: 'billing.paid_amount',
        header: 'Paid',
        sortable: true,
        visible: true,
    },
    {
        key: 8,
        field: 'billing.payment_status',
        header: 'Payment',
        visible: true,
    },
]);

const openCreateModal = () => {
    form_ref.value?.openNew();
};

const onRowAction = ({ event, data }) => {
    action_menu_ref.value?.showMenu(event, data);
};

const syncStatusDrafts = (rows) => {
    statusDrafts.value = rows.reduce((acc, appointment) => {
        acc[appointment.id] = appointment.status;
        return acc;
    }, {});
};

const updateStatus = (id) => {
    router.patch(
        route('appointments.status', id),
        { status: statusDrafts.value[id] },
        {
            preserveScroll: true,
            onSuccess: () => aug_data_table.value?.fetchData(),
        },
    );
};
</script>

<template>
    <AuthenticatedLayout :title="title">
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

            <div class="card !mb-0 !border-0 !border-slate-100 !pb-0 shadow-sm">
                <Toolbar class="mb-4 !rounded-xl">
                    <template #start>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary text-white shadow-lg shadow-blue-900/20 ring-1 ring-blue-400/30">
                                <i class="pi pi-calendar text-lg" />
                            </div>
                            <div>
                                <h1 class="text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                                    {{ title }}
                                </h1>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ desc }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <template #end>
                        <Button
                            icon="pi pi-plus"
                            class="mr-2"
                            v-tooltip.bottom="{ value: 'Add Appointment' }"
                            aria-label="Add appointment"
                            @click="openCreateModal"
                        />

                        <ColumnArrange
                            :moduleNm="moduleNm"
                            :allColumns="allColumns"
                            :tableRef="aug_data_table"
                        />
                    </template>
                </Toolbar>

                <DataTable
                    ref="aug_data_table"
                    :allColumns="allColumns"
                    :moduleNm="moduleNm"
                    :route_name="routeName + '.index'"
                    @row-action="onRowAction"
                    @data-update="syncStatusDrafts"
                >
                    <template #body-doctor-full_name="{ data }">
                        {{ data.doctor?.full_name || 'Auto assigned later' }}
                    </template>

                    <template #body-appointment_type-name="{ data }">
                        <Tag
                            v-if="data.appointment_type?.name"
                            :value="data.appointment_type.name"
                            severity="info"
                            rounded
                        />
                        <span v-else class="text-slate-400 text-xs">—</span>
                    </template>

                    <template #body-status="{ data }">
                        <div class="flex items-center gap-2">
                            <Select
                                v-model="statusDrafts[data.id]"
                                :options="statusOptions"
                                class="min-w-[10rem]"
                            />
                            <Button icon="pi pi-check" text rounded @click="updateStatus(data.id)" />
                        </div>
                    </template>

                    <template #body-billing-paid_amount="{ data }">
                        <Tag :value="`Rs. ${Number(data.billing?.paid_amount || 0).toLocaleString()}`" severity="success" rounded />
                    </template>

                    <template #body-billing-payment_status="{ data }">
                        <Tag
                            :value="data.billing?.payment_status || 'unpaid'"
                            :severity="paymentStatusSeverity[data.billing?.payment_status || 'unpaid'] ?? 'secondary'"
                            rounded
                        />
                    </template>
                </DataTable>
            </div>
        </div>

        <ActionMenu
            ref="action_menu_ref"
            :formRef="form_ref"
            :showRef="show_ref"
            :routeName="routeName"
            moduleName="Appointment"
            :enabledActions="['show', 'edit', 'delete', 'follow_up']"
            @fetch-data="() => aug_data_table?.fetchData()"
            @follow-up="(data) => form_ref?.openFollowUp(data)"
        />

        <Form
            ref="form_ref"
            :branches="branches"
            :patients="patients"
            :doctorOptions="doctorOptions"
            :specialties="specialties"
            :appointmentTypes="appointmentTypes"
            :bookingDraft="bookingDraft"
            :routeName="routeName"
            @fetch-data="() => aug_data_table?.fetchData()"
        />

        <Show
            ref="show_ref"
            :routeName="routeName"
        />
    </AuthenticatedLayout>
</template>
