<script setup>
/**
 * Patients/Index.vue
 *
 * Completely rewritten to mirror the AreaMaster/Index.vue architecture
 * from the reference project:
 *
 *   ┌─────────────────────────────────────────────────────┐
 *   │  Toolbar  [Icon + Title + Desc]   [+ Add] [⚙ Cols] │
 *   ├─────────────────────────────────────────────────────┤
 *   │  DentalComponent/DataTable                          │
 *   │  (server-side pagination + sort + column filters)   │
 *   └─────────────────────────────────────────────────────┘
 *   + ActionMenu (floating popup, anchored to ≡ per row)
 *   + Form.vue   (create/edit slide dialog)
 *   + Show.vue   (read-only detail dialog)
 *
 * Props (from PatientController::index via Inertia):
 *   title, desc, routeName, branches
 */
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
const aug_data_table  = ref(null);
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
        field:     'name',
        header:    'Name',
        filterType: 'text',
        filterNm:  'name',
        sortable:  true,
        visible:   true,
    },
    {
        key:       3,
        field:     'phone',
        header:    'Phone',
        filterType: 'text',
        filterNm:  'phone',
        sortable:  true,
        visible:   true,
    },
    {
        key:       4,
        field:     'branch.name',
        header:    'Branch',
        visible:   true,
    },
    {
        key:       5,
        field:     'gender',
        header:    'Gender',
        sortable:  true,
        visible:   true,
    },
    {
        key:       6,
        field:     'blood_group',
        header:    'Blood Group',
        filterType: 'text',
        filterNm:  'blood_group',
        sortable:  true,
        visible:   true,
    },
    {
        key:       7,
        field:     'email',
        header:    'Email',
        filterType: 'text',
        filterNm:  'email',
        sortable:  true,
        visible:   false,   // hidden by default; user can enable via ColumnArrange
    },
    {
        key:       8,
        field:     'date_of_birth',
        header:    'Date of Birth',
        sortable:  true,
        visible:   false,
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
                        :tableRef="aug_data_table"
                    />
                </template>
            </Toolbar>

            <!-- ── DataTable ───────────────────────────────────────────────── -->
            <DataTable
                ref="aug_data_table"
                :allColumns="allColumns"
                :moduleNm="moduleNm"
                :route_name="routeName + '.index'"
                @row-action="onRowAction"
            >
                <!-- Custom cell: Gender → coloured Tag -->
                <template #body-gender="{ data }">
                    <Tag
                        :value="data.gender || 'NA'"
                        :severity="
                            data.gender === 'Male'   ? 'info' :
                            data.gender === 'Female' ? 'warn' : 'secondary'
                        "
                        rounded
                    />
                </template>

                <!-- Custom cell: Blood Group → danger Tag -->
                <template #body-blood_group="{ data }">
                    <Tag
                        v-if="data.blood_group"
                        :value="data.blood_group"
                        severity="danger"
                        rounded
                    />
                    <span v-else class="text-slate-300">—</span>
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
            @fetch-data="() => aug_data_table?.fetchData()"
        />

        <!-- ── Form – Create / Edit dialog ────────────────────────────────── -->
        <Form
            ref="form_ref"
            :branches="branches"
            :routeName="routeName"
            @fetch-data="() => aug_data_table?.fetchData()"
        />

        <!-- ── Show – Read-only detail dialog ─────────────────────────────── -->
        <Show
            ref="show_ref"
            :routeName="routeName"
        />

    </AuthenticatedLayout>
</template>
