<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';

const props = defineProps({
    patients: Array,
    branches: Array,
});

const showCreateModal = ref(false);
const filters = ref({
    search: '',
    branchId: null,
});

const form = useForm({
    branch_id: props.branches[0]?.id ?? null,
    name: '',
    phone: '',
    email: '',
    gender: 'Male',
    date_of_birth: '',
    blood_group: '',
    address: '',
    allergies: '',
    notes: '',
});

const filteredPatients = computed(() =>
    props.patients.filter((patient) => {
        const search = filters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            patient.name?.toLowerCase().includes(search) ||
            patient.phone?.toLowerCase().includes(search) ||
            patient.patient_code?.toLowerCase().includes(search);
        const matchesBranch = !filters.value.branchId || patient.branch_id === filters.value.branchId;

        return matchesSearch && matchesBranch;
    }),
);

function resetForm() {
    form.reset('name', 'phone', 'email', 'date_of_birth', 'blood_group', 'address', 'allergies', 'notes');
    form.clearErrors();
    form.branch_id = props.branches[0]?.id ?? null;
    form.gender = 'Male';
}

function openCreateModal() {
    resetForm();
    showCreateModal.value = true;
}

function submit() {
    form.post(route('patients.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout title="Patients">
        <div class="page-toolbar">
            <div class="page-toolbar__filters">
                <InputText v-model="filters.search" placeholder="Search by patient, code, or phone" />
                <Select
                    v-model="filters.branchId"
                    :options="branches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Filter by branch"
                    showClear
                />
            </div>
            <div class="page-toolbar__actions">
                <Button label="Add Patient" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <Card class="glass-panel rounded-[32px] border-none shadow-none">
            <template #title>
                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Patients List</div>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Visits, notes, and branch ownership</h2>
            </template>
            <template #content>
                <DataTable :value="filteredPatients" stripedRows responsiveLayout="scroll">
                    <Column field="patient_code" header="Code" />
                    <Column field="name" header="Patient" />
                    <Column field="phone" header="Phone" />
                    <Column field="branch.name" header="Branch" />
                    <Column header="Gender">
                        <template #body="{ data }">
                            <Tag :value="data.gender || 'NA'" severity="contrast" rounded />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showCreateModal" modal header="Add Patient" :style="{ width: '48rem' }">
            <form class="form-grid" @submit.prevent="submit">
                <div class="form-field">
                    <label class="field-label">
                        Branch<span class="field-label__required">*</span>
                    </label>
                    <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                    <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">
                            Full Name<span class="field-label__required">*</span>
                        </label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">
                            Phone<span class="field-label__required">*</span>
                        </label>
                        <InputText v-model="form.phone" required />
                        <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field md:col-span-1">
                        <label class="field-label">Email</label>
                        <InputText v-model="form.email" type="email" />
                        <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">
                            Gender<span class="field-label__required">*</span>
                        </label>
                        <Select v-model="form.gender" :options="['Male', 'Female', 'Other']" required />
                        <small v-if="form.errors.gender" class="field-error">{{ form.errors.gender }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Date of Birth</label>
                        <InputText v-model="form.date_of_birth" type="date" />
                        <small v-if="form.errors.date_of_birth" class="field-error">{{ form.errors.date_of_birth }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Blood Group</label>
                        <InputText v-model="form.blood_group" />
                        <small v-if="form.errors.blood_group" class="field-error">{{ form.errors.blood_group }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Address</label>
                        <Textarea v-model="form.address" rows="2" />
                        <small v-if="form.errors.address" class="field-error">{{ form.errors.address }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Allergies / Cautions</label>
                        <Textarea v-model="form.allergies" rows="3" />
                        <small v-if="form.errors.allergies" class="field-error">{{ form.errors.allergies }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Clinical Notes</label>
                        <Textarea v-model="form.notes" rows="3" />
                        <small v-if="form.errors.notes" class="field-error">{{ form.errors.notes }}</small>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                    <Button type="submit" label="Save Patient" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
