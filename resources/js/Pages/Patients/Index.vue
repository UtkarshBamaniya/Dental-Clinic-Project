<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ContextMenu from 'primevue/contextmenu';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';

const props = defineProps({
    patients: Array,
    branches: Array,
    filters: Object,
});

// ── Local filters ─────────────────────────────────────────────────────────────
const localFilters = ref({ search: '', branchId: null });

// ── Date range filter ─────────────────────────────────────────────────────────
const dateFrom = ref(props.filters?.from_date ? new Date(props.filters.from_date) : null);
const dateTo   = ref(props.filters?.to_date   ? new Date(props.filters.to_date)   : null);
function formatDate(date) {
    if (!date) return null;
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
function generate() {
    router.get(route('patients.index'), {
        from_date: formatDate(dateFrom.value),
        to_date:   formatDate(dateTo.value),
    }, { preserveState: true, preserveScroll: true });
}
function resetDateFilter() {
    dateFrom.value = null; dateTo.value = null;
    router.get(route('patients.index'), {}, { preserveState: true, preserveScroll: true });
}

// ── Filtered list ─────────────────────────────────────────────────────────────
const filteredPatients = computed(() =>
    props.patients.filter((patient) => {
        const search = localFilters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            patient.name?.toLowerCase().includes(search) ||
            patient.phone?.toLowerCase().includes(search) ||
            patient.patient_code?.toLowerCase().includes(search);
        const matchesBranch = !localFilters.value.branchId || patient.branch_id === localFilters.value.branchId;
        return matchesSearch && matchesBranch;
    }),
);

// ── Create form ───────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const form = useForm({
    branch_id: props.branches[0]?.id ?? null,
    name: '', phone: '', email: '', gender: 'Male',
    date_of_birth: '', blood_group: '', address: '', allergies: '', notes: '',
});
function resetForm() {
    form.reset('name', 'phone', 'email', 'date_of_birth', 'blood_group', 'address', 'allergies', 'notes');
    form.clearErrors();
    form.branch_id = props.branches[0]?.id ?? null;
    form.gender = 'Male';
}
function openCreateModal() { resetForm(); showCreateModal.value = true; }
function submit() {
    form.post(route('patients.store'), {
        preserveScroll: true,
        onSuccess: () => { resetForm(); showCreateModal.value = false; },
    });
}

// ── Edit form ─────────────────────────────────────────────────────────────────
const showEditModal  = ref(false);
const editingPatient = ref(null);
const editForm = useForm({
    branch_id: null, name: '', phone: '', email: '', gender: 'Male',
    date_of_birth: '', blood_group: '', address: '', allergies: '', notes: '',
});
function openEditModal(patient) {
    editingPatient.value    = patient;
    editForm.branch_id      = patient.branch_id;
    editForm.name           = patient.name;
    editForm.phone          = patient.phone;
    editForm.email          = patient.email ?? '';
    editForm.gender         = patient.gender ?? 'Male';
    editForm.date_of_birth  = patient.date_of_birth ?? '';
    editForm.blood_group    = patient.blood_group ?? '';
    editForm.address        = patient.address ?? '';
    editForm.allergies      = patient.allergies ?? '';
    editForm.notes          = patient.notes ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}
function submitEdit() {
    editForm.put(route('patients.update', editingPatient.value.id), {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; editingPatient.value = null; },
    });
}

// ── Show dialog ───────────────────────────────────────────────────────────────
const showDetailDialog = ref(false);
const detailPatient    = ref(null);
function openShowDialog(patient) { detailPatient.value = patient; showDetailDialog.value = true; }

// ── Delete ────────────────────────────────────────────────────────────────────
function deletePatient(patient) {
    if (!window.confirm(`Delete patient "${patient.name}"? This cannot be undone.`)) return;
    router.delete(route('patients.destroy', patient.id), { preserveScroll: true });
}

// ── Context menu ──────────────────────────────────────────────────────────────
const ctxMenu = ref();
const ctxRow  = ref(null);
const ctxMenuItems = computed(() => [
    { label: 'Show',   icon: 'pi pi-eye',    command: () => openShowDialog(ctxRow.value) },
    { label: 'Edit',   icon: 'pi pi-pencil', command: () => openEditModal(ctxRow.value) },
    { separator: true },
    { label: 'Delete', icon: 'pi pi-trash',  class: 'text-red-600', command: () => deletePatient(ctxRow.value) },
]);
function onRowContextMenu(event) {
    ctxRow.value = event.data;
    ctxMenu.value.show(event.originalEvent);
}
</script>

