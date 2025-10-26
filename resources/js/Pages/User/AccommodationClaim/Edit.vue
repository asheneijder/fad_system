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
import { ref, computed, watch, onMounted } from "vue";

const toast = useToast();

const props = defineProps({
    accommodationClaim: {
        type: Object,
        default: () => ({})
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
    { label: 'Accommodation Claim #' + props.accommodationClaim.id, url: route('user.accommodation-claims.show', { accommodationClaim: props.accommodationClaim.id }) },
    { label: 'Edit Claim' }
];

// FIX: Format currencies for Select component
const formattedCurrencies = computed(() => {
    if (!props.currencies || !Array.isArray(props.currencies)) {
        return [
            { label: 'MYR - Malaysian Ringgit', value: 'MYR' },
            { label: 'USD - US Dollar', value: 'USD' },
            { label: 'SGD - Singapore Dollar', value: 'SGD' }
        ];
    }
    
    // If currencies is already in the correct format, return as is
    if (props.currencies.length > 0 && props.currencies[0].label && props.currencies[0].value) {
        return props.currencies;
    }
    
    // If currencies is an array of strings, format them
    if (props.currencies.length > 0 && typeof props.currencies[0] === 'string') {
        return props.currencies.map(currency => {
            const labels = {
                'MYR': 'MYR - Malaysian Ringgit',
                'USD': 'USD - US Dollar', 
                'SGD': 'SGD - Singapore Dollar'
            };
            return {
                label: labels[currency] || currency,
                value: currency
            };
        });
    }
    
    // Fallback to default currencies
    return [
        { label: 'MYR - Malaysian Ringgit', value: 'MYR' },
        { label: 'USD - US Dollar', value: 'USD' },
        { label: 'SGD - Singapore Dollar', value: 'SGD' }
    ];
});

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);

// File removal tracking
const removedHotelReceipts = ref([]);
const removedSupportingDocuments = ref([]);

const form = useForm({
    hotel_name: props.accommodationClaim.hotel_name || '',
    check_in_date: props.accommodationClaim.check_in_date ? new Date(props.accommodationClaim.check_in_date) : null,
    check_out_date: props.accommodationClaim.check_out_date ? new Date(props.accommodationClaim.check_out_date) : null,
    number_of_nights: props.accommodationClaim.number_of_nights || 1,
    rate_per_night: props.accommodationClaim.rate_per_night || 0,
    currency: props.accommodationClaim.currency || 'MYR',
    tax_percentage: props.accommodationClaim.tax_percentage || 0,
    service_charge_percentage: props.accommodationClaim.service_charge_percentage || 0,
    tax_amount: props.accommodationClaim.tax_amount || 0,
    service_charge_amount: props.accommodationClaim.service_charge_amount || 0,
    subtotal_amount: props.accommodationClaim.subtotal_amount || 0,
    total_amount: props.accommodationClaim.total_amount || 0,
    purpose: props.accommodationClaim.purpose || '',
    destination_city: props.accommodationClaim.destination_city || '',
    destination_country: props.accommodationClaim.destination_country || '',
    remarks: props.accommodationClaim.remarks || '',
    hotel_receipts: [],
    supporting_documents: [],
    save_as_draft: false,
});

// Watch currency to update default rates
watch(() => form.currency, (newCurrency) => {
    const defaultRate = props.defaultRates[newCurrency];
    if (defaultRate) {
        form.tax_percentage = defaultRate.tax_percentage;
        form.service_charge_percentage = defaultRate.service_charge_percentage;
    }
    calculateAmounts();
});

// Watch rate and nights to calculate amounts
watch([
    () => form.rate_per_night,
    () => form.number_of_nights,
    () => form.tax_percentage,
    () => form.service_charge_percentage
], () => {
    calculateAmounts();
}, { deep: true });

// Watch check-in and check-out dates to calculate number of nights
watch([() => form.check_in_date, () => form.check_out_date], ([checkIn, checkOut]) => {
    if (checkIn && checkOut) {
        const timeDiff = checkOut.getTime() - checkIn.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        if (nights > 0) {
            form.number_of_nights = nights;
        }
    }
});

// Calculate all amounts
const calculateAmounts = () => {
    const subtotal = form.rate_per_night * form.number_of_nights;
    const taxAmount = (subtotal * form.tax_percentage) / 100;
    const serviceChargeAmount = (subtotal * form.service_charge_percentage) / 100;
    const total = subtotal + taxAmount + serviceChargeAmount;

    form.subtotal_amount = parseFloat(subtotal.toFixed(2));
    form.tax_amount = parseFloat(taxAmount.toFixed(2));
    form.service_charge_amount = parseFloat(serviceChargeAmount.toFixed(2));
    form.total_amount = parseFloat(total.toFixed(2));
};

// Handle file upload for hotel receipts
const onHotelReceiptsSelect = (event) => {
    const files = Array.from(event.target.files);
    form.hotel_receipts = [...form.hotel_receipts, ...files];
};

// Handle file upload for supporting documents
const onSupportingDocumentsSelect = (event) => {
    const files = Array.from(event.target.files);
    form.supporting_documents = [...form.supporting_documents, ...files];
};

// Remove new hotel receipt file
const removeHotelReceipt = (index) => {
    if (index >= 0 && index < form.hotel_receipts.length) {
        form.hotel_receipts.splice(index, 1);
    }
};

// Remove new supporting document file
const removeSupportingDocument = (index) => {
    if (index >= 0 && index < form.supporting_documents.length) {
        form.supporting_documents.splice(index, 1);
    }
};

// Remove existing hotel receipt
const removeExistingHotelReceipt = (index, filename) => {
    if (props.accommodationClaim.hotel_receipts && props.accommodationClaim.hotel_receipts[index]) {
        removedHotelReceipts.value.push(filename);
        props.accommodationClaim.hotel_receipts.splice(index, 1);
    }
};

// Remove existing supporting document
const removeExistingSupportingDocument = (index, filename) => {
    if (props.accommodationClaim.supporting_documents && props.accommodationClaim.supporting_documents[index]) {
        removedSupportingDocuments.value.push(filename);
        props.accommodationClaim.supporting_documents.splice(index, 1);
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
    form.removed_hotel_receipts = removedHotelReceipts.value;
    form.removed_supporting_documents = removedSupportingDocuments.value;
    
    showAcknowledgmentDialog.value = false;
    
    form.put(route('user.accommodation-claims.update', { accommodationClaim: props.accommodationClaim.id }), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Accommodation claim saved as draft successfully!'
                : 'Accommodation claim updated successfully!';
                
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
    form.removed_hotel_receipts = removedHotelReceipts.value;
    form.removed_supporting_documents = removedSupportingDocuments.value;
    
    form.put(route('user.accommodation-claims.update', { accommodationClaim: props.accommodationClaim.id }), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Accommodation claim saved as draft successfully!',
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
    if (!form.hotel_name.trim()) return false;
    if (!form.check_in_date) return false;
    if (!form.check_out_date) return false;
    if (form.number_of_nights <= 0) return false;
    if (form.rate_per_night <= 0) return false;
    if (!form.currency) return false;
    if (!form.purpose.trim()) return false;
    if (!form.destination_city.trim()) return false;
    if (!form.destination_country.trim()) return false;

    // Check if check-out date is after check-in date
    if (form.check_out_date <= form.check_in_date) return false;

    return true;
};

// Format date for display
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-MY');
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
        USD: '$',
        SGD: 'S$'
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

// View claim details
const viewClaim = () => {
    router.get(route('user.accommodation-claims.show', { accommodationClaim: props.accommodationClaim.id }));
};

// Get max rate for current currency
const maxRate = computed(() => {
    return props.defaultRates[form.currency]?.max_rate || 300;
});

// Check if rate exceeds maximum
const isRateExceeded = computed(() => {
    return form.rate_per_night > maxRate.value;
});

// Initialize calculations
onMounted(() => {
    calculateAmounts();
});
</script>

<template>
    <Head :title="`Edit Accommodation Claim #${accommodationClaim.id}`" />
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
                                Penginapan pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(total_amount, currency) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(total_amount, currency) }}</p>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Edit Accommodation Claim #{{ accommodationClaim.id }}</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Update your accommodation expense claim</p>
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
                    <!-- Hotel Information -->
                    <Card class="shadow-lg">
                        <template #title>Hotel Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Hotel Name *</label>
                                    <InputText v-model="form.hotel_name" placeholder="e.g., Grand Hyatt Kuala Lumpur"
                                        class="w-full" :class="{ 'p-invalid': form.errors.hotel_name }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.hotel_name">{{ form.errors.hotel_name }}</small>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Check-in Date *</label>
                                        <DatePicker v-model="form.check_in_date" dateFormat="yy-mm-dd" showIcon
                                            class="w-full" :class="{ 'p-invalid': form.errors.check_in_date }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.check_in_date">{{ form.errors.check_in_date }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Check-out Date *</label>
                                        <DatePicker v-model="form.check_out_date" dateFormat="yy-mm-dd" showIcon
                                            class="w-full" :class="{ 'p-invalid': form.errors.check_out_date }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.check_out_date">{{ form.errors.check_out_date }}</small>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Number of Nights *</label>
                                        <InputNumber v-model="form.number_of_nights" mode="decimal" :min="1" :max="30"
                                            placeholder="Auto-calculated" class="w-full"
                                            :class="{ 'p-invalid': form.errors.number_of_nights }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.number_of_nights">{{ form.errors.number_of_nights }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Rate per Night *</label>
                                        <div class="flex items-center gap-2">
                                            <InputNumber v-model="form.rate_per_night" mode="decimal" :min="0" :max="1000"
                                                :fractionDigits="2" class="w-full flex-1"
                                                :class="{ 'p-invalid': form.errors.rate_per_night || isRateExceeded }" />
                                            <span class="text-sm text-gray-500 whitespace-nowrap">
                                                {{ getCurrencySymbol(form.currency) }}
                                            </span>
                                        </div>
                                        <small class="text-red-500 text-xs" v-if="form.errors.rate_per_night">{{ form.errors.rate_per_night }}</small>
                                        <small v-if="isRateExceeded" class="text-orange-600 text-xs">
                                            Rate exceeds maximum allowed rate of {{ formatCurrency(maxRate, form.currency) }}
                                        </small>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Currency *</label>
                                    <!-- FIX: Use formattedCurrencies instead of raw currencies -->
                                    <Select v-model="form.currency" :options="formattedCurrencies" optionLabel="label" optionValue="value"
                                        placeholder="Select Currency" class="w-full"
                                        :class="{ 'p-invalid': form.errors.currency }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.currency">{{ form.errors.currency }}</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Destination Information -->
                    <Card class="shadow-lg">
                        <template #title>Destination Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">City *</label>
                                    <InputText v-model="form.destination_city" placeholder="e.g., Kuala Lumpur"
                                        class="w-full" :class="{ 'p-invalid': form.errors.destination_city }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.destination_city">{{ form.errors.destination_city }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Country *</label>
                                    <InputText v-model="form.destination_country" placeholder="e.g., Malaysia"
                                        class="w-full" :class="{ 'p-invalid': form.errors.destination_country }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.destination_country">{{ form.errors.destination_country }}</small>
                                </div>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-sm font-medium text-gray-700">Purpose of Stay *</label>
                                <Textarea v-model="form.purpose" rows="3" placeholder="Describe the purpose of your stay..."
                                    class="w-full" :class="{ 'p-invalid': form.errors.purpose }" />
                                <small class="text-red-500 text-xs" v-if="form.errors.purpose">{{ form.errors.purpose }}</small>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                <Textarea v-model="form.remarks" rows="2" placeholder="Additional notes or remarks..."
                                    class="w-full" />
                                <small class="text-gray-500 text-xs">Optional remarks about your accommodation</small>
                            </div>
                        </template>
                    </Card>

                    <!-- Claim Calculation -->
                    <Card class="shadow-lg">
                        <template #title>Claim Calculation</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Tax Percentage (%)</label>
                                        <InputNumber v-model="form.tax_percentage" mode="decimal" :min="0" :max="100"
                                            :fractionDigits="2" class="w-full"
                                            :class="{ 'p-invalid': form.errors.tax_percentage }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.tax_percentage">{{ form.errors.tax_percentage }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Service Charge (%)</label>
                                        <InputNumber v-model="form.service_charge_percentage" mode="decimal" :min="0" :max="100"
                                            :fractionDigits="2" class="w-full"
                                            :class="{ 'p-invalid': form.errors.service_charge_percentage }" />
                                        <small class="text-red-500 text-xs" v-if="form.errors.service_charge_percentage">{{ form.errors.service_charge_percentage }}</small>
                                    </div>
                                </div>

                                <!-- Amount Breakdown -->
                                <div class="mt-4 space-y-2 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Subtotal ({{ form.number_of_nights }} nights × {{ formatCurrency(form.rate_per_night, form.currency) }}):</span>
                                        <span class="font-medium">{{ formatCurrency(form.subtotal_amount, form.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Tax ({{ form.tax_percentage }}%):</span>
                                        <span class="font-medium">{{ formatCurrency(form.tax_amount, form.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Service Charge ({{ form.service_charge_percentage }}%):</span>
                                        <span class="font-medium">{{ formatCurrency(form.service_charge_amount, form.currency) }}</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="flex justify-between text-lg font-semibold">
                                        <span class="text-gray-800">Total Amount:</span>
                                        <span class="text-blue-600">{{ formatCurrency(form.total_amount, form.currency) }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card class="shadow-lg">
                        <template #title>Attachments</template>
                        <template #content>
                            <div class="space-y-6">
                                <!-- Hotel Receipts -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Hotel Receipts</h4>
                                    
                                    <!-- Existing Hotel Receipts -->
                                    <div v-if="accommodationClaim.hotel_receipts && accommodationClaim.hotel_receipts.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Existing Receipts:</h5>
                                        <div v-for="(doc, index) in accommodationClaim.hotel_receipts" :key="index" 
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
                                                    @click="removeExistingHotelReceipt(index, doc.name)"
                                                    v-tooltip="'Remove receipt'" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Additional Hotel Receipts</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onHotelReceiptsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- New Hotel Receipts List -->
                                    <div v-if="form.hotel_receipts.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">New Receipts to Upload:</h5>
                                        <div v-for="(file, index) in form.hotel_receipts" :key="index" 
                                             class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <span class="text-sm text-gray-600">{{ file.name }}</span>
                                            <Button icon="pi pi-times" severity="danger" text rounded
                                                @click="removeHotelReceipt(index)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Supporting Documents</h4>
                                    
                                    <!-- Existing Supporting Documents -->
                                    <div v-if="accommodationClaim.supporting_documents && accommodationClaim.supporting_documents.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-medium text-gray-600">Existing Documents:</h5>
                                        <div v-for="(doc, index) in accommodationClaim.supporting_documents" :key="index" 
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
                                    <span class="text-sm font-medium">#{{ accommodationClaim.id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Hotel:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ form.hotel_name || '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Check-in:</span>
                                    <span class="text-sm font-medium">{{ formatDate(form.check_in_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Check-out:</span>
                                    <span class="text-sm font-medium">{{ formatDate(form.check_out_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Nights:</span>
                                    <span class="text-sm font-medium">{{ form.number_of_nights }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate/Night:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(form.rate_per_night, form.currency) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ form.currency }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Destination:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ form.destination_city }}, {{ form.destination_country }}
                                    </span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(form.total_amount, form.currency) }}</span>
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

                                <!-- Rate Warning -->
                                <div v-if="isRateExceeded" class="p-2 bg-orange-50 border border-orange-200 rounded">
                                    <p class="text-xs text-orange-700 text-center">
                                        ⚠️ Rate exceeds maximum allowed
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
                                    <li>Update hotel information and dates as needed</li>
                                    <li>Number of nights is auto-calculated from dates</li>
                                    <li>Tax and service charge rates are set by currency</li>
                                    <li>You can add or remove receipts and documents</li>
                                    <li>Submit for approval when all changes are complete</li>
                                    <li>Save as draft to continue editing later</li>
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