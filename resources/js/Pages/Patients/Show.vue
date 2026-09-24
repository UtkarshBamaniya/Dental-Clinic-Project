<script setup>
/**
 * Patients/Show.vue
 *
 * Read-only patient details dialog.
 * Fetches data via axios GET patients.show (JSON response).
 *
 * Exposed method (called by Index.vue via ref):
 *   openShow(id) – loads patient and shows the dialog
 */
import { route } from 'ziggy-js';
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';

const props = defineProps({
    routeName: { type: String, default: 'patients' },
});

const toast   = useToast();
const visible = ref(false);
const patient = ref(null);
const loading = ref(false);

const openShow = async (id) => {
    visible.value = true;
    loading.value = true;
    patient.value = null;

    try {
        const { data } = await axios.get(route(`${props.routeName}.show`, id), {
            headers: { Accept: 'application/json' },
        });
        patient.value = data.data;
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load patient details.', life: 3000 });
        visible.value = false;
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const val = (v) => v || '—';

defineExpose({ openShow });
</script>

<template>
    <Dialog
        v-model:visible="visible"
        header="Patient Details"
        :modal="true"
        :style="{ width: '46rem' }"
        :breakpoints="{ '768px': '95vw' }"
    >
        <!-- Loading state -->
        <div v-if="loading" class="py-12 flex flex-col items-center text-slate-400">
            <i class="pi pi-spin pi-spinner text-3xl mb-2" />
            <p class="text-sm">Loading patient details…</p>
        </div>

        <!-- Patient data -->
        <div v-else-if="patient" class="space-y-5">

            <!-- Code + Tags row -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1 font-semibold">
                        Patient Code
                    </div>
                    <div class="font-mono text-lg font-bold text-slate-800">
                        {{ patient.patient_code }}
                    </div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-2 mb-1 font-semibold">
                        Registered On
                    </div>
                    <div class="font-mono text-sm text-slate-600">
                        {{ formatDate(patient.created_at) }}
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap justify-end">
                    <Tag
                        :value="patient.status || 'NA'"
                        :severity="patient.status === 'active' ? 'success' : 'secondary'"
                        rounded
                    />
                    <Tag
                        :value="patient.gender || 'NA'"
                        :severity="patient.gender === 'Male' ? 'info' : patient.gender === 'Female' ? 'warn' : 'secondary'"
                        rounded
                    />
                    <Tag
                        v-if="patient.medical_history?.blood_group"
                        :value="patient.medical_history.blood_group"
                        severity="danger"
                        rounded
                    />
                </div>
            </div>

            <Divider />

            <!-- Core details grid -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 md:grid-cols-3">
                <div>
                    <div class="detail-label">First Name</div>
                    <div class="detail-value">{{ val(patient.first_name) }}</div>
                </div>
                <div>
                    <div class="detail-label">Middle Name</div>
                    <div class="detail-value">{{ val(patient.middle_name) }}</div>
                </div>
                <div>
                    <div class="detail-label">Last Name</div>
                    <div class="detail-value">{{ val(patient.last_name) }}</div>
                </div>
                <div>
                    <div class="detail-label">Mobile</div>
                    <div class="detail-value">{{ val(patient.mobile) }}</div>
                </div>
                <div>
                    <div class="detail-label">Alternate Mobile</div>
                    <div class="detail-value">{{ val(patient.alternate_mobile) }}</div>
                </div>
                <div>
                    <div class="detail-label">Email</div>
                    <div class="detail-value">{{ val(patient.email) }}</div>
                </div>
                <div>
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value">{{ val(patient.date_of_birth) }}</div>
                </div>
                <div>
                    <div class="detail-label">Occupation</div>
                    <div class="detail-value">{{ val(patient.occupation) }}</div>
                </div>
                <div>
                    <div class="detail-label">Referred By</div>
                    <div class="detail-value">{{ val(patient.referred_by) }}</div>
                </div>
                <div>
                    <div class="detail-label">City</div>
                    <div class="detail-value">{{ val(patient.city) }}</div>
                </div>
                <div>
                    <div class="detail-label">State</div>
                    <div class="detail-value">{{ val(patient.state) }}</div>
                </div>
                <div>
                    <div class="detail-label">Pincode</div>
                    <div class="detail-value">{{ val(patient.pincode) }}</div>
                </div>
            </div>

            <!-- Address -->
            <div v-if="patient.address">
                <div class="detail-label">Address</div>
                <div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm text-slate-700 leading-relaxed">
                    {{ patient.address }}
                </div>
            </div>

            <template v-if="patient.medical_history">
                <!-- Current Medicine -->
                <div v-if="patient.medical_history.current_medicine">
                    <div class="detail-label">Current Medicine</div>
                    <div class="mt-1 rounded-xl bg-slate-50 border border-slate-100 p-3 text-sm text-slate-700 leading-relaxed">
                        {{ patient.medical_history.current_medicine }}
                    </div>
                </div>
                
                <!-- Previous Dental Treatment -->
                <div v-if="patient.medical_history.previous_dental_treatment">
                    <div class="detail-label">Previous Dental Treatment</div>
                    <div class="mt-1 rounded-xl bg-slate-50 border border-slate-100 p-3 text-sm text-slate-700 leading-relaxed">
                        {{ patient.medical_history.previous_dental_treatment }}
                    </div>
                </div>

                <!-- Notes / Cautions (highlighted in red if they exist) -->
                <div v-if="patient.medical_history.other_notes">
                    <div class="detail-label" style="color: #ef4444">⚠ Other Notes / Cautions</div>
                    <div class="mt-1 rounded-xl bg-red-50 border border-red-100 p-3 text-sm text-red-700 leading-relaxed">
                        {{ patient.medical_history.other_notes }}
                    </div>
                </div>
            </template>

        </div>

        <template #footer>
            <Button label="Close" icon="pi pi-times" text @click="visible = false" />
        </template>
    </Dialog>
</template>

<style scoped>
.detail-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #94a3b8;
    margin-bottom: 3px;
}
.detail-value {
    font-size: 0.93rem;
    color: #1e293b;
    font-weight: 500;
}
</style>
