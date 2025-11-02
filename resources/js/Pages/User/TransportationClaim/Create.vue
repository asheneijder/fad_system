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
    transportTypes: {
        type: Array,
        default: () => []
    },
    tripTypes: {
        type: Array,
        default: () => []
    },
    currencies: {
        type: Array,
        default: () => []
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
    { label: 'New Transportation Claim' }
];

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);

const form = useForm({
    claim_date: new Date(),
    transport_type: '',
    purpose: '',
    from_location: '',
    to_location: '',
    distance_km: null,
    rate_per_km: 0,
    amount: 0,
    currency: 'MYR',
    trip_type: 'one_way',
    number_of_trips: 1,
    receipt_number: '',
    remarks: '',
    transport_receipts: [],
    supporting_documents: [],
    save_as_draft: false,
});

// Remove all auto-calculation watchers and functions
// Users will enter the total amount directly for all transport types

// Watch trip type and number of trips to show info message (but don't auto-calculate)
watch([
    () => form.trip_type,
    () => form.number_of_trips
], () => {
    // Just show info that user should adjust amount manually if needed
    if (form.amount > 0) {
        toast.add({
            severity: 'info',
            summary: 'Amount Adjustment',
            detail: 'Remember to adjust the total amount manually for trip type and number of trips',
            life: 3000
        });
    }
});

// Check if transport type requires location fields
const requiresLocation = computed(() => {
    return form.transport_type && form.transport_type !== 'parking';
});

// Handle file upload for transport receipts
const onTransportReceiptsSelect = (event) => {
    const files = Array.from(event.target.files);
    form.transport_receipts = [...form.transport_receipts, ...files];
};

// Handle file upload for supporting documents
const onSupportingDocumentsSelect = (event) => {
    const files = Array.from(event.target.files);
    form.supporting_documents = [...form.supporting_documents, ...files];
};

// Remove transport receipt file
const removeTransportReceipt = (index) => {
    if (index >= 0 && index < form.transport_receipts.length) {
        form.transport_receipts.splice(index, 1);
    }
};

// Remove supporting document file
const removeSupportingDocument = (index) => {
    if (index >= 0 && index < form.supporting_documents.length) {
        form.supporting_documents.splice(index, 1);
    }
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
    const buffer = 5; // 5px buffer
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
    
    form.post(route('user.transportation-claims.store'), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Transportation claim saved as draft successfully!'
                : 'Transportation claim submitted successfully!';
                
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
    
    form.post(route('user.transportation-claims.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Transportation claim saved as draft successfully!',
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
    // Check required fields
    if (!form.claim_date) return false;
    if (!form.transport_type) return false;
    if (!form.purpose.trim()) return false;
    if (requiresLocation.value) {
        if (!form.from_location.trim()) return false;
        if (!form.to_location.trim()) return false;
    }
    if (!form.currency) return false;
    if (!form.trip_type) return false;
    if (form.number_of_trips <= 0) return false;

    // Check amount (required for all transport types)
    if (!form.amount || form.amount <= 0) return false;

    // Check required files
    if (form.transport_type === 'flight') {
        if (!form.flight_receipts.length) return false;
    } else if (form.transport_type === 'train') {
        if (!form.train_receipts.length) return false;
    } else if (form.transport_type === 'bus') {
        if (!form.bus_receipts.length) return false;
    } else if (form.transport_type === 'car') {
        if (!form.car_receipts.length) return false;
    } else if (form.transport_type === 'mrt') {
        if (!form.mrt_receipts.length) return false;
    }

    return true;
};

// Format currency
const formatCurrency = (amount, currency = 'MYR') => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: currency
    }).format(amount || 0);
};

// Get currency symbol
const getCurrencySymbol = (currency) => {
    const symbols = {
        MYR: 'RM',
        USD: '$'
    };
    return symbols[currency] || currency;
};

// Current date for declaration
const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Get transport type description
const getTransportTypeDescription = computed(() => {
    const type = props.transportTypes.find(t => t.value === form.transport_type);
    return type ? type.label : '';
});
</script>

