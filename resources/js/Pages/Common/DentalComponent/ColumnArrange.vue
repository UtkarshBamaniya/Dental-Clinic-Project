<script setup>
/**
 * DentalComponent/ColumnArrange.vue
 *
 * Show/hide and reorder table columns, storing the arrangement in localStorage.
 * Mirrors the AugComponent/ColumnArrange.vue pattern but uses localStorage
 * instead of a backend DB table (no extra migration needed).
 *
 * Props:
 *   moduleNm   – unique key used for localStorage (e.g. 'dental_patients')
 *   allColumns – master column definitions from Index.vue
 *   tableRef   – ref to the DataTable component (so we can mutate its columns)
 */
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

const props = defineProps({
    moduleNm:   { type: String, required: true },
    allColumns: { type: Array,  required: true },
    tableRef:   { type: Object, default: null },
});

const toast    = useToast();
const menu     = ref();
const dialog   = ref(false);
const colList  = ref([]);   // draggable column list (excludes 'no')
const selected = ref([]);   // visible column headers

const storageKey = computed(() => `dental_cols_${props.moduleNm}`);

// ── Gear-icon menu items ─────────────────────────────────────────────────────
const menuItems = [
    {
        label:   'Column Arrange',
        icon:    'pi pi-list',
        command: () => openDialog(),
    },
    {
        label:   'Reset to Default',
        icon:    'pi pi-refresh',
        command: () => resetColumns(),
    },
];

// ── Open column arrange dialog ───────────────────────────────────────────────
const openDialog = () => {
    // Build list without the '#' row-number column
    colList.value = props.allColumns
        .filter(c => c.field !== 'no')
        .map(c => ({ ...c }));

    const stored = localStorage.getItem(storageKey.value);
    if (stored) {
        try {
            const saved  = JSON.parse(stored);
            const order  = Object.keys(saved);

            // Reorder to match saved order
            const ordered   = order.map(h => colList.value.find(c => c.header === h)).filter(Boolean);
            const remaining = colList.value.filter(c => !order.includes(c.header));
            colList.value   = [...ordered, ...remaining];

            // Tick visible columns
            selected.value = colList.value
                .filter(c => saved[c.header] !== false)
                .map(c => c.header);
        } catch {
            selected.value = colList.value.filter(c => c.visible !== false).map(c => c.header);
        }
    } else {
        selected.value = colList.value.filter(c => c.visible !== false).map(c => c.header);
    }

    dialog.value = true;
};

// ── Select-all state ─────────────────────────────────────────────────────────
const isAllSelected = computed(
    () => colList.value.length > 0 &&
          colList.value.every(c => selected.value.includes(c.header)),
);
const isPartial = computed(
    () => selected.value.length > 0 &&
          selected.value.length < colList.value.length,
);
const toggleAll = (val) => {
    selected.value = val ? colList.value.map(c => c.header) : [];
};

// ── Drag-to-reorder ──────────────────────────────────────────────────────────
const onRowReorder = (e) => { colList.value = e.value; };

// ── Save arrangement to localStorage + update DataTable reactively ───────────
const save = () => {
    // Persist { [header]: visible } in current order
    const arrangement = {};
    colList.value.forEach(c => {
        arrangement[c.header] = selected.value.includes(c.header);
    });
    localStorage.setItem(storageKey.value, JSON.stringify(arrangement));

    // Push the new column order + visibility into the DataTable's reactive columns ref
    if (props.tableRef?.columns) {
        const base      = props.allColumns.map(c => ({ ...c }));
        const noCol     = base.find(c => c.field === 'no');
        const others    = base.filter(c => c.field !== 'no');
        const ordered   = colList.value.map(col => {
            const orig = others.find(o => o.header === col.header);
            return orig ? { ...orig, visible: selected.value.includes(col.header) } : null;
        }).filter(Boolean);
        const remaining = others.filter(o => !colList.value.find(c => c.header === o.header));

        props.tableRef.columns.value = noCol
            ? [noCol, ...ordered, ...remaining]
            : [...ordered, ...remaining];
    }

    dialog.value = false;
    toast.add({ severity: 'success', summary: 'Columns saved', life: 2000 });
};

// ── Reset to default ─────────────────────────────────────────────────────────
const resetColumns = () => {
    localStorage.removeItem(storageKey.value);
    if (props.tableRef?.columns) {
        props.tableRef.columns.value = props.allColumns.map(c => ({ ...c }));
    }
    toast.add({ severity: 'info', summary: 'Columns reset to default', life: 2000 });
};

const toggle = (e) => menu.value.toggle(e);
</script>

<template>
    <!-- Gear button -->
    <Button
        icon="pi pi-cog"
        outlined
        severity="secondary"
        v-tooltip.bottom="{ value: 'Column Settings' }"
        aria-label="Column settings"
        @click="toggle"
    />
    <Menu ref="menu" :model="menuItems" popup />

    <!-- Column Arrange Dialog -->
    <Dialog
        v-model:visible="dialog"
        header="Arrange Columns"
        :modal="true"
        :style="{ width: 'auto', minWidth: '320px' }"
        position="topright"
        maximizable
    >
        <!-- Select-all row -->
        <div class="mb-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <Checkbox
                    :modelValue="isAllSelected"
                    :binary="true"
                    :indeterminate="isPartial"
                    @update:modelValue="toggleAll"
                />
                <span class="text-sm font-medium">Select All</span>
            </div>
            <small class="text-slate-400 text-xs">
                {{ selected.length }} / {{ colList.length }} visible
            </small>
        </div>

        <!-- Draggable column list -->
        <DataTable :value="colList" @rowReorder="onRowReorder" class="p-datatable-sm">
            <Column field="checkbox" style="width: 40px">
                <template #body="{ data }">
                    <Checkbox v-model="selected" :value="data.header" />
                </template>
            </Column>
            <Column field="header">
                <template #body="{ data }">{{ data.header }}</template>
            </Column>
            <Column rowReorder style="width: 40px" :reorderableColumn="false" />
        </DataTable>

        <template #footer>
            <Button label="Cancel" icon="pi pi-times" text @click="dialog = false" />
            <Button label="Save"   icon="pi pi-check" @click="save" />
        </template>
    </Dialog>
</template>
