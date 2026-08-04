<script setup>
/**
 * DentalComponent/ActionMenu.vue
 *
 * Per-row action menu handler — View / Edit / Delete.
 * Mirrors the AugComponent/ActionMenu.vue pattern but simplified for this project.
 *
 * Usage in Index.vue:
 *   <ActionMenu ref="action_menu_ref" :formRef="form_ref" :showRef="show_ref"
 *               routeName="patients" moduleName="Patient"
 *               @fetch-data="aug_data_table.fetchData()" />
 *
 *   // Wire DataTable row-action event:
 *   const onRowAction = ({ event, data }) => action_menu_ref.value.showMenu(event, data);
 *
 * Exposes:
 *   showMenu(event, rowData) – called by the parent when ≡ is clicked on a row
 */
import { route } from 'ziggy-js';
import axios from 'axios';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Menu from 'primevue/menu';

const emit = defineEmits(['fetch-data']);

const props = defineProps({
    formRef:        { type: Object, default: null },
    showRef:        { type: Object, default: null },
    routeName:      { type: String, default: '' },
    moduleName:     { type: String, default: 'Item' },
    enabledActions: { type: Array,  default: () => ['show', 'edit', 'delete'] },
});

const toast          = useToast();
const menu           = ref();
const currentRow     = ref(null);
const confirmDelete  = ref(false);
const deleting       = ref(false);

// Build menu items that close over currentRow (re-evaluated lazily on open)
const menuItems = computed(() => {
    const items = [];

    if (props.enabledActions.includes('show')) {
        items.push({
            label:   'View',
            icon:    'pi pi-eye',
            command: () => props.showRef?.openShow(currentRow.value?.id),
        });
    }

    if (props.enabledActions.includes('edit')) {
        items.push({
            label:   'Edit',
            icon:    'pi pi-pencil',
            command: () => props.formRef?.openEdit(currentRow.value?.id),
        });
    }

    if (props.enabledActions.includes('delete')) {
        if (items.length) items.push({ separator: true });
        items.push({
            label: 'Delete',
            icon:  'pi pi-trash',
            class: '!text-red-600',
            command: () => { confirmDelete.value = true; },
        });
    }

    return items;
});

/**
 * Called by DataTable's @row-action handler.
 * Sets the current row context, then shows the PrimeVue Menu.
 */
const showMenu = (event, rowData) => {
    currentRow.value = rowData;
    menu.value.show(event);
};

const doDelete = async () => {
    if (!currentRow.value?.id || !props.routeName) return;
    deleting.value = true;
    try {
        await axios.delete(route(`${props.routeName}.destroy`, currentRow.value.id), {
            headers: { Accept: 'application/json' },
        });
        confirmDelete.value = false;
        toast.add({
            severity: 'success',
            summary:  'Deleted',
            detail:   `${props.moduleName} deleted successfully.`,
            life: 3000,
        });
        emit('fetch-data');
    } catch {
        toast.add({
            severity: 'error',
            summary:  'Error',
            detail:   `Failed to delete ${props.moduleName}. Please try again.`,
            life: 3000,
        });
    } finally {
        deleting.value = false;
    }
};

defineExpose({ showMenu, menuItems });
</script>

<template>
    <!-- Floating popup menu (anchored to the ≡ button position) -->
    <Menu ref="menu" :model="menuItems" popup />

    <!-- Delete confirmation dialog -->
    <Dialog
        v-model:visible="confirmDelete"
        header="Confirm Delete"
        :modal="true"
        :style="{ width: '420px' }"
    >
        <div class="flex items-center gap-4">
            <i class="pi pi-exclamation-triangle text-3xl text-orange-400 flex-shrink-0" />
            <span class="text-sm text-slate-700 leading-relaxed">
                Are you sure you want to delete
                <strong>{{ currentRow?.name || `this ${moduleName}` }}</strong>?
                This action <strong>cannot be undone</strong>.
            </span>
        </div>
        <template #footer>
            <Button label="Cancel" icon="pi pi-times" text  @click="confirmDelete = false" :disabled="deleting" />
            <Button label="Delete" icon="pi pi-trash" severity="danger" :loading="deleting" @click="doDelete" />
        </template>
    </Dialog>
</template>
