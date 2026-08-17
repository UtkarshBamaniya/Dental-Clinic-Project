<script setup>
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

defineProps({
    appointment: Object,
});

const visible = defineModel('visible', { type: Boolean, default: false });
const emit = defineEmits(['edit', 'delete']);
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Appointment Details" :style="{ width: '48rem' }">
        <div v-if="appointment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Patient</div><div class="mt-1 font-medium">{{ appointment.patient?.name || '—' }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Doctor</div><div class="mt-1 font-medium">{{ appointment.doctor_profile?.user?.name || 'Auto assigned' }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Date</div><div class="mt-1 font-medium">{{ appointment.appointment_date }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Time</div><div class="mt-1 font-medium">{{ appointment.start_time }} – {{ appointment.end_time }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Treatment</div><div class="mt-1 font-medium">{{ appointment.treatment_name }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Specialty</div><div class="mt-1 font-medium">{{ appointment.specialty }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Visit Type</div><div class="mt-1 font-medium">{{ appointment.visit_type }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Token</div><div class="mt-1 font-medium">#{{ appointment.token_no }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Status</div><div class="mt-1"><Tag :value="appointment.status" severity="info" rounded /></div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Estimated</div><div class="mt-1 font-medium">Rs. {{ Number(appointment.estimated_amount).toLocaleString() }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Paid</div><div class="mt-1 font-medium">Rs. {{ Number(appointment.paid_amount).toLocaleString() }}</div></div>
                <div><div class="text-xs text-slate-400 uppercase tracking-wide">Branch</div><div class="mt-1 font-medium">{{ appointment.branch?.name || '—' }}</div></div>
            </div>
            <div v-if="appointment.notes"><div class="text-xs text-slate-400 uppercase tracking-wide">Notes</div><div class="mt-1 rounded-xl bg-slate-50 p-3 text-sm whitespace-pre-wrap">{{ appointment.notes }}</div></div>
            <div class="flex justify-between pt-2">
                <div class="flex gap-2">
                    <Button label="Edit" icon="pi pi-pencil" severity="secondary" @click="visible = false; emit('edit', appointment)" />
                    <Button label="Delete" icon="pi pi-trash" severity="danger" outlined @click="visible = false; emit('delete', appointment)" />
                </div>
                <Button label="Close" severity="secondary" outlined @click="visible = false" />
            </div>
        </div>
    </Dialog>
</template>
