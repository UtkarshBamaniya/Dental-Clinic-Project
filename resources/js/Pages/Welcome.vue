<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
</script>

<template>
    <Head title="SmileWorks Dental OS" />

    <div class="soft-grid min-h-screen px-4 py-6 md:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="glass-panel rounded-[32px] px-6 py-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.36em] text-slate-400">Dental Clinic Platform</div>
                        <h1 class="mt-2 text-3xl font-semibold text-slate-900">SmileWorks Dental OS</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('public.booking')">
                            <Button label="Book Online" icon="pi pi-calendar-plus" />
                        </Link>
                        <Link :href="route('login')">
                            <Button label="Staff Login" severity="secondary" outlined />
                        </Link>
                    </div>
                </div>
            </header>

            <section class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="glass-panel rounded-[36px] p-8 md:p-12">
                    <div class="max-w-3xl">
                        <div class="text-sm uppercase tracking-[0.35em] text-teal-700">PrimeVue-powered clinic operations</div>
                        <h2 class="mt-4 text-5xl font-semibold leading-tight text-slate-900 md:text-6xl">
                            Book, assign, bill,
                            <span class="text-teal-700">collect, and analyze</span>
                            your dental clinics in one flow.
                        </h2>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                            Built for multi-branch dental practices with patient intake, inquiry conversion,
                            automatic doctor assignment, treatment billing, payroll, accounting, and Razorpay-ready payments.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-3">
                            <Link :href="route('public.booking')">
                                <Button label="Customer Booking Page" icon="pi pi-globe" />
                            </Link>
                            <Link :href="route('login')">
                                <Button label="Open Staff Dashboard" severity="contrast" outlined icon="pi pi-arrow-right" icon-pos="right" />
                            </Link>
                        </div>

                        <div class="mt-10 grid gap-4 md:grid-cols-3">
                            <Card class="rounded-[28px] border-none bg-slate-900 text-white shadow-none">
                                <template #content>
                                    <div class="text-sm text-slate-300">Inquiries tracked</div>
                                    <div class="mt-3 text-3xl font-semibold">24/7</div>
                                    <div class="mt-2 text-sm text-slate-300">Lead capture across web, walk-in, WhatsApp</div>
                                </template>
                            </Card>
                            <Card class="rounded-[28px] border-none bg-white shadow-none">
                                <template #content>
                                    <div class="text-sm text-slate-500">Doctor assignment</div>
                                    <div class="mt-3 text-3xl font-semibold text-slate-900">Auto</div>
                                    <div class="mt-2 text-sm text-slate-500">Specialty + availability + least-load</div>
                                </template>
                            </Card>
                            <Card class="rounded-[28px] border-none bg-white shadow-none">
                                <template #content>
                                    <div class="text-sm text-slate-500">Collections</div>
                                    <div class="mt-3 text-3xl font-semibold text-slate-900">Razorpay</div>
                                    <div class="mt-2 text-sm text-slate-500">Online, cash, partial, and due recovery</div>
                                </template>
                            </Card>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <Card class="glass-panel rounded-[32px] border-none shadow-none">
                        <template #content>
                            <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Inside the system</div>
                            <ul class="mt-5 space-y-4 text-sm text-slate-600">
                                <li class="flex items-start gap-3">
                                    <i class="pi pi-check-circle mt-1 text-teal-700" />
                                    <span>PrimeVue dashboard with graphs for appointments, revenue, dues, and branch performance.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="pi pi-check-circle mt-1 text-orange-600" />
                                    <span>Direct inquiry to appointment conversion and separate public website booking.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="pi pi-check-circle mt-1 text-blue-700" />
                                    <span>All ledger entries plus profit and loss view for clinic accounting.</span>
                                </li>
                            </ul>
                        </template>
                    </Card>

                    <Card class="glass-panel rounded-[32px] border-none shadow-none">
                        <template #content>
                            <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Demo access</div>
                            <div class="mt-5 rounded-[24px] bg-slate-900 p-5 text-white">
                                <div class="text-sm text-slate-300">Super admin</div>
                                <div class="mt-2 font-semibold">admin@smileworks.test</div>
                                <div class="text-sm text-slate-300">password</div>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>
        </div>
    </div>
</template>
