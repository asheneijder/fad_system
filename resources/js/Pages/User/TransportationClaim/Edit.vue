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
    transportationClaim: {
        type: Object,
        default: () => ({})
    },
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
    { label: 'Transportation Claim #' + props.transportationClaim.id, url: route('user.transportation-claims.show', { transportationClaim: props.transportationClaim.id }) },
    { label: 'Edit Claim' }
];

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);

// File removal tracking
const removedTransportReceipts = ref([]);
const removedSupportingDocuments = ref([]);

const form = useForm({
    claim_date: props.transportationClaim.claim_date ? new Date(props.transportationClaim.claim_date) : new Date(),
    transport_type: props.transportationClaim.transport_type || '',
    purpose: props.transportationClaim.purpose || '',
    from_location: props.transportationClaim.from_location || '',
    to_location: props.transportationClaim.to_location || '',
    distance_km: props.transportationClaim.distance_km || null,
    rate_per_km: props.transportationClaim.rate_per_km || 0,
    amount: props.transportationClaim.amount || 0,
    currency: props.transportationClaim.currency || 'MYR',
    trip_type: props.transportationClaim.trip_type || 'one_way',
    number_of_trips: props.transportationClaim.number_of_trips || 1,
    receipt_number: props.transportationClaim.receipt_number || '',
    remarks: props.transportationClaim.remarks || '',
    transport_receipts: [],
    supporting_documents: [],
    save_as_draft: false,
});

// Watch transport type to update default rate
watch(() => form.transport_type, (newTransportType) => {
    if (newTransportType && props.defaultRates[newTransportType] !== undefined) {
        form.rate_per_km = props.defaultRates[newTransportType];
        calculateAmount();
    }
});

// Watch fields that affect amount calculation
watch([
    () => form.distance_km,
    () => form.rate_per_km,
    () => form.trip_type,
    () => form.number_of_trips
], () => {
    calculateAmount();
}, { deep: true });

// Calculate amount based on inputs
const calculateAmount = () => {
    let calculatedAmount = 0;

    // For distance-based transport
    if (form.distance_km && form.rate_per_km) {
        calculatedAmount = form.distance_km * form.rate_per_km;
    }

    // Apply trip type multiplier
    if (form.trip_type === 'round_trip') {
        calculatedAmount *= 2;
    }

    // Apply number of trips
    if (form.number_of_trips > 1) {
        calculatedAmount *= form.number_of_trips;
    }

    form.amount = parseFloat(calculatedAmount.toFixed(2));
};

// Check if transport type is distance-based
const isDistanceBased = computed(() => {
    return !['toll', 'parking'].includes(form.transport_type);
});

