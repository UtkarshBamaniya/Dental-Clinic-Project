<script setup>
/**
 * Patients/Form.vue
 *
 * Slide-in Dialog for creating and editing patients.
 * Uses axios (not Inertia useForm) so the DataTable can refresh without a
 * full-page reload.
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
    routeName: { type: String, default: 'patients' },
});

const toast   = useToast();
const visible = ref(false);
const isEdit  = ref(false);
const editId  = ref(null);
const saving  = ref(false);
const errors  = ref({});

const blankForm = () => ({
    first_name:    '',
    middle_name:   '',
    last_name:     '',
    mobile:        '',
    alternate_mobile: '',
    email:         '',
    gender:        'Male',
    date_of_birth: '',
    address:       '',
    city:          '',
    state:         '',
    pincode:       '',
    occupation:    '',
    referred_by:   '',
    status:        'active',
    medical_history: {
        blood_group:   '',
        current_medicine: '',
        previous_dental_treatment: '',
        other_notes: '',
    }
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

/** Open in EDIT mode – fetches data via patients.show (JSON) */
const openEdit = async (id) => {
    isEdit.value  = true;
    editId.value  = id;
    resetForm();
    visible.value = true;

    try {
        const { data } = await axios.get(route(`${props.routeName}.show`, id), {
            headers: { Accept: 'application/json' },
        });
        const p = data.data; // PatientResource wraps in data
        Object.assign(form, {
            first_name:    p.first_name,
            middle_name:   p.middle_name    ?? '',
            last_name:     p.last_name      ?? '',
            mobile:        p.mobile,
            alternate_mobile: p.alternate_mobile ?? '',
            email:         p.email          ?? '',
            gender:        p.gender         ?? 'Male',
            date_of_birth: p.date_of_birth  ?? '',
            address:       p.address        ?? '',
            city:          p.city           ?? '',
            state:         p.state          ?? '',
            pincode:       p.pincode        ?? '',
            occupation:    p.occupation     ?? '',
            referred_by:   p.referred_by    ?? '',
            status:        p.status         ?? 'active',
            medical_history: {
                blood_group:   p.medical_history?.blood_group    ?? '',
                current_medicine: p.medical_history?.current_medicine ?? '',
                previous_dental_treatment: p.medical_history?.previous_dental_treatment ?? '',
                other_notes: p.medical_history?.other_notes ?? '',
            }
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
            if (err.response.data.patient) {
                toast.add({ severity: 'warn', summary: 'Duplicate Mobile', detail: err.response.data.message, life: 5000 });
            }
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
        :style="{ width: '60rem' }"
        :breakpoints="{ '768px': '95vw' }"
        @hide="resetForm"
    >
        <form class="space-y-5" @submit.prevent="submit">

            <!-- Name -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">First Name <span class="field-label__required">*</span></label>
                    <InputText v-model="form.first_name" placeholder="First name" class="w-full" />
                    <small v-if="errors.first_name" class="field-error">{{ errors.first_name[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Middle Name</label>
                    <InputText v-model="form.middle_name" placeholder="Middle name" class="w-full" />
                    <small v-if="errors.middle_name" class="field-error">{{ errors.middle_name[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Last Name</label>
                    <InputText v-model="form.last_name" placeholder="Last name" class="w-full" />
                    <small v-if="errors.last_name" class="field-error">{{ errors.last_name[0] }}</small>
                </div>
            </div>

            <!-- Contact -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Mobile <span class="field-label__required">*</span></label>
                    <InputText v-model="form.mobile" placeholder="9876543210" class="w-full" />
                    <small v-if="errors.mobile" class="field-error">{{ errors.mobile[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Alternate Mobile</label>
                    <InputText v-model="form.alternate_mobile" placeholder="Optional" class="w-full" />
                    <small v-if="errors.alternate_mobile" class="field-error">{{ errors.alternate_mobile[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Email</label>
                    <InputText v-model="form.email" type="email" placeholder="email@example.com" class="w-full" />
                    <small v-if="errors.email" class="field-error">{{ errors.email[0] }}</small>
                </div>
            </div>

            <!-- Demographics -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">Gender</label>
                    <Select v-model="form.gender" :options="['Male', 'Female', 'Other']" placeholder="Select" class="w-full" />
                    <small v-if="errors.gender" class="field-error">{{ errors.gender[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Date of Birth</label>
                    <InputText v-model="form.date_of_birth" type="date" class="w-full" />
                    <small v-if="errors.date_of_birth" class="field-error">{{ errors.date_of_birth[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Occupation</label>
                    <InputText v-model="form.occupation" placeholder="Occupation" class="w-full" />
                    <small v-if="errors.occupation" class="field-error">{{ errors.occupation[0] }}</small>
                </div>
            </div>

            <!-- Address -->
            <div class="form-field">
                <label class="field-label">Address</label>
                <Textarea v-model="form.address" placeholder="Full address" rows="2" class="w-full" autoResize />
                <small v-if="errors.address" class="field-error">{{ errors.address[0] }}</small>
            </div>

            <!-- Location -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="form-field">
                    <label class="field-label">City</label>
                    <InputText v-model="form.city" placeholder="City" class="w-full" />
                    <small v-if="errors.city" class="field-error">{{ errors.city[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">State</label>
                    <InputText v-model="form.state" placeholder="State" class="w-full" />
                    <small v-if="errors.state" class="field-error">{{ errors.state[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Pincode</label>
                    <InputText v-model="form.pincode" placeholder="Pincode" class="w-full" />
                    <small v-if="errors.pincode" class="field-error">{{ errors.pincode[0] }}</small>
                </div>
            </div>

            <!-- Other -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Referred By</label>
                    <InputText v-model="form.referred_by" placeholder="Referral source" class="w-full" />
                    <small v-if="errors.referred_by" class="field-error">{{ errors.referred_by[0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Status</label>
                    <Select v-model="form.status" :options="['active', 'inactive']" placeholder="Status" class="w-full" />
                    <small v-if="errors.status" class="field-error">{{ errors.status[0] }}</small>
                </div>
            </div>

            <h3 class="text-lg font-medium text-slate-800 mt-6 mb-2 border-b pb-2">Medical History</h3>

            <!-- Medical History -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Blood Group</label>
                    <Select v-model="form.medical_history.blood_group" :options="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']" placeholder="Select Blood Group" class="w-full" showClear />
                    <small v-if="errors['medical_history.blood_group']" class="field-error">{{ errors['medical_history.blood_group'][0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Current Medicine</label>
                    <Textarea v-model="form.medical_history.current_medicine" placeholder="Any medicines patient is currently taking" rows="2" class="w-full" autoResize />
                    <small v-if="errors['medical_history.current_medicine']" class="field-error">{{ errors['medical_history.current_medicine'][0] }}</small>
                </div>
            </div>
            
            <div class="grid gap-4 md:grid-cols-2">
                <div class="form-field">
                    <label class="field-label">Previous Dental Treatment</label>
                    <Textarea v-model="form.medical_history.previous_dental_treatment" placeholder="Details of previous treatments" rows="2" class="w-full" autoResize />
                    <small v-if="errors['medical_history.previous_dental_treatment']" class="field-error">{{ errors['medical_history.previous_dental_treatment'][0] }}</small>
                </div>
                <div class="form-field">
                    <label class="field-label">Other Notes / Cautions</label>
                    <Textarea v-model="form.medical_history.other_notes" placeholder="Allergies, alerts, or clinical notes" rows="2" class="w-full" autoResize />
                    <small v-if="errors['medical_history.other_notes']" class="field-error">{{ errors['medical_history.other_notes'][0] }}</small>
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