<template>
    <AuthenticatedLayout title="Patients">
        <!-- Toolbar -->
        <div class="page-toolbar">
            <div class="page-toolbar__filters">
                <InputText v-model="localFilters.search" placeholder="Search by patient, code, or phone" />
                <Select v-model="localFilters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                <DatePicker v-model="dateFrom" placeholder="From Date" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                <DatePicker v-model="dateTo"   placeholder="To Date"   dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                <Button label="Generate" icon="pi pi-filter" @click="generate" />
                <Button v-if="dateFrom || dateTo" icon="pi pi-times" severity="secondary" outlined @click="resetDateFilter" v-tooltip="'Clear date filter'" />
            </div>
            <div class="page-toolbar__actions">
                <Button label="Add Patient" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <!-- Context Menu -->
        <ContextMenu ref="ctxMenu" :model="ctxMenuItems" />

        <!-- Table -->
        <Card class="glass-panel rounded-[32px] border-none shadow-none">
            <template #title>
                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Patients List</div>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Visits, notes, and branch ownership</h2>
            </template>
            <template #content>
                <DataTable
                    :value="filteredPatients"
                    stripedRows
                    responsiveLayout="scroll"
                    contextMenu
                    @row-contextmenu="onRowContextMenu"
                >
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

        <!-- ─── Show Dialog ──────────────────────────────────────────────── -->
        <Dialog v-model:visible="showDetailDialog" modal header="Patient Details" :style="{ width: '44rem' }">
            <div v-if="detailPatient" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Code</div><div class="mt-1 font-medium font-mono">{{ detailPatient.patient_code }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Name</div><div class="mt-1 font-medium">{{ detailPatient.name }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Phone</div><div class="mt-1 font-medium">{{ detailPatient.phone }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Email</div><div class="mt-1 font-medium">{{ detailPatient.email || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Gender</div><div class="mt-1"><Tag :value="detailPatient.gender || 'NA'" severity="contrast" rounded /></div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Date of Birth</div><div class="mt-1 font-medium">{{ detailPatient.date_of_birth || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Blood Group</div><div class="mt-1 font-medium">{{ detailPatient.blood_group || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Branch</div><div class="mt-1 font-medium">{{ detailPatient.branch?.name || '—' }}</div></div>
                </div>
                <div v-if="detailPatient.address"><div class="text-xs text-slate-400 uppercase tracking-wide">Address</div><div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm">{{ detailPatient.address }}</div></div>
                <div v-if="detailPatient.allergies"><div class="text-xs text-slate-400 uppercase tracking-wide text-red-500">Allergies / Cautions</div><div class="mt-1 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ detailPatient.allergies }}</div></div>
                <div v-if="detailPatient.notes"><div class="text-xs text-slate-400 uppercase tracking-wide">Notes</div><div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm whitespace-pre-wrap">{{ detailPatient.notes }}</div></div>
                <div class="flex justify-between pt-2">
                    <div class="flex gap-2">
                        <Button label="Edit" icon="pi pi-pencil" severity="secondary" @click="showDetailDialog = false; openEditModal(detailPatient)" />
                        <Button label="Delete" icon="pi pi-trash" severity="danger" outlined @click="showDetailDialog = false; deletePatient(detailPatient)" />
                    </div>
                    <Button label="Close" severity="secondary" outlined @click="showDetailDialog = false" />
                </div>
            </div>
        </Dialog>

        <!-- ─── Edit Dialog ──────────────────────────────────────────────── -->
        <Dialog v-model:visible="showEditModal" modal header="Edit Patient" :style="{ width: '48rem' }">
            <form class="form-grid" @submit.prevent="submitEdit">
                <div class="form-field">
                    <label class="field-label">Branch<span class="field-label__required">*</span></label>
                    <Select v-model="editForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                    <small v-if="editForm.errors.branch_id" class="field-error">{{ editForm.errors.branch_id }}</small>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Full Name<span class="field-label__required">*</span></label>
                        <InputText v-model="editForm.name" required />
                        <small v-if="editForm.errors.name" class="field-error">{{ editForm.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Phone<span class="field-label__required">*</span></label>
                        <InputText v-model="editForm.phone" required />
                        <small v-if="editForm.errors.phone" class="field-error">{{ editForm.errors.phone }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Email</label>
                        <InputText v-model="editForm.email" type="email" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Gender<span class="field-label__required">*</span></label>
                        <Select v-model="editForm.gender" :options="['Male', 'Female', 'Other']" required />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Date of Birth</label>
                        <InputText v-model="editForm.date_of_birth" type="date" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Blood Group</label>
                        <InputText v-model="editForm.blood_group" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Address</label>
                        <Textarea v-model="editForm.address" rows="2" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Allergies / Cautions</label>
                        <Textarea v-model="editForm.allergies" rows="3" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Clinical Notes</label>
                        <Textarea v-model="editForm.notes" rows="3" />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showEditModal = false" />
                    <Button type="submit" label="Save Changes" :loading="editForm.processing" />
                </div>
            </form>
        </Dialog>

        <!-- ─── Create Dialog ────────────────────────────────────────────── -->
        <Dialog v-model:visible="showCreateModal" modal header="Add Patient" :style="{ width: '48rem' }">
            <form class="form-grid" @submit.prevent="submit">
                <div class="form-field">
                    <label class="field-label">Branch<span class="field-label__required">*</span></label>
                    <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                    <small v-if="form.errors.branch_id" class="field-error">{{ form.errors.branch_id }}</small>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Full Name<span class="field-label__required">*</span></label>
                        <InputText v-model="form.name" required />
                        <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Phone<span class="field-label__required">*</span></label>
                        <InputText v-model="form.phone" required />
                        <small v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field md:col-span-1">
                        <label class="field-label">Email</label>
                        <InputText v-model="form.email" type="email" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Gender<span class="field-label__required">*</span></label>
                        <Select v-model="form.gender" :options="['Male', 'Female', 'Other']" required />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Date of Birth</label>
                        <InputText v-model="form.date_of_birth" type="date" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Blood Group</label>
                        <InputText v-model="form.blood_group" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Address</label>
                        <Textarea v-model="form.address" rows="2" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Allergies / Cautions</label>
                        <Textarea v-model="form.allergies" rows="3" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Clinical Notes</label>
                        <Textarea v-model="form.notes" rows="3" />
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