// Check if transport type is fixed amount
const isFixedAmount = computed(() => {
    return ['toll', 'parking'].includes(form.transport_type);
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

// Remove new transport receipt file
const removeTransportReceipt = (index) => {
    if (index >= 0 && index < form.transport_receipts.length) {
        form.transport_receipts.splice(index, 1);
    }
};

// Remove new supporting document file
const removeSupportingDocument = (index) => {
    if (index >= 0 && index < form.supporting_documents.length) {
        form.supporting_documents.splice(index, 1);
    }
};

// Remove existing transport receipt
const removeExistingTransportReceipt = (index, filename) => {
    if (props.transportationClaim.transport_receipts && props.transportationClaim.transport_receipts[index]) {
        removedTransportReceipts.value.push(filename);
        props.transportationClaim.transport_receipts.splice(index, 1);
    }
};

// Remove existing supporting document
const removeExistingSupportingDocument = (index, filename) => {
    if (props.transportationClaim.supporting_documents && props.transportationClaim.supporting_documents[index]) {
        removedSupportingDocuments.value.push(filename);
        props.transportationClaim.supporting_documents.splice(index, 1);
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
    form.removed_transport_receipts = removedTransportReceipts.value;
    form.removed_supporting_documents = removedSupportingDocuments.value;
    
    showAcknowledgmentDialog.value = false;
    
    form.put(route('user.transportation-claims.update', { transportationClaim: props.transportationClaim.id }), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Transportation claim saved as draft successfully!'
                : 'Transportation claim updated successfully!';
                
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
    form.removed_transport_receipts = removedTransportReceipts.value;
    form.removed_supporting_documents = removedSupportingDocuments.value;
    
    form.put(route('user.transportation-claims.update', { transportationClaim: props.transportationClaim.id }), {
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
    if (!form.from_location.trim()) return false;
    if (!form.to_location.trim()) return false;
    if (!form.currency) return false;
    if (!form.trip_type) return false;
    if (form.number_of_trips <= 0) return false;

    // For distance-based transport, check distance and rate
    if (isDistanceBased.value) {
        if (!form.distance_km || form.distance_km <= 0) return false;
        if (!form.rate_per_km || form.rate_per_km <= 0) return false;
    }

    // Check amount
    if (form.amount <= 0) return false;

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

// View claim details
const viewClaim = () => {
    router.get(route('user.transportation-claims.show', { transportationClaim: props.transportationClaim.id }));
};

// Calculate base amount for display
const baseAmount = computed(() => {
    if (!isDistanceBased.value) return form.amount;
    
    let base = (form.distance_km || 0) * (form.rate_per_km || 0);
    
    // Remove trip type multiplier for display
    if (form.trip_type === 'round_trip') {
        base /= 2;
    }
    
    // Remove number of trips multiplier for display
    if (form.number_of_trips > 1) {
        base /= form.number_of_trips;
    }
    
    return base;
});
</script>

<template>
    <Head :title="`Edit Transportation Claim #${transportationClaim.id}`" />
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
                    <Button label="Agree & Update Claim" severity="primary" 
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Edit Transportation Claim #{{ transportationClaim.id }}</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Update your transportation expense claim</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="View Claim" icon="pi pi-eye" severity="info" outlined
                        @click="viewClaim"
                        class="responsive-button" />
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

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Distance & Rate Information -->
                    <Card v-if="isDistanceBased" class="shadow-lg">
                        <template #title>Distance & Rate Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Distance (KM) *</label>
                                    <InputNumber v-model="form.distance_km" mode="decimal" :min="0" :max="1000"
                                        :fractionDigits="2" placeholder="e.g., 15.5" class="w-full"
                                        :class="{ 'p-invalid': form.errors.distance_km }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.distance_km">{{ form.errors.distance_km }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Rate per KM *</label>
                                    <div class="flex items-center gap-2">
                                        <InputNumber v-model="form.rate_per_km" mode="decimal" :min="0" :max="10"
                                            :fractionDigits="2" class="w-full flex-1"
                                            :class="{ 'p-invalid': form.errors.rate_per_km }" />
                                        <span class="text-sm text-gray-500 whitespace-nowrap">
                                            {{ getCurrencySymbol(form.currency) }}/km
                                        </span>
                                    </div>
                                    <small class="text-red-500 text-xs" v-if="form.errors.rate_per_km">{{ form.errors.rate_per_km }}</small>
                                    <small class="text-gray-500 text-xs">
                                        Default rate for {{ getTransportTypeDescription }}
                                    </small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Amount Information -->
                    <Card class="shadow-lg">
                        <template #title>Amount Information</template>
                        <template #content>
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
                                            :fractionDigits="2" class="w-full flex-1"
                                            :class="{ 'p-invalid': form.errors.amount }" 
                                            :disabled="isDistanceBased" />
                                        <span class="text-sm text-gray-500 whitespace-nowrap">
                                            {{ getCurrencySymbol(form.currency) }}
                                        </span>
                                    </div>
                                    <small class="text-red-500 text-xs" v-if="form.errors.amount">{{ form.errors.amount }}</small>
                                    <small v-if="isDistanceBased" class="text-gray-500 text-xs">
                                        Amount is auto-calculated based on distance, rate, and trip details
                                    </small>
                                    <small v-if="isFixedAmount" class="text-gray-500 text-xs">
                                        Enter the actual amount spent
                                    </small>
                                </div>
                            </div>

                            <!-- Amount Breakdown -->
                            <div v-if="isDistanceBased && form.amount > 0" class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <h4 class="font-semibold text-gray-800 mb-2">Amount Calculation:</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span>Base Amount:</span>
                                        <span>{{ formatCurrency(baseAmount, form.currency) }}</span>
                                    </div>
                                    <div v-if="form.trip_type === 'round_trip'" class="flex justify-between">
                                        <span>Round Trip (×2):</span>
                                        <span>× 2</span>
                                    </div>
                                    <div v-if="form.number_of_trips > 1" class="flex justify-between">
                                        <span>Number of Trips (×{{ form.number_of_trips }}):</span>
                                        <span>× {{ form.number_of_trips }}</span>
                                    </div>
                                    <hr class="my-1">
                                    <div class="flex justify-between font-semibold">
                                        <span>Total:</span>
                                        <span>{{ formatCurrency(form.amount, form.currency) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Receipt Number</label>
                                    <InputText v-model="form.receipt_number" placeholder="e.g., RCPT-001"
                                        class="w-full" />
                                    <small class="text-gray-500 text-xs">Optional receipt number for reference</small>
                                </div>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                <Textarea v-model="form.remarks" rows="2" placeholder="Additional notes or remarks..."
                                    class="w-full" />
                                <small class="text-gray-500 text-xs">Optional remarks about your transportation</small>
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
                                    <h4 class="text-sm font-medium text-gray-700">Transport Receipts</h4>
                                    
                                    <!-- Existing Transport Receipts -->
                                    <div v-if="transportationClaim.transport_receipts && transportationClaim.transport_receipts.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Existing Receipts:</h5>
                                        <div v-for="(doc, index) in transportationClaim.transport_receipts" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-file text-gray-500"></i>
                                                <span class="text-sm text-gray-600">{{ doc.name }}</span>
                                            </div>
                                            <div class="flex gap-1">
                                                <Button icon="pi pi-download" text rounded severity="info"
                                                    @click="window.open(doc.url, '_blank')"
                                                    v-tooltip="'Download receipt'" />
                                                <Button icon="pi pi-times" text rounded severity="danger"
                                                    @click="removeExistingTransportReceipt(index, doc.name)"
                                                    v-tooltip="'Remove receipt'" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Additional Transport Receipts</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onTransportReceiptsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- New Transport Receipts List -->
                                    <div v-if="form.transport_receipts.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">New Receipts to Upload:</h5>
                                        <div v-for="(file, index) in form.transport_receipts" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-600">{{ file.name }}</span>
                                            <Button icon="pi pi-times" severity="danger" text rounded
                                                @click="removeTransportReceipt(index)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Supporting Documents</h4>
                                    
                                    <!-- Existing Supporting Documents -->
                                    <div v-if="transportationClaim.supporting_documents && transportationClaim.supporting_documents.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Existing Documents:</h5>
                                        <div v-for="(doc, index) in transportationClaim.supporting_documents" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-file text-gray-500"></i>
                                                <span class="text-sm text-gray-600">{{ doc.name }}</span>
                                            </div>
                                            <div class="flex gap-1">
                                                <Button icon="pi pi-download" text rounded severity="info"
                                                    @click="window.open(doc.url, '_blank')"
                                                    v-tooltip="'Download document'" />
                                                <Button icon="pi pi-times" text rounded severity="danger"
                                                    @click="removeExistingSupportingDocument(index, doc.name)"
                                                    v-tooltip="'Remove document'" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Additional Supporting Documents</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onSupportingDocumentsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- New Supporting Documents List -->
                                    <div v-if="form.supporting_documents.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">New Documents to Upload:</h5>
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
                                    <span class="text-sm text-gray-600">Claim ID:</span>
                                    <span class="text-sm font-medium">#{{ transportationClaim.id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Transport Type:</span>
                                    <span class="text-sm font-medium">{{ getTransportTypeDescription || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
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
                                <div v-if="isDistanceBased" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Distance:</span>
                                    <span class="text-sm font-medium">{{ form.distance_km || 0 }} km</span>
                                </div>
                                <div v-if="isDistanceBased" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate:</span>
                                    <span class="text-sm font-medium">{{ form.rate_per_km }} {{ getCurrencySymbol(form.currency) }}/km</span>
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
                                <Button label="Update Claim" icon="pi pi-check" severity="success" 
                                    @click="openAcknowledgmentDialog" :loading="form.processing" :disabled="!validateForm()"
                                    class="w-full responsive-button" />
                                
                                <Button label="Save as Draft" icon="pi pi-save" severity="secondary" outlined
                                    @click="saveAsDraft" :loading="form.processing"
                                    class="w-full responsive-button" />
                                
                                <Button label="Cancel" icon="pi pi-times" severity="danger" text
                                    @click="viewClaim"
                                    class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>

                    <!-- Help Tips -->
                    <Card class="shadow-lg border-l-4 border-blue-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Editing Tips:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li>You can modify any transportation details</li>
                                    <li>Changing transport type will update the default rate</li>
                                    <li>Additional documents will be appended to existing ones</li>
                                    <li>Submit for approval when all changes are complete</li>
                                    <li>Save as draft to continue editing later</li>
                                    <li>Removed documents cannot be recovered</li>
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