<template>
    <Dialog 
        :visible="visible" 
        @update:visible="$emit('update:visible', $event)" 
        modal 
        header="Create New Patient" 
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        :closable="!loading"
        @hide="onHide"
        @show="onShow"
    >
        <div v-if="duplicatePatient" class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4 rounded-r-md">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-amber-800 font-medium">Patient Already Exists</h3>
                    <p class="text-amber-700 mt-1 text-sm">
                        A patient with the mobile number <strong>{{ form.mobile }}</strong> already exists in the system.
                    </p>
                    <div class="mt-3 bg-white p-3 rounded border border-amber-200">
                        <div class="font-bold text-gray-900">{{ duplicatePatient.patient_code }}</div>
                        <div class="text-gray-800">{{ duplicatePatient.full_name || (duplicatePatient.first_name + ' ' + duplicatePatient.last_name) }}</div>
                        <div class="text-sm text-gray-500"><i class="pi pi-mobile text-xs mr-1"></i> {{ duplicatePatient.mobile }}</div>
                    </div>
                </div>
                <Button label="Select Existing Patient" severity="warning" size="small" @click="selectDuplicatePatient" />
            </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- SECTION 1: Patient Information -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Patient Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <InputText id="first_name" v-model="form.first_name" class="w-full" :class="{'p-invalid': errors.first_name}" :disabled="loading" />
                        <small v-if="errors.first_name" class="p-error block mt-1">{{ errors.first_name[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <InputText id="middle_name" v-model="form.middle_name" class="w-full" :class="{'p-invalid': errors.middle_name}" :disabled="loading" />
                        <small v-if="errors.middle_name" class="p-error block mt-1">{{ errors.middle_name[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <InputText id="last_name" v-model="form.last_name" class="w-full" :class="{'p-invalid': errors.last_name}" :disabled="loading" />
                        <small v-if="errors.last_name" class="p-error block mt-1">{{ errors.last_name[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">Mobile *</label>
                        <InputText id="mobile" v-model="form.mobile" class="w-full" :class="{'p-invalid': errors.mobile}" :disabled="loading" />
                        <small v-if="errors.mobile" class="p-error block mt-1">{{ errors.mobile[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <Dropdown id="gender" v-model="form.gender" :options="genderOptions" placeholder="Select Gender" class="w-full" :class="{'p-invalid': errors.gender}" :disabled="loading" />
                        <small v-if="errors.gender" class="p-error block mt-1">{{ errors.gender[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <InputText id="date_of_birth" type="date" v-model="form.date_of_birth" class="w-full" :class="{'p-invalid': errors.date_of_birth}" :disabled="loading" />
                        <small v-if="errors.date_of_birth" class="p-error block mt-1">{{ errors.date_of_birth[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="alternate_mobile" class="block text-sm font-medium text-gray-700 mb-1">Alternate Mobile</label>
                        <InputText id="alternate_mobile" v-model="form.alternate_mobile" class="w-full" :class="{'p-invalid': errors.alternate_mobile}" :disabled="loading" />
                        <small v-if="errors.alternate_mobile" class="p-error block mt-1">{{ errors.alternate_mobile[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <InputText id="email" type="email" v-model="form.email" class="w-full" :class="{'p-invalid': errors.email}" :disabled="loading" />
                        <small v-if="errors.email" class="p-error block mt-1">{{ errors.email[0] }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="field md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <Textarea id="address" v-model="form.address" rows="2" class="w-full" :class="{'p-invalid': errors.address}" :disabled="loading" />
                        <small v-if="errors.address" class="p-error block mt-1">{{ errors.address[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <InputText id="city" v-model="form.city" class="w-full" :class="{'p-invalid': errors.city}" :disabled="loading" />
                        <small v-if="errors.city" class="p-error block mt-1">{{ errors.city[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                        <InputText id="state" v-model="form.state" class="w-full" :class="{'p-invalid': errors.state}" :disabled="loading" />
                        <small v-if="errors.state" class="p-error block mt-1">{{ errors.state[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="pincode" class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                        <InputText id="pincode" v-model="form.pincode" class="w-full" :class="{'p-invalid': errors.pincode}" :disabled="loading" />
                        <small v-if="errors.pincode" class="p-error block mt-1">{{ errors.pincode[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                        <InputText id="occupation" v-model="form.occupation" class="w-full" :class="{'p-invalid': errors.occupation}" :disabled="loading" />
                        <small v-if="errors.occupation" class="p-error block mt-1">{{ errors.occupation[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="referred_by" class="block text-sm font-medium text-gray-700 mb-1">Referred By</label>
                        <InputText id="referred_by" v-model="form.referred_by" class="w-full" :class="{'p-invalid': errors.referred_by}" :disabled="loading" />
                        <small v-if="errors.referred_by" class="p-error block mt-1">{{ errors.referred_by[0] }}</small>
                    </div>

                    <div class="field">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <Dropdown id="status" v-model="form.status" :options="['active', 'inactive']" class="w-full" :class="{'p-invalid': errors.status}" :disabled="loading" />
                        <small v-if="errors.status" class="p-error block mt-1">{{ errors.status[0] }}</small>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Medical History -->
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Medical History</h3>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="field md:w-1/2">
                        <label for="blood_group" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                        <Dropdown id="blood_group" v-model="form.medical_history.blood_group" :options="bloodGroupOptions" placeholder="Select Blood Group" class="w-full" :class="{'p-invalid': errors['medical_history.blood_group']}" :disabled="loading" showClear />
                        <small v-if="errors['medical_history.blood_group']" class="p-error block mt-1">{{ errors['medical_history.blood_group'][0] }}</small>
                    </div>

                    <div class="field">
                        <label for="current_medicine" class="block text-sm font-medium text-gray-700 mb-1">Current Medicine</label>
                        <Textarea id="current_medicine" v-model="form.medical_history.current_medicine" rows="2" class="w-full" :class="{'p-invalid': errors['medical_history.current_medicine']}" :disabled="loading" />
                        <small v-if="errors['medical_history.current_medicine']" class="p-error block mt-1">{{ errors['medical_history.current_medicine'][0] }}</small>
                    </div>

                    <div class="field">
                        <label for="previous_dental_treatment" class="block text-sm font-medium text-gray-700 mb-1">Previous Dental Treatment</label>
                        <Textarea id="previous_dental_treatment" v-model="form.medical_history.previous_dental_treatment" rows="2" class="w-full" :class="{'p-invalid': errors['medical_history.previous_dental_treatment']}" :disabled="loading" />
                        <small v-if="errors['medical_history.previous_dental_treatment']" class="p-error block mt-1">{{ errors['medical_history.previous_dental_treatment'][0] }}</small>
                    </div>

                    <div class="field">
                        <label for="other_notes" class="block text-sm font-medium text-gray-700 mb-1">Other Notes</label>
                        <Textarea id="other_notes" v-model="form.medical_history.other_notes" rows="2" class="w-full" :class="{'p-invalid': errors['medical_history.other_notes']}" :disabled="loading" />
                        <small v-if="errors['medical_history.other_notes']" class="p-error block mt-1">{{ errors['medical_history.other_notes'][0] }}</small>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancel" icon="pi pi-times" @click="$emit('update:visible', false)" class="p-button-text" :disabled="loading" />
            <Button label="Save Patient & Continue" icon="pi pi-check" @click="submitForm" :loading="loading" autofocus />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    initialMobile: {
        type: String,
        default: ''
    },
    initialFirstName: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:visible', 'created', 'selected-existing']);

const toast = useToast();
const loading = ref(false);
const errors = ref({});
const duplicatePatient = ref(null);

const genderOptions = ['Male', 'Female', 'Other'];
const bloodGroupOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const initialFormState = {
    first_name: '',
    middle_name: '',
    last_name: '',
    gender: null,
    date_of_birth: null,
    mobile: '',
    alternate_mobile: '',
    email: '',
    address: '',
    city: '',
    state: '',
    pincode: '',
    occupation: '',
    referred_by: '',
    status: 'active',
    medical_history: {
        blood_group: null,
        current_medicine: '',
        previous_dental_treatment: '',
        other_notes: ''
    }
};

const form = reactive(JSON.parse(JSON.stringify(initialFormState)));

function resetForm() {
    Object.assign(form, JSON.parse(JSON.stringify(initialFormState)));
    errors.value = {};
    duplicatePatient.value = null;
}

function onShow() {
    resetForm();
    if (props.initialMobile) {
        form.mobile = props.initialMobile;
    }
    if (props.initialFirstName) {
        // Simple heuristic to split name if it contains spaces
        const parts = props.initialFirstName.trim().split(/\s+/);
        if (parts.length > 1) {
            form.first_name = parts[0];
            form.last_name = parts.slice(1).join(' ');
        } else {
            form.first_name = parts[0];
        }
    }
}

function onHide() {
    if (!loading.value) {
        // Do not reset here, handled onShow so state is preserved if canceled by mistake
    }
}

async function submitForm() {
    loading.value = true;
    errors.value = {};
    duplicatePatient.value = null;

    try {
        const response = await axios.post(route('patients.store'), form, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        toast.add({ severity: 'success', summary: 'Success', detail: 'Patient created successfully', life: 3000 });
        emit('created', response.data.data);
        emit('update:visible', false);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            // Validation error or Duplicate mobile
            if (error.response.data.errors) {
                errors.value = error.response.data.errors;
            } else if (error.response.data.patient) {
                // Custom duplicate mobile response
                duplicatePatient.value = error.response.data.patient;
                toast.add({ severity: 'warn', summary: 'Duplicate Mobile', detail: error.response.data.message || 'A patient with this mobile number already exists.', life: 5000 });
            }
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create patient. Please try again.', life: 3000 });
            console.error("Failed to create patient", error);
        }
    } finally {
        loading.value = false;
    }
}

function selectDuplicatePatient() {
    if (duplicatePatient.value) {
        emit('selected-existing', duplicatePatient.value);
        emit('update:visible', false);
    }
}
</script>

<style scoped>
/* Scoped styles */
</style>
