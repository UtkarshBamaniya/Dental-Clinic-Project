<template>
    <div class="patient-selector relative">
        <label v-if="label" class="block font-medium text-sm text-gray-700 mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <!-- Selected Patient View -->
        <div v-if="selectedPatient" class="border rounded-md p-4 bg-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <div class="font-bold text-gray-900">{{ selectedPatient.patient_code }}</div>
                <div class="text-lg font-semibold text-teal-800">{{ selectedPatient.full_name }}</div>
                <div class="text-sm text-gray-600 mt-1 flex items-center gap-3">
                    <span><i class="pi pi-mobile text-xs mr-1"></i> {{ selectedPatient.mobile }}</span>
                    <span v-if="selectedPatient.gender" class="capitalize">| {{ selectedPatient.gender }}</span>
                    <span v-if="selectedPatient.date_of_birth">| DOB: {{ selectedPatient.date_of_birth }}</span>
                </div>
            </div>
            <Button label="Change Patient" severity="secondary" size="small" icon="pi pi-sync" @click="clearSelection" :disabled="disabled" />
        </div>

        <!-- Search View -->
        <div v-else class="relative">
            <div class="relative">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <InputText 
                    ref="searchInput"
                    v-model="searchQuery" 
                    placeholder="Search patient by name, mobile or code..." 
                    class="w-full pl-10 pr-10"
                    :disabled="disabled"
                    @input="onSearchInput"
                    @focus="onFocus"
                />
                <i v-if="loading" class="pi pi-spin pi-spinner absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <i v-else-if="searchQuery.length > 0" class="pi pi-times absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-gray-700" @click="clearSearch"></i>
            </div>
            
            <!-- Search Results Dropdown -->
            <div v-if="showResults" class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-72 overflow-y-auto">
                <ul v-if="searchResults.length > 0" class="m-0 p-0 list-none">
                    <li v-for="patient in searchResults" :key="patient.id" 
                        class="p-3 hover:bg-teal-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                        @click="selectPatient(patient)"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-gray-900">{{ patient.patient_code }}</div>
                                <div class="text-teal-800">{{ patient.full_name }}</div>
                            </div>
                            <div class="text-right text-sm text-gray-600">
                                <div><i class="pi pi-mobile text-xs mr-1"></i>{{ patient.mobile }}</div>
                                <div v-if="patient.gender" class="text-xs mt-1 capitalize">{{ patient.gender }}</div>
                            </div>
                        </div>
                    </li>
                </ul>
                <div v-else class="p-5 text-center text-gray-500">
                    <p class="mb-3">No patient found for "<span class="font-semibold">{{ searchQuery }}</span>"</p>
                    <Button label="Create New Patient" icon="pi pi-user-plus" size="small" @click="openCreateDialog" />
                </div>
            </div>
        </div>

        <CreatePatientDialog 
            v-model:visible="isCreateDialogOpen"
            :initial-mobile="isMobileSearch ? searchQuery : ''"
            :initial-first-name="!isMobileSearch ? searchQuery : ''"
            @created="onPatientCreated"
            @selected-existing="selectPatient"
        />
        
        <!-- Invisible overlay to close dropdown on click outside -->
        <div v-if="showResults" class="fixed inset-0 z-[90]" @click="showResults = false"></div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import CreatePatientDialog from './CreatePatientDialog.vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: null
    },
    label: {
        type: String,
        default: 'Patient'
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const searchInput = ref(null);
const selectedPatient = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const loading = ref(false);
const showResults = ref(false);
const isCreateDialogOpen = ref(false);
let searchTimeout = null;

// Determine if the search query looks like a phone number (just digits and spaces/plus)
const isMobileSearch = computed(() => {
    return /^[\d\s\+\-]{6,}$/.test(searchQuery.value.trim());
});

// Watch modelValue to load patient detail if initialized with an ID but no object
watch(() => props.modelValue, async (newVal) => {
    if (!newVal) {
        selectedPatient.value = null;
        return;
    }
    
    // If we have an ID but no patient object, fetch it (only if it's different)
    if (newVal && (!selectedPatient.value || selectedPatient.value.id !== newVal)) {
        await loadPatient(newVal);
    }
}, { immediate: true });

async function loadPatient(id) {
    try {
        const response = await axios.get(route('patients.show', id));
        selectedPatient.value = response.data.data;
    } catch (e) {
        console.error("Failed to load patient", e);
        selectedPatient.value = null;
        emit('update:modelValue', null);
    }
}

function onSearchInput() {
    if (searchQuery.value.trim().length < 2) {
        showResults.value = false;
        searchResults.value = [];
        return;
    }

    clearTimeout(searchTimeout);
    loading.value = true;
    showResults.value = true;
    
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('patients.search', { q: searchQuery.value }));
            // Only update if we are still searching for the same query
            // (prevents race conditions)
            searchResults.value = response.data.data;
        } catch (e) {
            console.error("Search failed", e);
            searchResults.value = [];
        } finally {
            loading.value = false;
        }
    }, 400); // 400ms debounce
}

function onFocus() {
    if (searchQuery.value.trim().length >= 2) {
        showResults.value = true;
    }
}

function clearSearch() {
    searchQuery.value = '';
    showResults.value = false;
    searchResults.value = [];
    nextTick(() => {
        if (searchInput.value) {
            searchInput.value.$el.focus();
        }
    });
}

function selectPatient(patient) {
    selectedPatient.value = patient;
    searchQuery.value = '';
    showResults.value = false;
    emit('update:modelValue', patient.id);
    emit('change', patient);
}

function clearSelection() {
    selectedPatient.value = null;
    emit('update:modelValue', null);
    emit('change', null);
    
    nextTick(() => {
        if (searchInput.value) {
            searchInput.value.$el.focus();
        }
    });
}

function openCreateDialog() {
    showResults.value = false;
    isCreateDialogOpen.value = true;
}

function onPatientCreated(patient) {
    selectPatient(patient);
}
</script>

<style scoped>
/* Optional scoped styling if needed */
</style>
