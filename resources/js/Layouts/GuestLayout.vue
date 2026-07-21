<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps({
    title: {
        type: String,
        default: 'SmileWorks Dental OS',
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="auth-shell">
        <div class="auth-shell__panel">
            <section class="auth-shell__hero">
                <div class="auth-shell__hero-badge">
                    <span class="pi pi-sparkles" />
                    <span>Free admin layout</span>
                </div>
                <h1 class="auth-shell__hero-title">
                    Manage appointments, staff, billing, and branch operations from one workspace.
                </h1>
                <p class="auth-shell__hero-copy">
                    Built on Laravel, Inertia, Vue, and PrimeVue with a layout structure you can keep extending without relying on a paid template.
                </p>

                <div class="grid gap-4 md:grid-cols-3">
                    <Card class="auth-shell__stat-card border-none shadow-none">
                        <template #content>
                            <div class="text-sm text-slate-500">Appointment desk</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">Live</div>
                        </template>
                    </Card>
                    <Card class="auth-shell__stat-card border-none shadow-none">
                        <template #content>
                            <div class="text-sm text-slate-500">PrimeVue components</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">Kept</div>
                        </template>
                    </Card>
                    <Card class="auth-shell__stat-card border-none shadow-none">
                        <template #content>
                            <div class="text-sm text-slate-500">Inertia navigation</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">Enabled</div>
                        </template>
                    </Card>
                </div>

                <div class="mt-8 flex flex-wrap gap-2">
                    <Tag value="Multi-branch" severity="info" rounded />
                    <Tag value="Scheduling" severity="success" rounded />
                    <Tag value="Finance" severity="warn" rounded />
                </div>
            </section>

            <Card class="auth-shell__card border-none shadow-none">
                <template #content>
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <div class="auth-shell__card-eyebrow">SmileWorks</div>
                            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ title }}</div>
                        </div>
                        <Link href="/" class="text-sm font-medium text-teal-700">Home</Link>
                    </div>

                    <slot />
                </template>
            </Card>
        </div>
    </div>
</template>
