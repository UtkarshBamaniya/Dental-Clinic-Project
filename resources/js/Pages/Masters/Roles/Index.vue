<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';

const props = defineProps({
    roles: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingRole = ref(null);

const form = useForm({
    code: '',
    name: '',
});

function resetForm() {
    form.reset('code', 'name');
    form.clearErrors();
}

function openCreateModal() {
    editingRole.value = null;
    resetForm();
    showCreateModal.value = true;
}

function openEditModal(role) {
    editingRole.value = role;
    form.code = role.code;
    form.name = role.name;
    form.clearErrors();
    showEditModal.value = true;
}

function submitCreate() {
    form.post(route('masters.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
        },
    });
}

function submitUpdate() {
    form.put(route('masters.roles.update', editingRole.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            editingRole.value = null;
            showEditModal.value = false;
        },
    });
}

function deleteRole(role) {
    if (role.is_system) {
        return;
    }

    if (!window.confirm(`Archive ${role.name}?`)) {
        return;
    }

    router.delete(route('masters.roles.destroy', role.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout title="Roles Master">
        <div class="page-toolbar">
            <div />
            <div class="page-toolbar__actions">
                <Button label="Add Role" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <Card class="glass-panel rounded-[32px] border-none shadow-none">
            <template #title>
                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Role Directory</div>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Access labels and system roles</h2>
            </template>
            <template #content>
                <DataTable :value="roles" stripedRows responsiveLayout="scroll">
                    <Column field="code" header="Code" />
                    <Column field="name" header="Name" />
                    <Column header="Type">
                        <template #body="{ data }">
                            <Tag :value="data.is_system ? 'System' : 'Custom'" :severity="data.is_system ? 'info' : 'contrast'" rounded />
                        </template>
                    </Column>
                    <Column header="Users">
                        <template #body="{ data }">
                            {{ data.users_count ?? 0 }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="secondary" text rounded @click="openEditModal(data)" />
                                <Button v-if="!data.is_system" icon="pi pi-trash" severity="danger" text rounded @click="deleteRole(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showCreateModal" modal header="Add Role" :style="{ width: '34rem' }">
            <form class="form-grid" @submit.prevent="submitCreate">
                <div class="form-field">
                    <label class="field-label">Role Code<span class="field-label__required">*</span></label>
                    <InputText v-model="form.code" required />
                    <small v-if="form.errors.code" class="field-error">{{ form.errors.code }}</small>
                </div>

                <div class="form-field">
                    <label class="field-label">Role Name<span class="field-label__required">*</span></label>
                    <InputText v-model="form.name" required />
                    <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                    <Button type="submit" label="Create Role" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="showEditModal" modal header="Edit Role" :style="{ width: '34rem' }">
            <form class="form-grid" @submit.prevent="submitUpdate">
                <div class="form-field">
                    <label class="field-label">Role Code<span class="field-label__required">*</span></label>
                    <InputText v-model="form.code" :disabled="editingRole?.is_system" required />
                    <small v-if="form.errors.code" class="field-error">{{ form.errors.code }}</small>
                </div>

                <div class="form-field">
                    <label class="field-label">Role Name<span class="field-label__required">*</span></label>
                    <InputText v-model="form.name" required />
                    <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showEditModal = false" />
                    <Button type="submit" label="Save Changes" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
