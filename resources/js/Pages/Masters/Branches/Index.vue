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
import Textarea from 'primevue/textarea';

const props = defineProps({
    branches: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingBranch = ref(null);

const form = useForm({
    name: '',
    code: '',
    phone: '',
    email: '',
    city: '',
    address: '',
    manager_name: '',
});

function resetForm() {
    form.reset('name', 'code', 'phone', 'email', 'city', 'address', 'manager_name');
    form.clearErrors();
}

function openCreateModal() {
    editingBranch.value = null;
    resetForm();
    showCreateModal.value = true;
}

function openEditModal(branch) {
    editingBranch.value = branch;
    form.name = branch.name;
    form.code = branch.code;
    form.phone = branch.phone ?? '';
    form.email = branch.email ?? '';
    form.city = branch.city ?? '';
    form.address = branch.address ?? '';
    form.manager_name = branch.manager_name ?? '';
    form.clearErrors();
    showEditModal.value = true;
}

function submitCreate() {
    form.post(route('masters.branches.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
        },
    });
}

function submitUpdate() {
    form.put(route('masters.branches.update', editingBranch.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            editingBranch.value = null;
            showEditModal.value = false;
        },
    });
}

function deleteBranch(branch) {
    if (!window.confirm(`Archive ${branch.name}?`)) {
        return;
    }

    router.delete(route('masters.branches.destroy', branch.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout title="Branches Master">
        <div class="page-toolbar">
            <div />
            <div class="page-toolbar__actions">
                <Button label="Add Branch" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <Card class="glass-panel rounded-[32px] border-none shadow-none">
            <template #title>
                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Branch Directory</div>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Clinic locations and ownership</h2>
            </template>
            <template #content>
                <DataTable :value="branches" stripedRows responsiveLayout="scroll">
                    <Column field="name" header="Name" />
                    <Column field="code" header="Code" />
                    <Column field="city" header="City" />
                    <Column field="manager_name" header="Manager" />
                    <Column header="Users">
                        <template #body="{ data }">
                            {{ data.users_count ?? 0 }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="secondary" text rounded @click="openEditModal(data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteBranch(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showCreateModal" modal header="Add Branch" :style="{ width: '42rem' }">
            <form class="form-grid" @submit.prevent="submitCreate">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Code<span class="field-label__required">*</span></label>
                        <InputText v-model="form.code" required />
                        <small v-if="form.errors.code" class="field-error">{{ form.errors.code }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
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
                        <label class="field-label">City</label>
                        <InputText v-model="form.city" />
                        <small v-if="form.errors.city" class="field-error">{{ form.errors.city }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Manager</label>
                        <InputText v-model="form.manager_name" />
                        <small v-if="form.errors.manager_name" class="field-error">{{ form.errors.manager_name }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Address</label>
                    <Textarea v-model="form.address" rows="4" autoResize />
                    <small v-if="form.errors.address" class="field-error">{{ form.errors.address }}</small>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                    <Button type="submit" label="Create Branch" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="showEditModal" modal header="Edit Branch" :style="{ width: '42rem' }">
            <form class="form-grid" @submit.prevent="submitUpdate">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Code<span class="field-label__required">*</span></label>
                        <InputText v-model="form.code" required />
                        <small v-if="form.errors.code" class="field-error">{{ form.errors.code }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
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
                        <label class="field-label">City</label>
                        <InputText v-model="form.city" />
                        <small v-if="form.errors.city" class="field-error">{{ form.errors.city }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Manager</label>
                        <InputText v-model="form.manager_name" />
                        <small v-if="form.errors.manager_name" class="field-error">{{ form.errors.manager_name }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Address</label>
                    <Textarea v-model="form.address" rows="4" autoResize />
                    <small v-if="form.errors.address" class="field-error">{{ form.errors.address }}</small>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showEditModal = false" />
                    <Button type="submit" label="Save Changes" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
