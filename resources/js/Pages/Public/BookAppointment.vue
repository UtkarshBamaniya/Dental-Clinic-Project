<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const props = defineProps({
    branches: Array,
    specialties: Array,
});

const page = usePage();
const success = computed(() => page.props.flash?.success);

const form = useForm({
    branch_id: props.branches[0]?.id ?? null,
    patient_name: '',
    phone: '',
    email: '',
    appointment_date: '',
    start_time: '10:00',
    end_time: '10:30',
    specialty: props.specialties[0] ?? 'General Dentistry',
    treatment_name: '',
    notes: '',
});

function submit() {
    form.post(route('public.booking.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('patient_name', 'phone', 'email', 'appointment_date', 'treatment_name', 'notes'),
    });
}
</script>

<template>
    <Head title="Book Dental Appointment" />

    <div class="soft-grid min-h-screen px-4 py-6 md:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="glass-panel rounded-[30px] px-6 py-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.34em] text-slate-400">SmileWorks Dental</div>
                        <h1 class="mt-2 text-3xl font-semibold text-slate-900">Book an Appointment</h1>
                        <p class="mt-2 text-slate-600">Choose branch, treatment, and preferred time. We will place your appointment directly into the clinic system.</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('login')">
                            <Button label="Staff Login" severity="secondary" outlined />
                        </Link>
                        <Link href="/">
                            <Button label="Home" />
                        </Link>
                    </div>
                </div>
            </header>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <Card class="glass-panel rounded-[30px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm font-medium text-slate-500">Customer Booking Form</div>
                    </template>
                    <template #content>
                        <div
                            v-if="success"
                            class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                        >
                            {{ success }}
                        </div>

                        <form class="grid gap-4" @submit.prevent="submit">
                            <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" placeholder="Select branch" />
                            <div class="grid gap-4 md:grid-cols-2">
                                <InputText v-model="form.patient_name" placeholder="Full name" />
                                <InputText v-model="form.phone" placeholder="Phone number" />
                            </div>
                            <InputText v-model="form.email" placeholder="Email address" />
                            <div class="grid gap-4 md:grid-cols-2">
                                <Select v-model="form.specialty" :options="specialties" placeholder="Specialty" />
                                <InputText v-model="form.treatment_name" placeholder="Treatment / reason" />
                            </div>
                            <div class="grid gap-4 md:grid-cols-3">
                                <InputText v-model="form.appointment_date" type="date" />
                                <InputText v-model="form.start_time" type="time" />
                                <InputText v-model="form.end_time" type="time" />
                            </div>
                            <Textarea v-model="form.notes" rows="4" placeholder="Symptoms, concerns, or message" />
                            <Button label="Book Appointment" type="submit" :loading="form.processing" />
                        </form>
                    </template>
                </Card>

                <div class="space-y-6">
                    <Card class="glass-panel rounded-[30px] border-none shadow-none">
                        <template #content>
                            <div class="text-sm font-medium text-slate-500">How it works</div>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                                <li class="flex gap-3"><i class="pi pi-check-circle mt-1 text-teal-700" />Your booking goes straight into the clinic appointment system.</li>
                                <li class="flex gap-3"><i class="pi pi-check-circle mt-1 text-teal-700" />The clinic can auto-assign the doctor based on specialty and slot.</li>
                                <li class="flex gap-3"><i class="pi pi-check-circle mt-1 text-teal-700" />A website inquiry record is also created for follow-up tracking.</li>
                            </ul>
                        </template>
                    </Card>

                    <Card class="glass-panel rounded-[30px] border-none shadow-none">
                        <template #content>
                            <div class="text-sm font-medium text-slate-500">Best for patients</div>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="font-medium text-slate-900">New Consultation</div>
                                    <div class="text-sm text-slate-500">General check-up, pain, swelling, or oral hygiene consultation.</div>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="font-medium text-slate-900">Follow-up Visit</div>
                                    <div class="text-sm text-slate-500">Braces, root canal review, implant review, or post-procedure follow-up.</div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
