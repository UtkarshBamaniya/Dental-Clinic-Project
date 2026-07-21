<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Register">
        <Head title="Register" />

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Name</label>
                <InputText v-model="form.name" class="w-full" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Email</label>
                <InputText v-model="form.email" class="w-full" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Password</label>
                <Password v-model="form.password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Confirm Password</label>
                <Password v-model="form.password_confirmation" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            </div>
            <Button label="Create account" type="submit" class="w-full" :loading="form.processing" />
            <div class="text-center text-sm text-slate-500">
                Already registered?
                <Link :href="route('login')" class="font-medium text-teal-700">Login</Link>
            </div>
        </form>
    </GuestLayout>
</template>
