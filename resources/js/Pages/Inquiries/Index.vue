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
    inquiries: Array,
    branches: Array,
    staff: Array,
    sources: Array,
    filters: Object,
});

// ── Local filters (client-side search/branch/status) ───────────────────────
const localFilters = ref({ search: '', branchId: null, status: null });

// ── Date range filter (server-side) ────────────────────────────────────────
const dateFrom = ref(props.filters?.from_date ? new Date(props.filters.from_date) : null);
const dateTo   = ref(props.filters?.to_date   ? new Date(props.filters.to_date)   : null);

function formatDate(date) {
    if (!date) return null;
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
function generate() {
    router.get(route('inquiries.index'), {
        from_date: formatDate(dateFrom.value),
        to_date:   formatDate(dateTo.value),
    }, { preserveState: true, preserveScroll: true });
}
function resetDateFilter() {
    dateFrom.value = null;
    dateTo.value   = null;
    router.get(route('inquiries.index'), {}, { preserveState: true, preserveScroll: true });
}

// ── Create form ─────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
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

function resetForm() {
    form.reset('name', 'phone', 'email', 'treatment_interest', 'next_follow_up_at', 'notes');
    form.clearErrors();
    form.branch_id   = props.branches[0]?.id ?? null;
    form.assigned_to = props.staff[0]?.id ?? null;
    form.source      = props.sources[0] ?? 'Walk-in';
    form.status      = 'new';
    form.priority    = 'warm';
}
function openCreateModal() { resetForm(); showCreateModal.value = true; }
function submit() {
    form.post(route('inquiries.store'), {
        preserveScroll: true,
        onSuccess: () => { resetForm(); showCreateModal.value = false; },
    });
}

// ── Edit form ────────────────────────────────────────────────────────────────
const showEditModal    = ref(false);
const editingInquiry   = ref(null);
const editForm = useForm({
    branch_id: null, assigned_to: null, name: '', phone: '', email: '',
    source: '', treatment_interest: '', status: '', priority: '',
    next_follow_up_at: '', notes: '',
});

function openEditModal(inquiry) {
    editingInquiry.value          = inquiry;
    editForm.branch_id            = inquiry.branch_id;
    editForm.assigned_to          = inquiry.assigned_to;
    editForm.name                 = inquiry.name;
    editForm.phone                = inquiry.phone;
    editForm.email                = inquiry.email ?? '';
    editForm.source               = inquiry.source;
    editForm.treatment_interest   = inquiry.treatment_interest;
    editForm.status               = inquiry.status;
    editForm.priority             = inquiry.priority;
    editForm.next_follow_up_at    = inquiry.next_follow_up_at ?? '';
    editForm.notes                = inquiry.notes ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}
function submitEdit() {
    editForm.put(route('inquiries.update', editingInquiry.value.id), {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; editingInquiry.value = null; },
    });
}

// ── Show dialog ──────────────────────────────────────────────────────────────
const showDetailDialog  = ref(false);
const detailInquiry     = ref(null);
function openShowDialog(inquiry) { detailInquiry.value = inquiry; showDetailDialog.value = true; }

// ── Delete ───────────────────────────────────────────────────────────────────
function deleteInquiry(inquiry) {
    if (!window.confirm(`Delete inquiry for "${inquiry.name}"? This cannot be undone.`)) return;
    router.delete(route('inquiries.destroy', inquiry.id), { preserveScroll: true });
}

// ── Context menu ─────────────────────────────────────────────────────────────
const ctxMenu     = ref();
const ctxRow      = ref(null);
const ctxMenuItems = computed(() => [
    {
        label: 'Show',
        icon: 'pi pi-eye',
        command: () => openShowDialog(ctxRow.value),
    },
    {
        label: 'Edit',
        icon: 'pi pi-pencil',
        command: () => openEditModal(ctxRow.value),
    },
    { separator: true },
    {
        label: 'Delete',
        icon: 'pi pi-trash',
        class: 'text-red-600',
        command: () => deleteInquiry(ctxRow.value),
    },
]);
function onRowContextMenu(event) {
    ctxRow.value = event.data;
    ctxMenu.value.show(event.originalEvent);
}

// ── Filtered list ─────────────────────────────────────────────────────────────
const filteredInquiries = computed(() =>
    props.inquiries.filter((inquiry) => {
        const search = localFilters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            inquiry.name?.toLowerCase().includes(search) ||
            inquiry.phone?.toLowerCase().includes(search) ||
            inquiry.treatment_interest?.toLowerCase().includes(search);
        const matchesBranch = !localFilters.value.branchId || inquiry.branch_id === localFilters.value.branchId;
        const matchesStatus = !localFilters.value.status || inquiry.status === localFilters.value.status;
        return matchesSearch && matchesBranch && matchesStatus;
    }),
);

// ── Other actions ─────────────────────────────────────────────────────────────
function bookFromInquiry(inquiry) { router.get(route('appointments.index'), { inquiry: inquiry.id }); }
function markConverted(inquiry)   { router.patch(route('inquiries.converted', inquiry.id), {}, { preserveScroll: true }); }
</script>

