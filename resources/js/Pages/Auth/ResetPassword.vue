<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <GuestLayout title="Reset Password">
        <Head title="Reset Password" />

        <form class="space-y-5" @submit.prevent="form.post(route('password.store'))">
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Email</label>
                <InputText v-model="form.email" class="w-full" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">New password</label>
                <Password v-model="form.password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Confirm password</label>
                <Password v-model="form.password_confirmation" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            </div>
            <Button label="Reset password" type="submit" class="w-full" :loading="form.processing" />
        </form>
    </GuestLayout>
</template>
