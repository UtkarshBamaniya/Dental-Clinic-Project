<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const page = usePage();
const user = page.props.auth.user;

const profileForm = useForm({
    name: user.name,
    email: user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="glass-panel rounded-[32px] border-none shadow-none">
                <template #title>
                    <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Profile</div>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Update account details</h2>
                </template>
                <template #content>
                    <form class="space-y-4" @submit.prevent="profileForm.patch(route('profile.update'))">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-600">Name</label>
                            <InputText v-model="profileForm.name" class="w-full" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-600">Email</label>
                            <InputText v-model="profileForm.email" class="w-full" />
                        </div>
                        <Button label="Save profile" type="submit" :loading="profileForm.processing" />
                    </form>
                </template>
            </Card>

            <Card class="glass-panel rounded-[32px] border-none shadow-none">
                <template #title>
                    <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Security</div>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Change password</h2>
                </template>
                <template #content>
                    <form class="space-y-4" @submit.prevent="passwordForm.put(route('password.update'))">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-600">Current password</label>
                            <Password v-model="passwordForm.current_password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-600">New password</label>
                            <Password v-model="passwordForm.password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-600">Confirm password</label>
                            <Password
                                v-model="passwordForm.password_confirmation"
                                class="w-full"
                                input-class="w-full"
                                :feedback="false"
                                toggle-mask
                            />
                        </div>
                        <Button label="Update password" type="submit" :loading="passwordForm.processing" />
                    </form>
                </template>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
