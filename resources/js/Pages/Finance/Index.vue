<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';

const props = defineProps({
    payments: Array,
    expenses: Array,
    payrolls: Array,
    journals: Array,
    branches: Array,
    patients: Array,
    appointments: Array,
    staff: Array,
    ledgerSummary: Array,
    profitLoss: Object,
});

const activeSection = ref('ledger');
const showPaymentModal = ref(false);
const showExpenseModal = ref(false);
const showPayrollModal = ref(false);
const filters = ref({
    search: '',
    branchId: null,
});

const paymentForm = useForm({
    branch_id: props.branches[0]?.id ?? null,
    patient_id: props.patients[0]?.id ?? null,
    appointment_id: props.appointments[0]?.id ?? null,
    payment_date: '',
    amount: 0,
    method: 'razorpay',
    status: 'captured',
    razorpay_order_id: '',
    razorpay_payment_id: '',
    razorpay_reference: '',
    notes: '',
});

const expenseForm = useForm({
    branch_id: props.branches[0]?.id ?? null,
    category: 'Clinic Supplies',
    title: '',
    vendor_name: '',
    expense_date: '',
    amount: 0,
    paid_via: 'bank',
    notes: '',
});

const payrollForm = useForm({
    branch_id: props.branches[0]?.id ?? null,
    user_id: props.staff[0]?.id ?? null,
    salary_month: '',
    gross_salary: Number(props.staff[0]?.monthly_salary ?? 0),
    bonus: 0,
    deductions: 0,
    payment_status: 'processed',
    paid_on: '',
});

const summary = computed(() => ({
    collection: props.profitLoss.income,
    expense: props.profitLoss.operating_expenses,
    payroll: props.profitLoss.payroll_expenses,
    profit: props.profitLoss.net_profit,
}));

const filteredJournals = computed(() =>
    props.journals.filter((entry) => {
        const search = filters.value.search.trim().toLowerCase();
        const matchesSearch =
            !search ||
            entry.account_head?.toLowerCase().includes(search) ||
            entry.reference_type?.toLowerCase().includes(search) ||
            entry.narration?.toLowerCase().includes(search);
        const matchesBranch = !filters.value.branchId || entry.branch_id === filters.value.branchId;

        return matchesSearch && matchesBranch;
    }),
);

const filteredLedgerSummary = computed(() =>
    props.ledgerSummary.filter((entry) => {
        const search = filters.value.search.trim().toLowerCase();
        return !search || entry.account_head?.toLowerCase().includes(search);
    }),
);

function resetPaymentForm() {
    paymentForm.reset('payment_date', 'amount', 'razorpay_order_id', 'razorpay_payment_id', 'razorpay_reference', 'notes');
    paymentForm.clearErrors();
    paymentForm.branch_id = props.branches[0]?.id ?? null;
    paymentForm.patient_id = props.patients[0]?.id ?? null;
    paymentForm.appointment_id = props.appointments[0]?.id ?? null;
    paymentForm.method = 'razorpay';
    paymentForm.status = 'captured';
    paymentForm.amount = 0;
}

function resetExpenseForm() {
    expenseForm.reset('title', 'vendor_name', 'expense_date', 'amount', 'notes');
    expenseForm.clearErrors();
    expenseForm.branch_id = props.branches[0]?.id ?? null;
    expenseForm.category = 'Clinic Supplies';
    expenseForm.paid_via = 'bank';
    expenseForm.amount = 0;
}

function resetPayrollForm() {
    payrollForm.reset('salary_month', 'gross_salary', 'bonus', 'deductions', 'paid_on');
    payrollForm.clearErrors();
    payrollForm.branch_id = props.branches[0]?.id ?? null;
    payrollForm.user_id = props.staff[0]?.id ?? null;
    payrollForm.gross_salary = Number(props.staff[0]?.monthly_salary ?? 0);
    payrollForm.bonus = 0;
    payrollForm.deductions = 0;
    payrollForm.payment_status = 'processed';
}

function openPaymentModal() {
    resetPaymentForm();
    showPaymentModal.value = true;
}

function openExpenseModal() {
    resetExpenseForm();
    showExpenseModal.value = true;
}

