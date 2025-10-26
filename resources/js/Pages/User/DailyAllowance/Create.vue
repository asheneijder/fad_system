<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker";
import InputNumber from "primevue/inputnumber";
import Checkbox from "primevue/checkbox";
import Breadcrumb from "primevue/breadcrumb";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import { useToast } from "primevue/usetoast";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const toast = useToast();

const props = defineProps({
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
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Create Daily Allowance Claim' }
];

// Form state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);

const form = useForm({
    claim_date: new Date(),
    allowance_type: 'full_day',
    currency: 'MYR',
    daily_rate: null,
    purpose: '',
    destination: '',
    attachments: [],
    save_as_draft: false,
});

// Convert allowance types object to array for Select component
const allowanceTypeOptions = computed(() => {
    return Object.entries(props.allowanceTypes).map(([value, label]) => ({
        value,
        label
    }));
});

// Convert currencies object to array for Select component
const currencyOptions = computed(() => {
    return Object.entries(props.currencies).map(([value, label]) => ({
        value,
        label
    }));
});

// Calculate claim amount based on selected type and rate
const claimAmount = computed(() => {
    if (!form.daily_rate || !form.allowance_type) return 0;
    
    const percentage = getPercentageByType(form.allowance_type);
    return (form.daily_rate * percentage) / 100;
});

// Get percentage based on allowance type
const getPercentageByType = (type) => {
    const percentages = {
        full_day: 100,
        breakfast: 20,
        lunch: 40,
        dinner: 40
    };
    return percentages[type] || 0;
};

// Get default rate based on currency
const getDefaultRate = (currency, allowanceType) => {
    return props.defaultRates[currency]?.[allowanceType] || 0;
};

// Watch currency and allowance type to set default rate
watch([() => form.currency, () => form.allowance_type], ([currency, allowanceType]) => {
    if (currency && allowanceType && !form.daily_rate) {
        form.daily_rate = getDefaultRate(currency, allowanceType);
    }
}, { immediate: true });

// Handle file upload
const onFileSelect = (event) => {
    const files = Array.from(event.target.files);
    form.attachments = [...form.attachments, ...files];
};

// Remove file from attachments
const removeFile = (index) => {
    form.attachments.splice(index, 1);
};

// Open acknowledgment dialog
const openAcknowledgmentDialog = () => {
    if (!validateForm()) {
        toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: 'Please fill in all required fields',
            life: 5000
        });
        return;
    }
    
    showAcknowledgmentDialog.value = true;
    agreedToTerms.value = false;
    hasScrolledToBottom.value = false;
};

// Handle scroll in acknowledgment dialog
const onAcknowledgmentScroll = (event) => {
    const element = event.target;
    const buffer = 5;
    const isAtBottom = element.scrollTop + element.clientHeight >= element.scrollHeight - buffer;
    
    if (isAtBottom) {
        hasScrolledToBottom.value = true;
    }
};

// Submit form after agreement
const submitFormWithAgreement = (saveAsDraft = false) => {
    if (!agreedToTerms.value) {
        toast.add({
            severity: 'warn',
            summary: 'Acknowledgement Required',
            detail: 'Please agree to the terms before submitting',
            life: 3000
        });
        return;
    }

    form.save_as_draft = saveAsDraft;
    showAcknowledgmentDialog.value = false;
    
    form.post(route('user.daily-allowances.store'), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Daily allowance claim saved as draft successfully!'
                : 'Daily allowance claim submitted successfully!';
                
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: message,
                life: 5000
            });
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Please check the form for errors',
                life: 5000
            });
        }
    });
};

// Save as draft without acknowledgment
const saveAsDraft = () => {
    if (!validateForm()) {
        toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: 'Please fill in all required fields',
            life: 5000
        });
        return;
    }

    form.save_as_draft = true;
    
    form.post(route('user.daily-allowances.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Daily allowance claim saved as draft successfully!',
                life: 5000
            });
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Please check the form for errors',
                life: 5000
            });
        }
    });
};

// Validate form before submission
const validateForm = () => {
    return form.claim_date && 
           form.allowance_type && 
           form.currency && 
           form.daily_rate && 
           form.daily_rate > 0 &&
           form.purpose.trim() && 
           form.destination.trim();
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: form.currency || 'MYR'
    }).format(amount || 0);
};