<template>
    <Head title="New Transportation Claim" />
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
                                Pengangkutan pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(form.amount, form.currency) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(form.amount, form.currency) }}</p>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">New Transportation Claim</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Create a new transportation expense claim</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.get(route('user.request-claim.index'))"
                        class="responsive-button" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transportation Information -->
                    <Card class="shadow-lg">
                        <template #title>Transportation Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Claim Date *</label>
                                        <DatePicker v-model="form.claim_date" dateFormat="yy-mm-dd" showIcon
                                            class="w-full" :class="{ 'p-invalid': form.errors.claim_date }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.claim_date">{{ form.errors.claim_date }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Transport Type *</label>
                                        <Select v-model="form.transport_type" :options="transportTypes" optionLabel="label" optionValue="value"
                                            placeholder="Select Transport Type" class="w-full"
                                            :class="{ 'p-invalid': form.errors.transport_type }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.transport_type">{{ form.errors.transport_type }}</small>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Purpose of Travel *</label>
                                    <Textarea v-model="form.purpose" rows="3" placeholder="Describe the purpose of your transportation..."
                                        class="w-full" :class="{ 'p-invalid': form.errors.purpose }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.purpose">{{ form.errors.purpose }}</small>
                                </div>

                                <div v-if="requiresLocation" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">From Location *</label>
                                        <InputText v-model="form.from_location" placeholder="e.g., Kuala Lumpur"
                                            class="w-full" :class="{ 'p-invalid': form.errors.from_location }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.from_location">{{ form.errors.from_location }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">To Location *</label>
                                        <InputText v-model="form.to_location" placeholder="e.g., Petaling Jaya"
                                            class="w-full" :class="{ 'p-invalid': form.errors.to_location }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.to_location">{{ form.errors.to_location }}</small>
                                    </div>
                                </div>

                                <div v-else class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <p class="text-sm text-blue-700">
                                        💡 For {{ getTransportTypeDescription }}, location fields are optional.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Trip Type *</label>
                                        <Select v-model="form.trip_type" :options="tripTypes" optionLabel="label" optionValue="value"
                                            placeholder="Select Trip Type" class="w-full"
                                            :class="{ 'p-invalid': form.errors.trip_type }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.trip_type">{{ form.errors.trip_type }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Number of Trips *</label>
                                        <InputNumber v-model="form.number_of_trips" mode="decimal" :min="1" :max="10"
                                            class="w-full" :class="{ 'p-invalid': form.errors.number_of_trips }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.number_of_trips">{{ form.errors.number_of_trips }}</small>
                                        <small class="text-gray-500 text-xs">
                                            Remember to include this in your total amount calculation
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Amount Information -->
                    <Card class="shadow-lg">
                        <template #title>Amount Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Currency *</label>
                                        <Select v-model="form.currency" :options="currencies" optionLabel="label" optionValue="value"
                                            placeholder="Select Currency" class="w-full"
                                            :class="{ 'p-invalid': form.errors.currency }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.currency">{{ form.errors.currency }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Total Amount *</label>
                                        <div class="flex items-center gap-2">
                                            <InputNumber v-model="form.amount" mode="decimal" :min="0" :max="10000"
                                                :fractionDigits="2" placeholder="0.00" class="w-full flex-1"
                                                :class="{ 'p-invalid': form.errors.amount }" />
                                            <span class="text-sm text-gray-500 whitespace-nowrap">
                                                {{ getCurrencySymbol(form.currency) }}
                                            </span>
                                        </div>
                                        <small class="text-red-500 text-xs" v-if="form.errors.amount">{{ form.errors.amount }}</small>
                                        <small class="text-gray-500 text-xs">
                                            Enter the total amount based on your actual expense
                                        </small>
                                    </div>
                                </div>

                                <!-- Trip Information Helper -->
                                <div v-if="form.trip_type !== 'one_way' || form.number_of_trips > 1" 
                                     class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <h4 class="font-semibold text-yellow-800 text-sm mb-2">Trip Information:</h4>
                                    <div class="space-y-1 text-sm text-yellow-700">
                                        <div class="flex justify-between">
                                            <span>Trip Type:</span>
                                            <span class="font-medium">{{ form.trip_type === 'round_trip' ? 'Round Trip' : 'One Way' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Number of Trips:</span>
                                            <span class="font-medium">{{ form.number_of_trips }}</span>
                                        </div>
                                        <div class="mt-2 text-xs">
                                            💡 Ensure your total amount reflects the {{ form.trip_type === 'round_trip' ? 'round trip' : '' }} 
                                            and {{ form.number_of_trips > 1 ? form.number_of_trips + ' trips' : 'single trip' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Receipt Number</label>
                                        <InputText v-model="form.receipt_number" placeholder="e.g., RCPT-001"
                                            class="w-full" />
                                        <small class="text-gray-500 text-xs">Optional receipt number for reference</small>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <Textarea v-model="form.remarks" rows="2" placeholder="Additional notes or remarks about your claim..."
                                        class="w-full" />
                                    <small class="text-gray-500 text-xs">Optional remarks about your transportation claim</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card class="shadow-lg">
                        <template #title>Attachments</template>
                        <template #content>
                            <div class="space-y-6">
                                <!-- Transport Receipts -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Transport Receipts *</h4>
                                    
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Transport Receipts</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onTransportReceiptsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                        <small class="text-red-500 text-xs" v-if="form.errors.transport_receipts">{{ form.errors.transport_receipts }}</small>
                                    </div>

                                    <!-- Transport Receipts List -->
                                    <div v-if="form.transport_receipts.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Receipts to Upload:</h5>
                                        <div v-for="(file, index) in form.transport_receipts" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-600">{{ file.name }}</span>
                                            <Button icon="pi pi-times" severity="danger" text rounded
                                                @click="removeTransportReceipt(index)" />
                                        </div>
                                    </div>
                                    <div v-else class="p-3 bg-orange-50 rounded border border-orange-200">
                                        <p class="text-xs text-orange-700">
                                            ⚠️ Receipts are required for claim verification
                                        </p>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Supporting Documents</h4>
                                    
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Supporting Documents</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onSupportingDocumentsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- Supporting Documents List -->
                                    <div v-if="form.supporting_documents.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Documents to Upload:</h5>
                                        <div v-for="(file, index) in form.supporting_documents" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-600">{{ file.name }}</span>
                                            <Button icon="pi pi-times" severity="danger" text rounded
                                                @click="removeSupportingDocument(index)" />
                                        </div>
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
                                    <span class="text-sm text-gray-600">Transport Type:</span>
                                    <span class="text-sm font-medium">{{ getTransportTypeDescription || '—' }}</span>
                                </div>
                                <div v-if="requiresLocation" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Route:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ form.from_location || '—' }} → {{ form.to_location || '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trip Type:</span>
                                    <span class="text-sm font-medium">{{ form.trip_type ? tripTypes.find(t => t.value === form.trip_type)?.label : '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Number of Trips:</span>
                                    <span class="text-sm font-medium">{{ form.number_of_trips }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ form.currency }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(form.amount, form.currency) }}</span>
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
                                <Button label="Submit Claim" icon="pi pi-check" severity="success" 
                                    @click="openAcknowledgmentDialog" :loading="form.processing" :disabled="!validateForm()"
                                    class="w-full responsive-button" />
                                
                                <Button label="Save as Draft" icon="pi pi-save" severity="secondary" outlined
                                    @click="saveAsDraft" :loading="form.processing"
                                    class="w-full responsive-button" />
                                
                                <Button label="Cancel" icon="pi pi-times" severity="danger" text
                                    @click="router.get(route('user.request-claim.index'))"
                                    class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>

                    <!-- Help Tips -->
                    <Card class="shadow-lg border-l-4 border-blue-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Tips:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li>Select the appropriate transport type</li>
                                    <li>Enter the actual total amount from your receipts</li>
                                    <li>For multiple trips, ensure total amount includes all trips</li>
                                    <li>Location is optional for parking claims</li>
                                    <li>Upload clear photos of all receipts</li>
                                    <li>Receipts are required for claim verification</li>
                                    <li>Save as draft to complete later if needed</li>
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
/* Responsive styles */
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
</style>