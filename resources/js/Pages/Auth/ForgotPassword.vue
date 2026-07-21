<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});
</script>

<template>
    <GuestLayout title="Forgot Password">
        <Head title="Forgot Password" />

        <Message severity="info" class="mb-4">
            Enter your email and the password reset link will be sent if mail is configured.
        </Message>
        <Message v-if="status" severity="success" class="mb-4">{{ status }}</Message>

        <form class="space-y-5" @submit.prevent="form.post(route('password.email'))">
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Email</label>
                <InputText v-model="form.email" class="w-full" />
            </div>
            <Button label="Send reset link" type="submit" class="w-full" :loading="form.processing" />
        </form>
    </GuestLayout>
</template>
