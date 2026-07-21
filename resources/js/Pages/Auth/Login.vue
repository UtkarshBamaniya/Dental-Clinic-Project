<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Message from 'primevue/message';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
    status: String,
    canResetPassword: Boolean,
});

const form = useForm({
    email: 'admin@smileworks.test',
    password: 'password',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout title="Login">
        <Head title="Login" />

        <Message v-if="status" severity="success" class="mb-4">{{ status }}</Message>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Email</label>
                <InputText v-model="form.email" class="w-full" />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-600">Password</label>
                <Password v-model="form.password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.remember" binary input-id="remember" />
                    <label for="remember" class="text-sm text-slate-600">Remember me</label>
                </div>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-teal-700">Forgot password?</Link>
            </div>

            <Button label="Sign in" type="submit" class="w-full" :loading="form.processing" />
        </form>
    </GuestLayout>
</template>
