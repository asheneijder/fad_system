<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const toast = useToast();

const props = defineProps({
    dailyAllowance: {
        type: Object,
        default: () => ({})
    },
    allowanceTypes: {
        type: Object,
        default: () => ({})
    },
    currencies: {
        type: Object,
        default: () => ({})
    },
    defaultRates: {
        type: Object,
        default: () => ({})
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Daily Allowance Details', url: route('user.daily-allowances.show', { dailyAllowance: props.dailyAllowance.id }) },
    { label: 'Edit Daily Allowance' }
];

const loading = ref(false);
const form = ref({
    claim_date: props.dailyAllowance.claim_date ? new Date(props.dailyAllowance.claim_date).toISOString().split('T')[0] : '',
    allowance_type: props.dailyAllowance.allowance_type || 'full_day',
    currency: props.dailyAllowance.currency || 'MYR',
    daily_rate: Number(props.dailyAllowance.daily_rate) || 0,
    purpose: props.dailyAllowance.purpose || '',
    destination: props.dailyAllowance.destination || '',
    attachments: [],
    existing_attachments: props.dailyAllowance.documents || [],
    save_as_draft: true
});

const fileInput = ref(null);

const formatCurrency = (amount, currency = 'MYR') => {
    const currencySymbols = {
        MYR: 'RM',
        USD: '$'
    };
    
    const numericAmount = Number(amount) || 0;
    return `${currencySymbols[currency] || ''}${numericAmount.toFixed(2)}`;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const goBack = () => {
    router.get(route('user.daily-allowances.show', { dailyAllowance: props.dailyAllowance.id }));
};

const removeExistingAttachment = (index) => {
    form.value.existing_attachments.splice(index, 1);
};

const removeNewAttachment = (index) => {
    form.value.attachments.splice(index, 1);
};

const onFileSelect = (event) => {
    const files = Array.from(event.target.files);
    form.value.attachments = [...form.value.attachments, ...files];
    event.target.value = '';
};

const openFileDialog = () => {
    fileInput.value?.click();
};

const calculateClaimAmount = computed(() => {
    const percentage = getPercentageByType(form.value.allowance_type);
    const dailyRate = Number(form.value.daily_rate) || 0;
    return (dailyRate * percentage) / 100;
});

const getPercentageByType = (type) => {
    const percentages = {
        full_day: 100,
        breakfast: 20,
        lunch: 40,
        dinner: 40
    };
    return percentages[type] || 100;
};

const updateDailyRate = () => {
    const defaultRate = props.defaultRates[form.value.currency]?.[form.value.allowance_type];
    if (defaultRate && (!form.value.daily_rate || form.value.daily_rate === 0)) {
        form.value.daily_rate = defaultRate;
    }
};

// Watch for currency and allowance type changes to update default rates
watch([() => form.value.currency, () => form.value.allowance_type], () => {
    updateDailyRate();
});

const submitForm = (saveAsDraft = true) => {
    form.value.save_as_draft = saveAsDraft;
    loading.value = true;

    // Create FormData for file uploads
    const formData = new FormData();
    
    // Add form fields
    formData.append('_method', 'PUT');
    formData.append('claim_date', form.value.claim_date);
    formData.append('allowance_type', form.value.allowance_type);
    formData.append('currency', form.value.currency);
    formData.append('daily_rate', form.value.daily_rate);
    formData.append('purpose', form.value.purpose);
    formData.append('destination', form.value.destination);
    formData.append('save_as_draft', saveAsDraft);

    // Add existing attachments (send as array of indices to keep)
    form.value.existing_attachments.forEach((attachment, index) => {
        formData.append(`existing_attachments[${index}]`, attachment.name);
    });

    // Add new attachments
    form.value.attachments.forEach((file) => {
        formData.append('attachments[]', file);
    });

    router.post(route('user.daily-allowances.update', { dailyAllowance: props.dailyAllowance.id }), 
        formData,
        {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: saveAsDraft 
                        ? 'Daily allowance claim updated as draft successfully!' 
                        : 'Daily allowance claim updated and submitted successfully!',
                    life: 3000
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Please check the form for errors',
                    life: 3000
                });
            },
            onFinish: () => {
                loading.value = false;
            }
        }
    );
};