<template>
    <AuthenticatedLayout title="Inquiries">
        <!-- Toolbar -->
        <div class="page-toolbar">
            <div class="page-toolbar__filters">
                <InputText v-model="localFilters.search" placeholder="Search by name, phone, or treatment" />
                <Select v-model="localFilters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                <Select v-model="localFilters.status" :options="['new', 'follow_up', 'quoted', 'converted']" placeholder="Filter by status" showClear />
                <DatePicker v-model="dateFrom" placeholder="From Date" dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                <DatePicker v-model="dateTo"   placeholder="To Date"   dateFormat="yy-mm-dd" showIcon iconDisplay="input" />
                <Button label="Generate" icon="pi pi-filter" @click="generate" />
                <Button v-if="dateFrom || dateTo" icon="pi pi-times" severity="secondary" outlined @click="resetDateFilter" v-tooltip="'Clear date filter'" />
            </div>
            <div class="page-toolbar__actions">
                <Button label="Add Inquiry" icon="pi pi-plus" @click="openCreateModal" />
            </div>
        </div>

        <!-- Context Menu -->
        <ContextMenu ref="ctxMenu" :model="ctxMenuItems" />

        <!-- Table -->
        <Card class="glass-panel rounded-[28px] border-none shadow-none">
            <template #title>
                <div class="text-sm font-medium text-slate-500">Inquiry List</div>
            </template>
            <template #content>
                <DataTable
                    :value="filteredInquiries"
                    stripedRows
                    responsiveLayout="scroll"
                    contextMenu
                    @row-contextmenu="onRowContextMenu"
                >
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

        <!-- ─── Show Dialog ─────────────────────────────────────────────── -->
        <Dialog v-model:visible="showDetailDialog" modal header="Inquiry Details" :style="{ width: '44rem' }">
            <div v-if="detailInquiry" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Name</div><div class="mt-1 font-medium">{{ detailInquiry.name }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Phone</div><div class="mt-1 font-medium">{{ detailInquiry.phone }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Email</div><div class="mt-1 font-medium">{{ detailInquiry.email || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Source</div><div class="mt-1 font-medium">{{ detailInquiry.source }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Treatment Interest</div><div class="mt-1 font-medium">{{ detailInquiry.treatment_interest }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Branch</div><div class="mt-1 font-medium">{{ detailInquiry.branch?.name || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Status</div><div class="mt-1"><Tag :value="detailInquiry.status" severity="info" rounded /></div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Priority</div><div class="mt-1"><Tag :value="detailInquiry.priority" severity="warn" rounded /></div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Next Follow Up</div><div class="mt-1 font-medium">{{ detailInquiry.next_follow_up_at || '—' }}</div></div>
                    <div><div class="text-xs text-slate-400 uppercase tracking-wide">Assignee</div><div class="mt-1 font-medium">{{ detailInquiry.assignee?.name || '—' }}</div></div>
                </div>
                <div v-if="detailInquiry.notes">
                    <div class="text-xs text-slate-400 uppercase tracking-wide">Notes</div>
                    <div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm text-slate-700 whitespace-pre-wrap">{{ detailInquiry.notes }}</div>
                </div>
                <div class="flex justify-between pt-2">
                    <div class="flex gap-2">
                        <Button label="Edit" icon="pi pi-pencil" severity="secondary" @click="showDetailDialog = false; openEditModal(detailInquiry)" />
                        <Button label="Delete" icon="pi pi-trash" severity="danger" outlined @click="showDetailDialog = false; deleteInquiry(detailInquiry)" />
                    </div>
                    <Button label="Close" severity="secondary" outlined @click="showDetailDialog = false" />
                </div>
            </div>
        </Dialog>

        <!-- ─── Edit Dialog ─────────────────────────────────────────────── -->
        <Dialog v-model:visible="showEditModal" modal header="Edit Inquiry" :style="{ width: '52rem' }">
            <form class="form-grid" @submit.prevent="submitEdit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="editForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="editForm.errors.branch_id" class="field-error">{{ editForm.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Assignee</label>
                        <Select v-model="editForm.assigned_to" :options="staff" optionLabel="name" optionValue="id" showClear />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Inquiry Name<span class="field-label__required">*</span></label>
                        <InputText v-model="editForm.name" required />
                        <small v-if="editForm.errors.name" class="field-error">{{ editForm.errors.name }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Phone<span class="field-label__required">*</span></label>
                        <InputText v-model="editForm.phone" required />
                        <small v-if="editForm.errors.phone" class="field-error">{{ editForm.errors.phone }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Email</label>
                        <InputText v-model="editForm.email" type="email" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Source<span class="field-label__required">*</span></label>
                        <Select v-model="editForm.source" :options="sources" required />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Treatment Interest<span class="field-label__required">*</span></label>
                        <InputText v-model="editForm.treatment_interest" required />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Status<span class="field-label__required">*</span></label>
                        <Select v-model="editForm.status" :options="['new', 'follow_up', 'quoted', 'converted']" required />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Priority<span class="field-label__required">*</span></label>
                        <Select v-model="editForm.priority" :options="['cold', 'warm', 'hot']" required />
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Next Follow Up</label>
                    <InputText v-model="editForm.next_follow_up_at" type="datetime-local" />
                </div>

                <div class="form-field">
                    <label class="field-label">Notes</label>
                    <Textarea v-model="editForm.notes" rows="4" />
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showEditModal = false" />
                    <Button type="submit" label="Save Changes" :loading="editForm.processing" />
                </div>
            </form>
        </Dialog>

        <!-- ─── Create Dialog ───────────────────────────────────────────── -->
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
