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
import ContextMenu from 'primevue/contextmenu';

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
            class: 'delete-item',
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
    <!-- Floating context menu – positioned at exact cursor coordinates -->
    <ContextMenu
        ref="menu"
        :model="menuItems"
        class="action-popup-menu"
        :pt="{
            root: { style: 'min-width: 9rem; width: 9rem; padding: 0.25rem 0; border-radius: 0.625rem; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e2e8f0;' },
            list: { style: 'padding: 0;' },
            item: { style: 'padding: 0;' },
            itemContent: { style: 'padding: 0;' },
        }"
    />

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
            <Button label="Cancel" icon="pi pi-times" text @click="confirmDelete = false" :disabled="deleting" />
            <Button label="Delete" icon="pi pi-trash" severity="danger" :loading="deleting" @click="doDelete" />
        </template>
    </Dialog>
</template>

<style>
/* Compact action context menu */
.action-popup-menu.p-contextmenu {
    min-width: 9rem !important;
    width: 9rem !important;
    padding: 0.25rem 0 !important;
    border-radius: 0.625rem !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
}

.action-popup-menu .p-contextmenu-item-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    border-radius: 0;
    transition: background 0.15s ease;
    cursor: pointer;
    white-space: nowrap;
}

.action-popup-menu .p-contextmenu-item-link:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.action-popup-menu .p-contextmenu-item-link .p-contextmenu-item-icon {
    font-size: 0.8rem;
    color: #64748b;
}

.action-popup-menu .p-contextmenu-item-link:hover .p-contextmenu-item-icon {
    color: #0f172a;
}

/* Delete item – red text + icon */
.action-popup-menu .p-contextmenu-item.delete-item .p-contextmenu-item-link {
    color: #dc2626 !important;
}

.action-popup-menu .p-contextmenu-item.delete-item .p-contextmenu-item-link .p-contextmenu-item-icon {
    color: #dc2626 !important;
}

.action-popup-menu .p-contextmenu-item.delete-item .p-contextmenu-item-link:hover {
    background: #fef2f2 !important;
}

.action-popup-menu .p-contextmenu-separator {
    margin: 0.2rem 0;
    border-color: #e2e8f0;
}
</style>
