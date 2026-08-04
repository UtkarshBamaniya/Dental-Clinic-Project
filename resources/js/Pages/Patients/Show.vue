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
        patient.value = data;
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load patient details.', life: 3000 });
        visible.value = false;
    } finally {
        loading.value = false;
    }
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
                </div>
                <div class="flex gap-2 flex-wrap justify-end">
                    <Tag
                        :value="patient.gender || 'NA'"
                        :severity="patient.gender === 'Male' ? 'info' : patient.gender === 'Female' ? 'warn' : 'secondary'"
                        rounded
                    />
                    <Tag
                        v-if="patient.blood_group"
                        :value="patient.blood_group"
                        severity="danger"
                        rounded
                    />
                </div>
            </div>

            <Divider />

            <!-- Core details grid -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">{{ val(patient.name) }}</div>
                </div>
                <div>
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">{{ val(patient.phone) }}</div>
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
                    <div class="detail-label">Branch</div>
                    <div class="detail-value">{{ val(patient.branch?.name) }}</div>
                </div>
                <div>
                    <div class="detail-label">Last Visit</div>
                    <div class="detail-value">{{ val(patient.last_visit_at) }}</div>
                </div>
            </div>

            <!-- Address -->
            <div v-if="patient.address">
                <div class="detail-label">Address</div>
                <div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm text-slate-700 leading-relaxed">
                    {{ patient.address }}
                </div>
            </div>

            <!-- Allergies (highlighted in red) -->
            <div v-if="patient.allergies">
                <div class="detail-label" style="color: #ef4444">⚠ Allergies / Cautions</div>
                <div class="mt-1 rounded-xl bg-red-50 border border-red-100 p-3 text-sm text-red-700 leading-relaxed">
                    {{ patient.allergies }}
                </div>
            </div>

            <!-- Clinical notes -->
            <div v-if="patient.notes">
                <div class="detail-label">Clinical Notes</div>
                <div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">
                    {{ patient.notes }}
                </div>
            </div>

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
