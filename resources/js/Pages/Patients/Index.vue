<script setup>

import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';

// ── Reusable DentalComponents ────────────────────────────────────────────────
import DataTable    from '@/Pages/Common/DentalComponent/DataTable.vue';
import ColumnArrange from '@/Pages/Common/DentalComponent/ColumnArrange.vue';
import ActionMenu   from '@/Pages/Common/DentalComponent/ActionMenu.vue';

// ── Module-specific components ───────────────────────────────────────────────
import Form from './Form.vue';
import Show from './Show.vue';

// ── Props (passed from PatientController::index) ─────────────────────────────
const props = defineProps({
    title:     { type: String, default: 'Patients' },
    desc:      { type: String, default: 'Manage patient records' },
    routeName: { type: String, default: 'patients' },
    branches:  { type: Array,  default: () => [] },
});

// ── Component refs ───────────────────────────────────────────────────────────
const data_table_ref  = ref(null);
const action_menu_ref = ref(null);
const form_ref        = ref(null);
const show_ref        = ref(null);

// ── Module identifier for localStorage column arrangement ────────────────────
const moduleNm = 'dental_patients';

// ── Column definitions – mirrors AreaMaster allColumns pattern ───────────────
// Each column: { key, field, header, filterType?, filterNm?, sortable?, visible? }
const allColumns = ref([
    {
        key:     0,
        field:   'no',
        header:  'No',
        visible: true,
    },
    {
        key:       1,
        field:     'patient_code',
        header:    'Patient Code',
        filterType: 'text',
        filterNm:  'patient_code',
        sortable:  true,
        visible:   true,
    },
    {
        key:       2,
        field:     'full_name',
        header:    'Name',
        filterType: 'text',
        filterNm:  'name',
        sortable:  true,
        visible:   true,
    },
    {
        key:       3,
        field:     'mobile',
        header:    'Mobile',
        filterType: 'text',
        filterNm:  'mobile',
        sortable:  true,
        visible:   true,
    },
    {
        key:       4,
        field:     'dob_formatted',
        header:    'Date of Birth',
        filterType: 'text',
        filterNm:  'date_of_birth',
        sortable:  true,
        visible:   true,
    },
    {
        key:       5,
        field:     'medical_history.blood_group',
        header:    'Blood Group',
        filterType: 'text',
        filterNm:  'blood_group',
        sortable:  false,
        visible:   true,
    },
    {
        key:       6,
        field:     'email',
        header:    'Email',
        filterType: 'text',
        filterNm:  'email',
        sortable:  true,
        visible:   false,
    },
    {
        key:       6,
        field:     'city',
        header:    'City',
        filterType: 'text',
        filterNm:  'city',
        sortable:  true,
        visible:   true,
    },
    {
        key:       7,
        field:     'state',
        header:    'State',
        filterType: 'text',
        filterNm:  'state',
        sortable:  true,
        visible:   true,
    },
    {
        key:       8,
        field:     'occupation',
        header:    'Occupation',
        filterType: 'text',
        filterNm:  'occupation',
        sortable:  true,
        visible:   true,
    },
    {
        key:       9,
        field:     'status',
        header:    'Status',
        filterType: 'text',
        filterNm:  'status',
        sortable:  true,
        visible:   true,
    },
]);

// ── Row ≡ action handler – wires DataTable click to ActionMenu ───────────────
const onRowAction = ({ event, data }) => {
    action_menu_ref.value?.showMenu(event, data);
};
</script>

<template>
    <AuthenticatedLayout :title="title">

        <div class="card !mb-0 !border-0 !border-slate-100 !pb-0 shadow-sm">

            <!-- ── Toolbar ─────────────────────────────────────────────────── -->
            <Toolbar class="mb-4 !rounded-xl">
                <template #start>
                    <div class="flex items-center gap-3">
                        <!-- Module icon badge -->
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg
                                    bg-primary text-white shadow-lg shadow-blue-900/20 ring-1 ring-blue-400/30">
                            <i class="pi pi-users text-lg" />
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
                    <!-- Add Patient button -->
                    <Button
                        icon="pi pi-plus"
                        class="mr-2"
                        v-tooltip.bottom="{ value: 'Add Patient' }"
                        aria-label="Add patient"
                        @click="form_ref?.openNew()"
                    />

                    <!-- Column Arrange gear -->
                    <ColumnArrange
                        :moduleNm="moduleNm"
                        :allColumns="allColumns"
                        :tableRef="data_table_ref"
                    />
                </template>
            </Toolbar>

            <!-- ── DataTable ───────────────────────────────────────────────── -->
            <DataTable
                ref="data_table_ref"
                :allColumns="allColumns"
                :moduleNm="moduleNm"
                :route_name="routeName + '.index'"
                @row-action="onRowAction"
            >
                <!-- Custom cell: Gender → coloured Tag -->
                <!-- <template #body-gender="{ data }">
                    <Tag
                        :value="data.gender || 'NA'"
                        :severity="
                            data.gender === 'Male'   ? 'info' :
                            data.gender === 'Female' ? 'warn' : 'secondary'
                        "
                        rounded
                    />
                </template> -->

                <!-- Custom cell: Full Name (Account style) -->
                <!-- <template #body-full_name="{ data }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                            {{ data.full_name ? data.full_name.charAt(0).toUpperCase() : '—' }}
                        </div>
                        <span class="font-medium text-slate-700 dark:text-slate-200">
                            {{ data.full_name || '—' }}
                        </span>
                    </div>
                </template> -->

                <!-- Custom cell: Date of Birth -->
                <!-- <template #body-date_of_birth="{ data }">
                    <span v-if="data.dob_formatted">
                        {{ data.dob_formatted }}
                    </span>
                    <span v-else class="text-slate-300">—</span>
                </template> -->

                <!-- Custom cell: Blood Group → danger Tag -->
                <template #body-medical_history-blood_group="{ data }">
                    <Tag
                        v-if="data.medical_history?.blood_group"
                        :value="data.medical_history.blood_group"
                        severity="danger"
                        rounded
                    />
                    <span v-else class="text-slate-300">-</span>
                </template>
            </DataTable>
        </div>

        <!-- ── ActionMenu – floating popup per row ─────────────────────────── -->
        <ActionMenu
            ref="action_menu_ref"
            :formRef="form_ref"
            :showRef="show_ref"
            :routeName="routeName"
            moduleName="Patient"
            :enabledActions="['show', 'edit', 'delete']"
            @fetch-data="() => data_table_ref?.fetchData()"
        />

        <!-- ── Form – Create / Edit dialog ────────────────────────────────── -->
        <Form
            ref="form_ref"
            :branches="branches"
            :routeName="routeName"
            @fetch-data="() => data_table_ref?.fetchData()"
        />

        <!-- ── Show – Read-only detail dialog ─────────────────────────────── -->
        <Show
            ref="show_ref"
            :routeName="routeName"
        />

    </AuthenticatedLayout>
</template>
