<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    metrics: Object,
    branches: Array,
});

const appointmentChartData = computed(() => ({
    labels: props.metrics.charts.labels,
    datasets: [
        {
            label: 'Appointments',
            data: props.metrics.charts.appointments,
            borderColor: '#0f766e',
            backgroundColor: 'rgba(15, 118, 110, 0.12)',
            fill: true,
            tension: 0.4,
        },
    ],
}));

const revenueChartData = computed(() => ({
    labels: props.metrics.charts.labels,
    datasets: [
        {
            label: 'Revenue',
            data: props.metrics.charts.revenue,
            backgroundColor: ['#0f766e', '#1d4ed8', '#7c3aed', '#ea580c', '#14b8a6', '#6366f1', '#f97316'],
            borderRadius: 12,
        },
    ],
}));

const statusChartData = computed(() => ({
    labels: ['Booked', 'Confirmed', 'Completed', 'Cancelled', 'No Show'],
    datasets: [
        {
            data: [
                props.metrics.statusBreakdown.booked,
                props.metrics.statusBreakdown.confirmed,
                props.metrics.statusBreakdown.completed,
                props.metrics.statusBreakdown.cancelled,
                props.metrics.statusBreakdown.no_show,
            ],
            backgroundColor: ['#38bdf8', '#6366f1', '#0f766e', '#ea580c', '#ef4444'],
        },
    ],
}));
</script>

<template>
    <AuthenticatedLayout title="Executive Dashboard">
        <div class="space-y-6">
            <section class="grid gap-4 xl:grid-cols-4">
                <Card
                    v-for="item in metrics.overview"
                    :key="item.label"
                    class="metric-gradient rounded-[28px] border-none shadow-none"
                >
                    <template #content>
                        <div class="text-sm text-slate-500">{{ item.label }}</div>
                        <div class="mt-4 text-3xl font-semibold text-slate-900">{{ item.value }}</div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm text-emerald-600">{{ item.trend }}</span>
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.accent }" />
                        </div>
                    </template>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Demand Trend</div>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Appointments in the last 7 days</h2>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <Chart type="line" :data="appointmentChartData" class="h-[320px]" />
                    </template>
                </Card>

                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Status Mix</div>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Chairside flow</h2>
                    </template>
                    <template #content>
                        <Chart type="doughnut" :data="statusChartData" class="mx-auto max-w-[360px]" />
                    </template>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Finance Snapshot</div>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Collections vs cost outflow</h2>
                    </template>
                    <template #content>
                        <div class="grid gap-4">
                            <div class="rounded-[24px] bg-white p-4">
                                <div class="text-sm text-slate-500">Collections</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">Rs. {{ metrics.finance.collections.toLocaleString() }}</div>
                            </div>
                            <div class="rounded-[24px] bg-white p-4">
                                <div class="text-sm text-slate-500">Expenses</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">Rs. {{ metrics.finance.expenses.toLocaleString() }}</div>
                            </div>
                            <div class="rounded-[24px] bg-white p-4">
                                <div class="text-sm text-slate-500">Payroll</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">Rs. {{ metrics.finance.payroll.toLocaleString() }}</div>
                            </div>
                            <div class="rounded-[24px] bg-slate-900 p-4 text-white">
                                <div class="text-sm text-slate-300">Estimated operating surplus</div>
                                <div class="mt-2 text-2xl font-semibold">
                                    Rs. {{ metrics.finance.profit.toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Revenue Curve</div>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Daily collection graph</h2>
                    </template>
                    <template #content>
                        <Chart type="bar" :data="revenueChartData" class="h-[320px]" />
                    </template>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Recent Appointments</div>
                    </template>
                    <template #content>
                        <DataTable :value="metrics.recentAppointments" stripedRows responsiveLayout="scroll">
                            <Column field="patient.name" header="Patient" />
                            <Column field="branch.name" header="Branch" />
                            <Column field="treatment_name" header="Treatment" />
                            <Column header="Doctor">
                                <template #body="{ data }">
                                    {{ data.doctor_profile?.user?.name || 'Auto-pending' }}
                                </template>
                            </Column>
                            <Column header="Status">
                                <template #body="{ data }">
                                    <Tag :value="data.status" severity="info" rounded />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <Card class="glass-panel rounded-[32px] border-none shadow-none">
                    <template #title>
                        <div class="text-sm uppercase tracking-[0.32em] text-slate-400">Recent Payments</div>
                    </template>
                    <template #content>
                        <DataTable :value="metrics.recentPayments" stripedRows responsiveLayout="scroll">
                            <Column field="invoice_number" header="Invoice" />
                            <Column field="patient.name" header="Patient" />
                            <Column field="method" header="Mode" />
                            <Column header="Amount">
                                <template #body="{ data }">Rs. {{ Number(data.amount).toLocaleString() }}</template>
                            </Column>
                            <Column header="Status">
                                <template #body="{ data }">
                                    <Tag :value="data.status" severity="success" rounded />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