const allowanceTypeOptions = computed(() => {
    return Object.entries(props.allowanceTypes || {}).map(([value, label]) => ({
        value,
        label
    }));
});

const currencyOptions = computed(() => {
    return Object.entries(props.currencies || {}).map(([value, label]) => ({
        value,
        label
    }));
});
</script>

<template>
    <Head title="Edit Daily Allowance Claim" />
    <AppLayout>
        <Toast />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-gray-600 hover:text-gray-900 text-sm sm:text-base cursor-pointer" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Button icon="pi pi-arrow-left" text rounded @click="goBack" 
                        class="responsive-icon-button" v-tooltip="'Back to Claim Details'" />
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Edit Daily Allowance #{{ dailyAllowance.id }}</h1>
                        <p class="text-gray-500 text-sm mt-1">Update your daily allowance claim details</p>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Basic Information -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-blue-500"></i>
                                <span class="text-lg font-semibold">Basic Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Claim Date -->
                                <div class="space-y-2">
                                    <label for="claim_date" class="block text-sm font-medium text-gray-700">
                                        Claim Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="claim_date"
                                        v-model="form.claim_date"
                                        type="date"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': errors.claim_date }"
                                    />
                                    <div v-if="errors.claim_date" class="text-red-500 text-sm">
                                        {{ errors.claim_date }}
                                    </div>
                                </div>

                                <!-- Allowance Type -->
                                <div class="space-y-2">
                                    <label for="allowance_type" class="block text-sm font-medium text-gray-700">
                                        Allowance Type <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="allowance_type"
                                        v-model="form.allowance_type"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': errors.allowance_type }"
                                    >
                                        <option v-for="option in allowanceTypeOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <div v-if="errors.allowance_type" class="text-red-500 text-sm">
                                        {{ errors.allowance_type }}
                                    </div>
                                </div>

                                <!-- Currency -->
                                <div class="space-y-2">
                                    <label for="currency" class="block text-sm font-medium text-gray-700">
                                        Currency <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="currency"
                                        v-model="form.currency"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': errors.currency }"
                                    >
                                        <option v-for="option in currencyOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <div v-if="errors.currency" class="text-red-500 text-sm">
                                        {{ errors.currency }}
                                    </div>
                                </div>

                                <!-- Daily Rate -->
                                <div class="space-y-2">
                                    <label for="daily_rate" class="block text-sm font-medium text-gray-700">
                                        Daily Rate <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500">
                                                {{ form.currency === 'MYR' ? 'RM' : '$' }}
                                            </span>
                                        </div>
                                        <input
                                            id="daily_rate"
                                            v-model.number="form.daily_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            :class="{ 'border-red-300': errors.daily_rate }"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div v-if="errors.daily_rate" class="text-red-500 text-sm">
                                        {{ errors.daily_rate }}
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        Default rate: {{ formatCurrency(defaultRates[form.currency]?.[form.allowance_type], form.currency) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Details -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-green-500"></i>
                                <span class="text-lg font-semibold">Travel Details</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4 sm:space-y-6">
                                <!-- Destination -->
                                <div class="space-y-2">
                                    <label for="destination" class="block text-sm font-medium text-gray-700">
                                        Destination <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="destination"
                                        v-model="form.destination"
                                        type="text"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': errors.destination }"
                                        placeholder="Enter destination"
                                    />
                                    <div v-if="errors.destination" class="text-red-500 text-sm">
                                        {{ errors.destination }}
                                    </div>
                                </div>

                                <!-- Purpose -->
                                <div class="space-y-2">
                                    <label for="purpose" class="block text-sm font-medium text-gray-700">
                                        Purpose of Travel <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="purpose"
                                        v-model="form.purpose"
                                        rows="4"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-300': errors.purpose }"
                                        placeholder="Describe the purpose of your travel..."
                                        maxlength="500"
                                    ></textarea>
                                    <div v-if="errors.purpose" class="text-red-500 text-sm">
                                        {{ errors.purpose }}
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ form.purpose.length }}/500 characters
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-paperclip text-purple-500"></i>
                                <span class="text-lg font-semibold">Supporting Documents</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <!-- Existing Attachments -->
                                <div v-if="form.existing_attachments.length > 0" class="space-y-2">
                                    <h4 class="font-medium text-gray-700">Current Documents</h4>
                                    <div v-for="(doc, index) in form.existing_attachments" :key="index" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <i class="pi pi-file text-gray-500"></i>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ doc.name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ (doc.size / 1024).toFixed(1) }} KB
                                                </p>
                                            </div>
                                        </div>
                                        <Button icon="pi pi-times" text rounded severity="danger"
                                                @click="removeExistingAttachment(index)"
                                                v-tooltip="'Remove document'" />
                                    </div>
                                </div>

                                <!-- New Attachments -->
                                <div v-if="form.attachments.length > 0" class="space-y-2">
                                    <h4 class="font-medium text-gray-700">New Documents to Upload</h4>
                                    <div v-for="(file, index) in form.attachments" :key="index" 
                                         class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <i class="pi pi-file text-blue-500"></i>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ (file.size / 1024).toFixed(1) }} KB
                                                </p>
                                            </div>
                                        </div>
                                        <Button icon="pi pi-times" text rounded severity="danger"
                                                @click="removeNewAttachment(index)"
                                                v-tooltip="'Remove file'" />
                                    </div>
                                </div>

                                <!-- File Upload -->
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        @change="onFileSelect"
                                        class="hidden"
                                    />
                                    <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-600 mb-2">
                                        Drag & drop files here or
                                        <button type="button" @click="openFileDialog" class="text-blue-600 hover:text-blue-700 font-medium">
                                            browse files
                                        </button>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Supported formats: JPG, JPEG, PNG, PDF (Max: 10MB per file)
                                    </p>
                                </div>
                                <div v-if="errors.attachments" class="text-red-500 text-sm">
                                    {{ errors.attachments }}
                                </div>
                                <div v-if="errors['attachments.*']" class="text-red-500 text-sm">
                                    {{ errors['attachments.*'] }}
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Cost Summary -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-calculator text-red-500"></i>
                                <span class="text-lg font-semibold">Cost Summary</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Daily Rate:</span>
                                    <span class="font-semibold">{{ formatCurrency(form.daily_rate, form.currency) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Allowance Type:</span>
                                    <span class="font-semibold">{{ allowanceTypes[form.allowance_type] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Claim Percentage:</span>
                                    <span class="font-semibold">{{ getPercentageByType(form.allowance_type) }}%</span>
                                </div>
                                <div class="flex justify-between items-center border-t pt-2">
                                    <span class="text-lg font-bold text-gray-800">Total Claim:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(calculateClaimAmount, form.currency) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-bolt text-orange-500"></i>
                                <span class="text-lg font-semibold">Actions</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-3">
                                <Button label="Save as Draft" icon="pi pi-save" severity="warning"
                                        @click="submitForm(true)" :loading="loading"
                                        class="w-full responsive-button" />
                                
                                <Button label="Update & Submit" icon="pi pi-send" severity="primary"
                                        @click="submitForm(false)" :loading="loading"
                                        class="w-full responsive-button" />
                                
                                <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined
                                        @click="goBack"
                                        class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>

                    <!-- Help Information -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-question-circle text-green-500"></i>
                                <span class="text-lg font-semibold">Need Help?</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p>• Ensure all required fields are filled</p>
                                <p>• Attach supporting documents (receipts, etc.)</p>
                                <p>• Verify the calculated amount before submitting</p>
                                <p>• Contact admin for rate inquiries</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

.responsive-button :deep(.p-button-label) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 0.875rem;
    }
}

@media (min-width: 1024px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 1rem;
    }
}

.responsive-icon-button :deep(.p-button) {
    width: 2rem;
    height: 2rem;
}

@media (min-width: 640px) {
    .responsive-icon-button :deep(.p-button) {
        width: 2.5rem;
        height: 2.5rem;
    }
}

input, select, textarea {
    transition: all 0.2s ease-in-out;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>