// Current date for declaration
const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Get percentage display text
const percentageDisplay = computed(() => {
    return getPercentageByType(form.allowance_type) + '%';
});

// Get allowance type description
const allowanceTypeDescription = computed(() => {
    const descriptions = {
        full_day: 'Full day meal allowance (100%)',
        breakfast: 'Breakfast allowance (20%)',
        lunch: 'Lunch allowance (40%)',
        dinner: 'Dinner allowance (40%)'
    };
    return descriptions[form.allowance_type] || '';
});

// Cancel and go back
const cancelCreate = () => {
    router.get(route('user.request-claim.index'));
};
</script>

<template>
    <Head title="Create Daily Allowance Claim" />
    <AppLayout>
        <Toast />

        <!-- Acknowledgment Dialog -->
        <Dialog v-model:visible="showAcknowledgmentDialog" modal header="Declaration and Acknowledgment" 
                :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div class="space-y-4">
                <div class="text-sm text-gray-600">
                    <p>Please read the following declaration carefully before submitting your claim.</p>
                </div>

                <div id="acknowledgment-content" 
                     class="border border-gray-200 rounded-lg p-4 max-h-60 overflow-y-auto bg-gray-50"
                     @scroll="onAcknowledgmentScroll">
                    <div class="space-y-4 text-sm text-gray-700">
                        <p class="font-semibold text-center text-gray-800 mb-4">
                            PERAKUAN DAN PENGAKUAN
                        </p>
                        
                        <p>
                            Saya dengan ini mengaku bahawa:
                        </p>
                        
                        <ol class="list-decimal list-inside space-y-2 ml-4">
                            <li class="pl-2">
                                Tuntutan elaun harian pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(claimAmount) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(claimAmount) }}</p>
                            <p class="mt-2">Tarikh: {{ currentDate }}</p>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-xs text-yellow-700">
                                <strong>Perhatian:</strong> Pengesahan ini adalah mengikat. Sebarang maklumat palsu boleh menyebabkan tindakan tatatertib.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <Checkbox v-model="agreedToTerms" inputId="agreeTerms" :binary="true" 
                             :disabled="!hasScrolledToBottom" />
                    <label for="agreeTerms" class="text-sm text-gray-700">
                        Saya telah membaca dan memahami semua pernyataan di atas dan bersetuju dengan syarat-syarat yang dinyatakan.
                        <span v-if="!hasScrolledToBottom" class="text-orange-600 text-xs block mt-1">
                            (Sila baca sehingga ke akhir untuk membolehkan kotak semak)
                        </span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
                    <Button label="Cancel" severity="secondary" text
                            @click="showAcknowledgmentDialog = false" />
                    <Button label="Agree & Submit Claim" severity="primary" 
                            @click="submitFormWithAgreement(false)" 
                            :disabled="!agreedToTerms || !hasScrolledToBottom"
                            :loading="form.processing" />
                </div>
            </div>
        </Dialog>

        <div class="p-3 sm:p-4 md:p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 hover:text-blue-800 cursor-pointer" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">
                        {{ item.label }}
                    </span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Create Daily Allowance Claim</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Submit your daily meal allowance claim</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="cancelCreate"
                        class="responsive-button" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Claim Information -->
                    <Card class="shadow-lg">
                        <template #title>Claim Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Claim Date *</label>
                                    <DatePicker v-model="form.claim_date" dateFormat="yy-mm-dd" showIcon
                                        class="w-full" :class="{ 'p-invalid': form.errors.claim_date }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.claim_date">{{ form.errors.claim_date }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Destination *</label>
                                    <InputText v-model="form.destination" placeholder="e.g., Kuala Lumpur, Penang, etc."
                                        class="w-full" :class="{ 'p-invalid': form.errors.destination }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.destination">{{ form.errors.destination }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Allowance Type *</label>
                                    <Select v-model="form.allowance_type" :options="allowanceTypeOptions" optionLabel="label" optionValue="value"
                                        placeholder="Select Allowance Type" class="w-full"
                                        :class="{ 'p-invalid': form.errors.allowance_type }" />
                                    <small class="text-gray-500 text-xs">{{ allowanceTypeDescription }}</small>
                                    <small class="text-red-500 text-xs" v-if="form.errors.allowance_type">{{ form.errors.allowance_type }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Currency *</label>
                                    <Select v-model="form.currency" :options="currencyOptions" optionLabel="label" optionValue="value"
                                        placeholder="Select Currency" class="w-full"
                                        :class="{ 'p-invalid': form.errors.currency }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.currency">{{ form.errors.currency }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Daily Rate ({{ form.currency }}) *</label>
                                    <InputNumber v-model="form.daily_rate" mode="decimal" :min="0" :max="9999"
                                        placeholder="Enter daily rate"
                                        class="w-full" :class="{ 'p-invalid': form.errors.daily_rate }" />
                                    <small class="text-gray-500 text-xs">Standard rate for {{ form.allowance_type }} allowance</small>
                                    <small class="text-red-500 text-xs" v-if="form.errors.daily_rate">{{ form.errors.daily_rate }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Claim Percentage</label>
                                    <InputText :value="percentageDisplay" class="w-full" disabled />
                                    <small class="text-gray-500 text-xs">Percentage based on allowance type</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Purpose & Details -->
                    <Card class="shadow-lg">
                        <template #title>Purpose & Additional Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Purpose of Claim *</label>
                                    <Textarea v-model="form.purpose" rows="3" placeholder="Describe the purpose of your travel and why you need this allowance..."
                                        class="w-full" :class="{ 'p-invalid': form.errors.purpose }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.purpose">{{ form.errors.purpose }}</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card class="shadow-lg">
                        <template #title>Supporting Documents</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Upload Receipts or Supporting Documents</label>
                                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onFileSelect"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                </div>

                                <!-- File List -->
                                <div v-if="form.attachments.length > 0" class="space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700">Files to Upload:</h4>
                                    <div v-for="(file, index) in form.attachments" :key="index" 
                                         class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span class="text-sm text-gray-600">{{ file.name }}</span>
                                        <Button icon="pi pi-times" severity="danger" text rounded
                                            @click="removeFile(index)" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar - Summary & Actions -->
                <div class="space-y-6">
                    <!-- Summary Card -->
                    <Card class="shadow-lg sticky top-6">
                        <template #title>Claim Summary</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Allowance Type:</span>
                                    <span class="text-sm font-medium">{{ props.allowanceTypes[form.allowance_type] || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ form.currency }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Daily Rate:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(form.daily_rate) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Claim Percentage:</span>
                                    <span class="text-sm font-medium">{{ percentageDisplay }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Claim Amount:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claimAmount) }}</span>
                                </div>
                                
                                <!-- Validation Status -->
                                <div v-if="!validateForm()" class="p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="text-xs text-yellow-700 text-center">
                                        ⚠️ Please complete all required fields
                                    </p>
                                </div>
                                <div v-else class="p-2 bg-green-50 border border-green-200 rounded">
                                    <p class="text-xs text-green-700 text-center">
                                        ✓ All required fields completed
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Action Buttons -->
                    <Card class="shadow-lg">
                        <template #content>
                            <div class="space-y-3">
                                <Button label="Submit for Approval" icon="pi pi-check" severity="success" 
                                    @click="openAcknowledgmentDialog" :loading="form.processing" :disabled="!validateForm()"
                                    class="w-full responsive-button" />
                                
                                <Button label="Save as Draft" icon="pi pi-save" severity="secondary" outlined
                                    @click="saveAsDraft" :loading="form.processing" :disabled="!validateForm()"
                                    class="w-full responsive-button" />
                                
                                <Button label="Cancel" icon="pi pi-times" severity="danger" text
                                    @click="cancelCreate"
                                    class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>

                    <!-- Help Tips -->
                    <Card class="shadow-lg border-l-4 border-blue-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Allowance Information:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li><strong>Full Day (100%):</strong> Complete daily meal allowance</li>
                                    <li><strong>Breakfast (20%):</strong> Morning meal allowance only</li>
                                    <li><strong>Lunch (40%):</strong> Afternoon meal allowance only</li>
                                    <li><strong>Dinner (40%):</strong> Evening meal allowance only</li>
                                    <li>Standard rates are provided as reference</li>
                                    <li>Upload receipts for better approval process</li>
                                </ul>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
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

:deep(.p-invalid) {
    border-color: #e53e3e !important;
}

/* Style for disabled input */
:deep(.p-inputnumber.p-component.p-disabled) {
    opacity: 0.6;
    background-color: #f9fafb;
    border-color: #d1d5db;
}

:deep(.p-inputnumber.p-component.p-disabled .p-inputtext) {
    background-color: #f9fafb;
    color: #6b7280;
}
</style>