function openPayrollModal() {
    resetPayrollForm();
    showPayrollModal.value = true;
}

function savePayment() {
    paymentForm.post(route('finance.payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetPaymentForm();
            showPaymentModal.value = false;
        },
    });
}

function saveExpense() {
    expenseForm.post(route('finance.expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetExpenseForm();
            showExpenseModal.value = false;
        },
    });
}

function savePayroll() {
    payrollForm.post(route('finance.payroll.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetPayrollForm();
            showPayrollModal.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout title="Finance, Ledger & Profit Loss">
        <div class="space-y-6">
            <section class="grid gap-4 xl:grid-cols-4">
                <Card class="metric-gradient rounded-[28px] border-none shadow-none">
                    <template #content>
                        <div class="text-sm text-slate-500">Income</div>
                        <div class="mt-3 text-3xl font-semibold text-slate-900">Rs. {{ summary.collection.toLocaleString() }}</div>
                    </template>
                </Card>
                <Card class="metric-gradient rounded-[28px] border-none shadow-none">
                    <template #content>
                        <div class="text-sm text-slate-500">Operating Expense</div>
                        <div class="mt-3 text-3xl font-semibold text-slate-900">Rs. {{ summary.expense.toLocaleString() }}</div>
                    </template>
                </Card>
                <Card class="metric-gradient rounded-[28px] border-none shadow-none">
                    <template #content>
                        <div class="text-sm text-slate-500">Payroll Expense</div>
                        <div class="mt-3 text-3xl font-semibold text-slate-900">Rs. {{ summary.payroll.toLocaleString() }}</div>
                    </template>
                </Card>
                <Card class="rounded-[28px] border-none bg-slate-900 text-white shadow-none">
                    <template #content>
                        <div class="text-sm text-slate-300">Net Profit / Loss</div>
                        <div class="mt-3 text-3xl font-semibold">Rs. {{ summary.profit.toLocaleString() }}</div>
                    </template>
                </Card>
            </section>

            <div class="flex flex-wrap gap-2">
                <Button label="Ledger Entries" :severity="activeSection === 'ledger' ? 'contrast' : 'secondary'" :outlined="activeSection !== 'ledger'" @click="activeSection = 'ledger'" />
                <Button label="Ledger Summary" :severity="activeSection === 'summary' ? 'contrast' : 'secondary'" :outlined="activeSection !== 'summary'" @click="activeSection = 'summary'" />
                <Button label="Profit & Loss" :severity="activeSection === 'pl' ? 'contrast' : 'secondary'" :outlined="activeSection !== 'pl'" @click="activeSection = 'pl'" />
            </div>

            <div class="page-toolbar">
                <div class="page-toolbar__filters">
                    <InputText v-model="filters.search" placeholder="Search finance records" />
                    <Select v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="Filter by branch" showClear />
                </div>
                <div class="page-toolbar__actions">
                    <Button label="Add Payment" icon="pi pi-plus" @click="openPaymentModal" />
                    <Button label="Add Expense" icon="pi pi-plus" severity="secondary" @click="openExpenseModal" />
                    <Button label="Add Payroll" icon="pi pi-plus" severity="contrast" @click="openPayrollModal" />
                </div>
            </div>

            <section v-if="activeSection === 'ledger'">
                <Card class="glass-panel rounded-[28px] border-none shadow-none">
                    <template #content>
                        <DataTable :value="filteredJournals" stripedRows responsiveLayout="scroll">
                            <Column field="entry_date" header="Date" />
                            <Column field="branch.name" header="Branch" />
                            <Column field="account_head" header="Account Head" />
                            <Column field="reference_type" header="Ref Type" />
                            <Column field="narration" header="Narration" />
                            <Column header="Debit">
                                <template #body="{ data }">Rs. {{ Number(data.debit).toLocaleString() }}</template>
                            </Column>
                            <Column header="Credit">
                                <template #body="{ data }">Rs. {{ Number(data.credit).toLocaleString() }}</template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeSection === 'summary'">
                <Card class="glass-panel rounded-[28px] border-none shadow-none">
                    <template #content>
                        <DataTable :value="filteredLedgerSummary" stripedRows responsiveLayout="scroll">
                            <Column field="account_head" header="Account Head" />
                            <Column header="Total Debit">
                                <template #body="{ data }">Rs. {{ Number(data.debit).toLocaleString() }}</template>
                            </Column>
                            <Column header="Total Credit">
                                <template #body="{ data }">Rs. {{ Number(data.credit).toLocaleString() }}</template>
                            </Column>
                            <Column header="Balance">
                                <template #body="{ data }">
                                    <Tag :value="`Rs. ${Number(data.balance).toLocaleString()}`" severity="contrast" rounded />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeSection === 'pl'">
                <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                    <Card class="glass-panel rounded-[28px] border-none shadow-none">
                        <template #title><div class="text-sm font-medium text-slate-500">Profit & Loss Summary</div></template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-4">
                                    <span>Income</span>
                                    <strong>Rs. {{ profitLoss.income.toLocaleString() }}</strong>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-4">
                                    <span>Operating Expenses</span>
                                    <strong>Rs. {{ profitLoss.operating_expenses.toLocaleString() }}</strong>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-4">
                                    <span>Payroll Expenses</span>
                                    <strong>Rs. {{ profitLoss.payroll_expenses.toLocaleString() }}</strong>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-slate-900 px-4 py-4 text-white">
                                    <span>Net Profit / Loss</span>
                                    <strong>Rs. {{ profitLoss.net_profit.toLocaleString() }}</strong>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="glass-panel rounded-[28px] border-none shadow-none">
                        <template #title><div class="text-sm font-medium text-slate-500">Source Entries</div></template>
                        <template #content>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="text-sm text-slate-500">Receipts Count</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ payments.length }}</div>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="text-sm text-slate-500">Expense Count</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ expenses.length }}</div>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="text-sm text-slate-500">Payroll Count</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ payrolls.length }}</div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>
        </div>

        <Dialog v-model:visible="showPaymentModal" modal header="Add Payment" :style="{ width: '48rem' }">
            <form class="form-grid" @submit.prevent="savePayment">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="paymentForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="paymentForm.errors.branch_id" class="field-error">{{ paymentForm.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Patient<span class="field-label__required">*</span></label>
                        <Select v-model="paymentForm.patient_id" :options="patients" optionLabel="name" optionValue="id" required />
                        <small v-if="paymentForm.errors.patient_id" class="field-error">{{ paymentForm.errors.patient_id }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Appointment</label>
                        <Select v-model="paymentForm.appointment_id" :options="appointments" optionLabel="treatment_name" optionValue="id" showClear />
                        <small v-if="paymentForm.errors.appointment_id" class="field-error">{{ paymentForm.errors.appointment_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Payment Date<span class="field-label__required">*</span></label>
                        <InputText v-model="paymentForm.payment_date" type="date" required />
                        <small v-if="paymentForm.errors.payment_date" class="field-error">{{ paymentForm.errors.payment_date }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Amount<span class="field-label__required">*</span></label>
                        <InputNumber v-model="paymentForm.amount" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="paymentForm.errors.amount" class="field-error">{{ paymentForm.errors.amount }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Method<span class="field-label__required">*</span></label>
                        <Select v-model="paymentForm.method" :options="['razorpay', 'cash', 'bank_transfer', 'upi']" required />
                        <small v-if="paymentForm.errors.method" class="field-error">{{ paymentForm.errors.method }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Status<span class="field-label__required">*</span></label>
                        <Select v-model="paymentForm.status" :options="['captured', 'pending', 'failed']" required />
                        <small v-if="paymentForm.errors.status" class="field-error">{{ paymentForm.errors.status }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Razorpay Order ID</label>
                        <InputText v-model="paymentForm.razorpay_order_id" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Razorpay Payment ID</label>
                        <InputText v-model="paymentForm.razorpay_payment_id" />
                    </div>
                    <div class="form-field">
                        <label class="field-label">Reference</label>
                        <InputText v-model="paymentForm.razorpay_reference" />
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Notes</label>
                    <Textarea v-model="paymentForm.notes" rows="3" />
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showPaymentModal = false" />
                    <Button type="submit" label="Save Payment" :loading="paymentForm.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="showExpenseModal" modal header="Add Expense" :style="{ width: '44rem' }">
            <form class="form-grid" @submit.prevent="saveExpense">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="expenseForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="expenseForm.errors.branch_id" class="field-error">{{ expenseForm.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Category<span class="field-label__required">*</span></label>
                        <InputText v-model="expenseForm.category" required />
                        <small v-if="expenseForm.errors.category" class="field-error">{{ expenseForm.errors.category }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Expense Title<span class="field-label__required">*</span></label>
                        <InputText v-model="expenseForm.title" required />
                        <small v-if="expenseForm.errors.title" class="field-error">{{ expenseForm.errors.title }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Vendor</label>
                        <InputText v-model="expenseForm.vendor_name" />
                        <small v-if="expenseForm.errors.vendor_name" class="field-error">{{ expenseForm.errors.vendor_name }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="form-field">
                        <label class="field-label">Expense Date<span class="field-label__required">*</span></label>
                        <InputText v-model="expenseForm.expense_date" type="date" required />
                        <small v-if="expenseForm.errors.expense_date" class="field-error">{{ expenseForm.errors.expense_date }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Amount<span class="field-label__required">*</span></label>
                        <InputNumber v-model="expenseForm.amount" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="expenseForm.errors.amount" class="field-error">{{ expenseForm.errors.amount }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Paid Via<span class="field-label__required">*</span></label>
                        <Select v-model="expenseForm.paid_via" :options="['bank', 'cash', 'card']" required />
                        <small v-if="expenseForm.errors.paid_via" class="field-error">{{ expenseForm.errors.paid_via }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label class="field-label">Notes</label>
                    <Textarea v-model="expenseForm.notes" rows="3" />
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showExpenseModal = false" />
                    <Button type="submit" label="Save Expense" :loading="expenseForm.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="showPayrollModal" modal header="Add Payroll" :style="{ width: '44rem' }">
            <form class="form-grid" @submit.prevent="savePayroll">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Branch<span class="field-label__required">*</span></label>
                        <Select v-model="payrollForm.branch_id" :options="branches" optionLabel="name" optionValue="id" required />
                        <small v-if="payrollForm.errors.branch_id" class="field-error">{{ payrollForm.errors.branch_id }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Staff Member<span class="field-label__required">*</span></label>
                        <Select v-model="payrollForm.user_id" :options="staff" optionLabel="name" optionValue="id" required />
                        <small v-if="payrollForm.errors.user_id" class="field-error">{{ payrollForm.errors.user_id }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Salary Month<span class="field-label__required">*</span></label>
                        <InputText v-model="payrollForm.salary_month" type="date" required />
                        <small v-if="payrollForm.errors.salary_month" class="field-error">{{ payrollForm.errors.salary_month }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Gross Salary<span class="field-label__required">*</span></label>
                        <InputNumber v-model="payrollForm.gross_salary" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="payrollForm.errors.gross_salary" class="field-error">{{ payrollForm.errors.gross_salary }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Bonus</label>
                        <InputNumber v-model="payrollForm.bonus" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="payrollForm.errors.bonus" class="field-error">{{ payrollForm.errors.bonus }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Deductions</label>
                        <InputNumber v-model="payrollForm.deductions" mode="currency" currency="INR" locale="en-IN" />
                        <small v-if="payrollForm.errors.deductions" class="field-error">{{ payrollForm.errors.deductions }}</small>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="form-field">
                        <label class="field-label">Payment Status<span class="field-label__required">*</span></label>
                        <Select v-model="payrollForm.payment_status" :options="['processed', 'paid']" required />
                        <small v-if="payrollForm.errors.payment_status" class="field-error">{{ payrollForm.errors.payment_status }}</small>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Paid On</label>
                        <InputText v-model="payrollForm.paid_on" type="date" />
                        <small v-if="payrollForm.errors.paid_on" class="field-error">{{ payrollForm.errors.paid_on }}</small>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Cancel" severity="secondary" outlined @click="showPayrollModal = false" />
                    <Button type="submit" label="Process Payroll" :loading="payrollForm.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
