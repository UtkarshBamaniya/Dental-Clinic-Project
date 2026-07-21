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
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    staff: Array,
    branches: Array,
    roles: Array,
    specialties: Array,
});

const showCreateModal = ref(false);
const filters = ref({
    search: '',
    branchId: null,
    role: null,
});

const form = useForm({
    branch_id: props.branches[0]?.id ?? null,
    name: '',
    email: '',
    phone: '',
    role: 'receptionist',
    job_title: '',
    monthly_salary: 0,
    specialty: props.specialties[0] ?? 'General Dentistry',
});

const filteredStaff = computed(() =>
    props.staff.filter((member) => {
        const search = filters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            member.name?.toLowerCase().includes(search) ||
            member.email?.toLowerCase().includes(search) ||
            member.job_title?.toLowerCase().includes(search);
        const matchesBranch = !filters.value.branchId || member.branch_id === filters.value.branchId;
        const matchesRole = !filters.value.role || member.role === filters.value.role;

        return matchesSearch && matchesBranch && matchesRole;
    }),
);

function resetForm() {
    form.reset('name', 'email', 'phone', 'job_title', 'monthly_salary');
    form.clearErrors();
    form.branch_id = props.branches[0]?.id ?? null;
    form.role = 'receptionist';
    form.specialty = props.specialties[0] ?? 'General Dentistry';
    form.monthly_salary = 0;
}

function openCreateModal() {
    resetForm();
    showCreateModal.value = true;
}

function submit() {
    form.post(route('staff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout title="Staff & Doctors">
        <div class="page-toolbar">
            <div class="page-toolbar__filters">
                <InputText v-model="filters.search" placeholder="Search by name, email, or title" />
                <Select v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                <Select v-model="filters.role" :options="roles" placeholder="Filter by role" showClear />
            </div>
            <div class="page-toolbar__actions">
                <Button label="Add Staff" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <Card class="glass-panel rounded-[32px] border-none shadow-none">
            <template #title>
                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Team Directory</div>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Role mix and salary visibility</h2>
            </template>
            <template #content>
                <DataTable :value="filteredStaff" stripedRows responsiveLayout="scroll">
                    <Column field="name" header="Name" />
                    <Column field="branch.name" header="Branch" />
                    <Column field="job_title" header="Designation" />
                    <Column header="Role">
                        <template #body="{ data }">
                            <Tag :value="data.role" severity="contrast" rounded />
                        </template>
                    </Column>
                    <Column header="Salary">
                        <template #body="{ data }">Rs. {{ Number(data.monthly_salary).toLocaleString() }}</template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showCreateModal" modal header="Add Staff Member" :style="{ width: '48rem' }">
            <form class="form-grid" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Role<span class="field-label__required">*</span></label>
                        <Select v-model="form.role" :options="roles" required />
                        <small v-if="form.errors.role" class="field-error">{{ form.errors.role }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Full Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Email<span class="field-label__required">*</span></label>
                        <InputText v-model="form.email" type="email" required />
                        <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Phone</label>
                        <InputText v-model="form.phone" />
                        <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Job Title</label>
                        <InputText v-model="form.job_title" />
                        <small v-if="form.errors.job_title" class="field-error">{{ form.errors.job_title }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Monthly Salary</label>
                        <InputNumber v-model="form.monthly_salary" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="form.errors.monthly_salary" class="field-error">{{ form.errors.monthly_salary }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Specialty</label>
                        <Select v-model="form.specialty" :options="specialties" />
                        <small v-if="form.errors.specialty" class="field-error">{{ form.errors.specialty }}</small>
                    </div>
                </div>

                <div class="rounded-[20px] bg-orange-50 px-4 py-3 text-sm text-orange-700">
                    New staff accounts are created with the default password <strong>password</strong>.
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                    <Button type="submit" label="Create Staff Account" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
