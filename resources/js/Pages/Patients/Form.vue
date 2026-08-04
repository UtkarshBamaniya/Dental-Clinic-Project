<script setup>
/**
 * Patients/Form.vue
 *
 * Slide-in Dialog for creating and editing patients.
 * Uses axios (not Inertia useForm) so the DataTable can refresh without a
 * full-page reload — matching the reference AreaMaster pattern.
 *
 * Exposed methods (called by Index.vue via ref):
 *   openNew()        – opens the form in create mode
 *   openEdit(id)     – fetches patient data then opens the form in edit mode
 *
 * Emits:
 *   fetch-data – after a successful store or update
 */
import { route } from 'ziggy-js';
import axios from 'axios';
import { reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const emit = defineEmits(['fetch-data']);

const props = defineProps({
    branches:  { type: Array,  default: () => [] },
    routeName: { type: String, default: 'patients' },
});

const toast   = useToast();
const visible = ref(false);
const isEdit  = ref(false);
const editId  = ref(null);
const saving  = ref(false);
const errors  = ref({});

const blankForm = () => ({
    branch_id:     props.branches[0]?.id ?? null,
    name:          '',
    phone:         '',
    email:         '',
    gender:        'Male',
    date_of_birth: '',
    blood_group:   '',
    address:       '',
    allergies:     '',
    notes:         '',
});

const form = reactive(blankForm());

const resetForm = () => {
    Object.assign(form, blankForm());
    errors.value = {};
};

/** Open in CREATE mode */
const openNew = () => {
    isEdit.value  = false;
    editId.value  = null;
    resetForm();
    visible.value = true;
};

/** Open in EDIT mode – fetches data via patients.edit (JSON) */
const openEdit = async (id) => {
    isEdit.value  = true;
    editId.value  = id;
    resetForm();
    visible.value = true;

    try {
        const { data: p } = await axios.get(route(`${props.routeName}.edit`, id), {
            headers: { Accept: 'application/json' },
        });
        Object.assign(form, {
            branch_id:     p.branch_id,
            name:          p.name,
            phone:         p.phone,
            email:         p.email          ?? '',
            gender:        p.gender         ?? 'Male',
            date_of_birth: p.date_of_birth  ?? '',   // already YYYY-MM-DD from Laravel cast
            blood_group:   p.blood_group    ?? '',
            address:       p.address        ?? '',
            allergies:     p.allergies      ?? '',
            notes:         p.notes          ?? '',
        });
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load patient data.', life: 3000 });
        visible.value = false;
    }
};

/** Submit – POST for create, PUT for update */
const submit = async () => {
    saving.value = true;
    errors.value = {};

    try {
        if (isEdit.value) {
            await axios.put(route(`${props.routeName}.update`, editId.value), form, {
                headers: { Accept: 'application/json' },
            });
        } else {
            await axios.post(route(`${props.routeName}.store`), form, {
                headers: { Accept: 'application/json' },
            });
        }

        toast.add({
            severity: 'success',
            summary:  'Success',
            detail:   isEdit.value ? 'Patient updated successfully.' : 'Patient registered successfully.',
            life: 3000,
        });
        visible.value = false;
        emit('fetch-data');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors ?? {};
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'An unexpected error occurred.', life: 3000 });
        }
    } finally {
        saving.value = false;
    }
};

defineExpose({ openNew, openEdit });
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :header="isEdit ? 'Edit Patient' : 'Add New Patient'"
        :modal="true"
        :style="{ width: '52rem' }"
        :breakpoints="{ '768px': '95vw' }"
        @hide="resetForm"
    >
        <form class="space-y-5" @submit.prevent="submit">

            <!-- Branch -->
            <div class="form-field">
                <label class="field-label">Branch <span class="field-label__required">*</span></label>
                <Select
                    v-model="form.branch_id"
                    :options="branches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select branch"
                    class="w-full"
                />
                <small v-if="errors.branch_id" class="field-error">{{ errors.branch_id[0] }}</small>
            </div>

            <!-- Name + Phone -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Full Name <span class="field-label__required">*</span></label>
                    <InputText v-model="form.name" placeholder="Patient full name" class="w-full" />
                    <small v-if="errors.name" class="field-error">{{ errors.name[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Phone <span class="field-label__required">*</span></label>
                    <InputText v-model="form.phone" placeholder="+91 98765 43210" class="w-full" />
                    <small v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</small>
                </div>
            </div>

            <!-- Email + Gender + DOB -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Email</label>
                    <InputText v-model="form.email" type="email" placeholder="email@example.com" class="w-full" />
                    <small v-if="errors.email" class="field-error">{{ errors.email[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Gender <span class="field-label__required">*</span></label>
                    <Select v-model="form.gender" :options="['Male', 'Female', 'Other']" placeholder="Select" class="w-full" />
                    <small v-if="errors.gender" class="field-error">{{ errors.gender[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Date of Birth</label>
                    <InputText v-model="form.date_of_birth" type="date" class="w-full" />
                </div>
            </div>

            <!-- Blood Group + Address -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Blood Group</label>
                    <InputText v-model="form.blood_group" placeholder="e.g. A+" class="w-full" />
                </div>
                <div class="form-field">
                    <label class="field-label">Address</label>
                    <Textarea v-model="form.address" placeholder="Full address" rows="2" class="w-full" autoResize />
                </div>
            </div>

            <!-- Allergies + Notes -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label" style="color: #ef4444">⚠ Allergies / Cautions</label>
                    <Textarea v-model="form.allergies" placeholder="List any known allergies or cautions" rows="3" class="w-full" autoResize />
                </div>
                <div class="form-field">
                    <label class="field-label">Clinical Notes</label>
                    <Textarea v-model="form.notes" placeholder="Additional clinical notes" rows="3" class="w-full" autoResize />
                </div>
            </div>

        </form>

        <template #footer>
            <Button label="Cancel" icon="pi pi-times" text :disabled="saving" @click="visible = false" />
            <Button
                :label="isEdit ? 'Save Changes' : 'Register Patient'"
                icon="pi pi-check"
                :loading="saving"
                @click="submit"
            />
        </template>
    </Dialog>
</template>
