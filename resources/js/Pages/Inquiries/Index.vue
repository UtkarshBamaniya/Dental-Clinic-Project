<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
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
    inquiries: Array,
    branches: Array,
    staff: Array,
    sources: Array,
});

const showCreateModal = ref(false);
const filters = ref({
    search: '',
    branchId: null,
    status: null,
});

const form = useForm({
    branch_id: props.branches[0]?.id ?? null,
    assigned_to: props.staff[0]?.id ?? null,
    name: '',
    phone: '',
    email: '',
    source: props.sources[0] ?? 'Walk-in',
    treatment_interest: '',
    status: 'new',
    priority: 'warm',
    next_follow_up_at: '',
    notes: '',
});

const filteredInquiries = computed(() =>
    props.inquiries.filter((inquiry) => {
        const search = filters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            inquiry.name?.toLowerCase().includes(search) ||
            inquiry.phone?.toLowerCase().includes(search) ||
            inquiry.treatment_interest?.toLowerCase().includes(search);
        const matchesBranch = !filters.value.branchId || inquiry.branch_id === filters.value.branchId;
        const matchesStatus = !filters.value.status || inquiry.status === filters.value.status;

        return matchesSearch && matchesBranch && matchesStatus;
    }),
);

function resetForm() {
    form.reset('name', 'phone', 'email', 'treatment_interest', 'next_follow_up_at', 'notes');
    form.clearErrors();
    form.branch_id = props.branches[0]?.id ?? null;
    form.assigned_to = props.staff[0]?.id ?? null;
    form.source = props.sources[0] ?? 'Walk-in';
    form.status = 'new';
    form.priority = 'warm';
}

function openCreateModal() {
    resetForm();
    showCreateModal.value = true;
}

function submit() {
    form.post(route('inquiries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            showCreateModal.value = false;
        },
    });
}

function bookFromInquiry(inquiry) {
    router.get(route('appointments.index'), { inquiry: inquiry.id });
}

function markConverted(inquiry) {
    router.patch(route('inquiries.converted', inquiry.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout title="Inquiries">
        <div class="page-toolbar">
            <div class="page-toolbar__filters">
                <InputText v-model="filters.search" placeholder="Search by name, phone, or treatment" />
                <Select v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                <Select v-model="filters.status" :options="['new', 'follow_up', 'quoted', 'converted']" placeholder="Filter by status" showClear />
            </div>
            <div class="page-toolbar__actions">
                <Button label="Add Inquiry" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <Card class="glass-panel rounded-[28px] border-none shadow-none">
            <template #title>
                <div class="text-sm font-medium text-slate-500">Inquiry List</div>
            </template>
            <template #content>
                <DataTable :value="filteredInquiries" stripedRows responsiveLayout="scroll">
                    <Column field="name" header="Name" />
                    <Column field="phone" header="Phone" />
                    <Column field="source" header="Source" />
                    <Column field="treatment_interest" header="Treatment" />
                    <Column header="Priority">
                        <template #body="{ data }">
                            <Tag :value="data.priority" severity="warn" rounded />
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.status" severity="info" rounded />
                        </template>
                    </Column>
                    <Column header="Action">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Button label="Book Appointment" size="small" @click="bookFromInquiry(data)" />
                                <Button label="Converted" size="small" severity="secondary" outlined @click="markConverted(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showCreateModal" modal header="Add Inquiry" :style="{ width: '52rem' }">
            <form class="form-grid" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Assignee</label>
                        <Select v-model="form.assigned_to" :options="staff" optionLabel="name" optionValue="id" showClear />
                        <small v-if="form.errors.assigned_to" class="field-error">{{ form.errors.assigned_to }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Inquiry Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Phone<span class="field-label__required">*</span></label>
                        <InputText v-model="form.phone" required />
                        <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Email</label>
                        <InputText v-model="form.email" type="email" />
                        <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Source<span class="field-label__required">*</span></label>
                        <Select v-model="form.source" :options="sources" required />
                        <small v-if="form.errors.source" class="field-error">{{ form.errors.source }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Treatment Interest<span class="field-label__required">*</span></label>
                        <InputText v-model="form.treatment_interest" required />
                        <small v-if="form.errors.treatment_interest" class="field-error">{{ form.errors.treatment_interest }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Status<span class="field-label__required">*</span></label>
                        <Select v-model="form.status" :options="['new', 'follow_up', 'quoted', 'converted']" required />
                        <small v-if="form.errors.status" class="field-error">{{ form.errors.status }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Priority<span class="field-label__required">*</span></label>
                        <Select v-model="form.priority" :options="['cold', 'warm', 'hot']" required />
                        <small v-if="form.errors.priority" class="field-error">{{ form.errors.priority }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Next Follow Up</label>
                    <InputText v-model="form.next_follow_up_at" type="datetime-local" />
                    <small v-if="form.errors.next_follow_up_at" class="field-error">{{ form.errors.next_follow_up_at }}</small>
                </div>

                <div class="form-field">
                    <label class="field-label">Notes</label>
                    <Textarea v-model="form.notes" rows="4" />
                    <small v-if="form.errors.notes" class="field-error">{{ form.errors.notes }}</small>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showCreateModal = false" />
                    <Button type="submit" label="Save Inquiry" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
