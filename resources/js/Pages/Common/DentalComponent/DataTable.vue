<script setup>
/**
 * DentalComponent/DataTable.vue
 *
 * Reusable server-side DataTable for the Dental Clinic project.
 * Mirrors the AugComponent/DataTable.vue pattern from the reference project
 * but adapted for this project's tech stack (no custom helpers, no DB column arrange).
 *
 * Props:
 *   allColumns   – column definitions (key, field, header, filterType, filterNm, sortable, visible)
 *   route_name   – Ziggy route name for the JSON data endpoint (e.g. 'patients.index')
 *   moduleNm     – unique module key used for localStorage column arrangement
 *   route_param  – optional extra route parameter
 *   defaultPageSize – rows per page (default 50)
 *
 * Emits:
 *   row-action   – { event, data } when the ≡ button on a row is clicked
 *   data-update  – emitted with the new data array after every fetch
 *
 * Exposes:
 *   fetchData()  – trigger a refresh from the parent
 *   columns      – reactive column array (mutated by ColumnArrange)
 */
import { route } from 'ziggy-js';
import axios from 'axios';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTablePV from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';

// ── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    permissions:     { type: Object, default: () => ({}) },
    allColumns:      { type: Array,  default: () => [] },
    route_name:      { type: String, required: true },
    moduleNm:        { type: String, required: true },
    route_param:     { type: [Object, String], default: null },
    defaultPageSize: { type: Number, default: 50 },
});

const emit = defineEmits(['row-action', 'data-update']);

const toast = useToast();

// ── State ────────────────────────────────────────────────────────────────────
const products     = ref([]);
const loading      = ref(false);
const totalRecords = ref(0);
const page         = ref(1);
const size         = ref(props.defaultPageSize);
const sortField    = ref('id');
const sortOrder    = ref(0);   // PrimeVue convention: 1 = asc, -1 = desc, 0 = none
const filters      = ref({});

// ── Column arrangement (driven by localStorage, mutated by ColumnArrange) ───
const columns = ref([]);

const storageKey = computed(() => `dental_cols_${props.moduleNm}`);

const applyStoredColumns = () => {
    const stored = localStorage.getItem(storageKey.value);
    let base = props.allColumns.map(c => ({ ...c }));

    if (!stored) {
        columns.value = base;
        return;
    }

    try {
        const saved = JSON.parse(stored);   // { [header]: boolean (visible) }
        const order = Object.keys(saved);

        // Apply saved visibility
        base = base.map(c => ({
            ...c,
            visible: c.field === 'no'
                ? true
                : (saved[c.header] !== undefined ? saved[c.header] : c.visible !== false),
        }));

        // Re-order (keep 'no' pinned first)
        const noCol    = base.find(c => c.field === 'no');
        const others   = base.filter(c => c.field !== 'no');
        const ordered  = order.map(h => others.find(c => c.header === h)).filter(Boolean);
        const remaining = others.filter(c => !order.includes(c.header));

        columns.value = noCol
            ? [noCol, ...ordered, ...remaining]
            : [...ordered, ...remaining];
    } catch {
        columns.value = base;
    }
};

// Re-apply whenever allColumns changes (e.g. ColumnArrange saves)
watch(() => props.allColumns, applyStoredColumns, { immediate: true });

const visibleColumns = computed(() =>
    columns.value.filter(c => c.visible !== false),
);

// ── Data Fetch ────────────────────────────────────────────────────────────────
const fetchData = async () => {
    loading.value = true;
    try {
        const routeUrl = props.route_param
            ? route(props.route_name, props.route_param)
            : route(props.route_name);

        const { data: resp } = await axios.get(routeUrl, {
            params: {
                size:      size.value,
                page:      page.value,
                sortField: sortField.value,
                sortOrder: sortOrder.value === 1 ? 'asc' : 'desc',
                ...filters.value,
            },
            headers: { Accept: 'application/json' },
        });

        products.value     = resp.data;
        totalRecords.value = resp.total;
        page.value         = resp.current_page;
        size.value         = resp.per_page;
        emit('data-update', products.value);
    } catch {
        toast.add({
            severity: 'error',
            summary:  'Error',
            detail:   'Failed to load data. Please try again.',
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};

// ── Pagination & Sort events ─────────────────────────────────────────────────
const onPage = (e) => {
    page.value = e.page + 1;
    size.value = e.rows;
    fetchData();
};

const onSort = (e) => {
    sortField.value = e.sortField ?? 'id';
    sortOrder.value = e.sortOrder;
    fetchData();
};

// ── Filter debounce ──────────────────────────────────────────────────────────
let filterTimer = null;
const onFilterChange = () => {
    page.value = 1;
    clearTimeout(filterTimer);
    filterTimer = setTimeout(fetchData, 400);
};

// ── Nested field resolver (e.g. 'branch.name' → data.branch?.name) ──────────
const resolveField = (data, field) => {
    if (!field || !field.includes('.')) return data[field];
    return field.split('.').reduce((obj, key) => obj?.[key], data);
};

onMounted(fetchData);

defineExpose({ fetchData, columns });
</script>

<template>
    <DataTablePV
        :value="products"
        :loading="loading"
        :totalRecords="totalRecords"
        :rows="size"
        :rowsPerPageOptions="[25, 50, 100]"
        :sortField="sortField"
        :sortOrder="sortOrder"
        lazy
        paginator
        filterDisplay="row"
        stripedRows
        removableSort
        scrollable
        dataKey="id"
        class="p-datatable-sm"
        @page="onPage"
        @sort="onSort"
    >
        <!-- ── Empty / Loading states ──────────────────────────────── -->
        <template #empty>
            <div class="py-14 flex flex-col items-center text-slate-400">
                <i class="pi pi-inbox text-5xl mb-3 opacity-30" />
                <p class="text-sm">No records found.</p>
            </div>
        </template>
        <template #loading>
            <div class="py-14 flex flex-col items-center text-slate-400">
                <i class="pi pi-spin pi-spinner text-3xl mb-2" />
                <p class="text-sm">Loading data…</p>
            </div>
        </template>

        <!-- ── Dynamic columns ────────────────────────────────────── -->
        <template v-for="col in visibleColumns" :key="col.field">

            <!-- Row number (#) -->
            <Column
                v-if="col.field === 'no'"
                header="No"
                style="width: 56px; min-width: 56px"
            >
                <template #body="{ index }">
                    <span class="text-xs text-slate-400">
                        {{ (page - 1) * size + index + 1 }}
                    </span>
                </template>
            </Column>

            <!-- Regular data column -->
            <Column
                v-else
                :field="col.field"
                :header="col.header"
                :sortable="col.sortable ?? false"
                :style="col.style"
            >
                <!-- Filter input row -->
                <template v-if="col.filterType === 'text'" #filter>
                    <InputText
                        v-model="filters[col.filterNm]"
                        :placeholder="`Search ${col.header}`"
                        class="text-xs w-full"
                        @input="onFilterChange"
                    />
                </template>

                <!-- Cell body – parent can override via named slot -->
                <template #body="{ data }">
                    <slot :name="`body-${col.field.replace('.', '-')}`" :data="data" :col="col">
                        {{ resolveField(data, col.field) ?? '—' }}
                    </slot>
                </template>
            </Column>
        </template>

        <!-- ── Action column (≡ hamburger) ───────────────────────── -->
        <Column
            header="Action"
            style="width: 64px; min-width: 64px"
            frozen
            alignFrozen="right"
        >
            <template #body="{ data }">
                <Button
                    icon="pi pi-ellipsis-v"
                    text
                    rounded
                    size="small"
                    severity="secondary"
                    aria-label="Row actions"
                    @click.stop="$emit('row-action', { event: $event, data })"
                />
            </template>
        </Column>
    </DataTablePV>
</template>
