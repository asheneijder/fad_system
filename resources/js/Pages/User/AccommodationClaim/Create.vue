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
    currencies: {
        type: Object,  
        default: () => ({}) 
    },
    defaultRates: {
        type: Object,
        default: () => ({})
    },
    userJobTitle: {
        type: String,
        default: ''
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Accommodation Claim' }
];

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);
const showRateWarning = ref(false);

const form = useForm({
    hotel_name: '',
    check_in_date: null,
    check_out_date: null,
    number_of_nights: 0,
    rate_per_night: 0,
    currency: 'MYR',
    tax_percentage: 6,
    service_charge_percentage: 10,
    tax_amount: 0,
    service_charge_amount: 0,
    subtotal_amount: 0,
    total_amount: 0,
    purpose: '',
    destination_city: '',
    destination_country: '',
    remarks: '',
    hotel_receipts: [],
    supporting_documents: [],
    save_as_draft: false,
});

// Check if rate exceeds maximum
const checkRateLimit = () => {
    const currentMaxRate = props.defaultRates[form.currency]?.max_rate;
    if (currentMaxRate && form.rate_per_night > currentMaxRate) {
        showRateWarning.value = true;
    } else {
        showRateWarning.value = false;
    }
};

// Watch currency to update default rates
watch(() => form.currency, (newCurrency) => {
    const defaultRate = props.defaultRates[newCurrency];
    if (defaultRate) {
        form.tax_percentage = defaultRate.tax_percentage;
        form.service_charge_percentage = defaultRate.service_charge_percentage;
        
        // Set default rate per night if not set
        if (!form.rate_per_night || form.rate_per_night === 0) {
            form.rate_per_night = defaultRate.max_rate;
        }
        
        // Check rate limit again when currency changes
        checkRateLimit();
    }
});

// Calculate number of nights
const numberOfNights = computed(() => {
    if (!form.check_in_date || !form.check_out_date) return 0;
    
    const checkIn = new Date(form.check_in_date);
    const checkOut = new Date(form.check_out_date);
    const diffTime = checkOut.getTime() - checkIn.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

// Update number of nights in form
watch(numberOfNights, (newNights) => {
    form.number_of_nights = newNights;
});

// Watch rate_per_night to check limit
watch(() => form.rate_per_night, () => {
    checkRateLimit();
});



// Calculate all amounts
const subtotalAmount = computed(() => {
    const rate = Number(form.rate_per_night) || 0;
    return rate * numberOfNights.value;
});

const taxAmount = computed(() => {
    const taxPercent = Number(form.tax_percentage) || 0;
    return (subtotalAmount.value * taxPercent) / 100;
});

const serviceChargeAmount = computed(() => {
    const servicePercent = Number(form.service_charge_percentage) || 0;
    return (subtotalAmount.value * servicePercent) / 100;
});

const totalAmount = computed(() => {
    return subtotalAmount.value + taxAmount.value + serviceChargeAmount.value;
});

// Update form amounts when calculations change
watch([subtotalAmount, taxAmount, serviceChargeAmount, totalAmount], () => {
    form.subtotal_amount = parseFloat(subtotalAmount.value.toFixed(2));
    form.tax_amount = parseFloat(taxAmount.value.toFixed(2));
    form.service_charge_amount = parseFloat(serviceChargeAmount.value.toFixed(2));
    form.total_amount = parseFloat(totalAmount.value.toFixed(2));
});

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

// Remove file from hotel receipts
const removeHotelReceipt = (index) => {
    form.hotel_receipts.splice(index, 1);
};

// Remove file from supporting documents
const removeSupportingDocument = (index) => {
    form.supporting_documents.splice(index, 1);
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
    form.save_as_draft = saveAsDraft;
    showAcknowledgmentDialog.value = false;
    
    form.post(route('user.accommodation-claims.store'), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Accommodation claim saved as draft successfully!'
                : 'Accommodation claim submitted successfully!';
                
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

// Validate form before submission
const validateForm = () => {
    // Check hotel name
    if (!form.hotel_name.trim()) {
        return false;
    }

    // Check dates
    if (!form.check_in_date || !form.check_out_date) {
        return false;
    }

    // Check if check-out is after check-in
    if (form.check_in_date && form.check_out_date) {
        const checkIn = new Date(form.check_in_date);
        const checkOut = new Date(form.check_out_date);
        if (checkOut <= checkIn) {
            return false;
        }
    }

    // Check rate
    if (!form.rate_per_night || form.rate_per_night <= 0) {
        return false;
    }

    // Check purpose
    if (!form.purpose.trim()) {
        return false;
    }

    // Check destination
    if (!form.destination_city.trim() || !form.destination_country.trim()) {
        return false;
    }

    return true;
};

// Format currency for display
const formatCurrency = (amount) => {
    const currencySymbols = {
        MYR: 'RM',
        USD: '$',
        SGD: 'S$'
    };
    
    const symbol = currencySymbols[form.currency] || '';
    const numericAmount = Number(amount) || 0;
    return `${symbol}${numericAmount.toFixed(2)}`;
};

// Current date for declaration
const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Check if form is valid
const isFormValid = computed(() => {
    return validateForm();
});

// Get stay period for display
const stayPeriod = computed(() => {
    if (!form.check_in_date || !form.check_out_date) return '—';
    
    const checkIn = new Date(form.check_in_date);
    const checkOut = new Date(form.check_out_date);
    
    return `${checkIn.toLocaleDateString('en-MY')} - ${checkOut.toLocaleDateString('en-MY')}`;
});

// Get currency options for select
const currencyOptions = computed(() => {
    return Object.entries(props.currencies || {}).map(([value, label]) => ({
        value,
        label
    }));
});
</script>

<template>
    <Head title="Create Accommodation Claim" />
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
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(totalAmount) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(totalAmount) }}</p>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Create Accommodation Claim</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Submit your hotel and lodging expenses</p>
                </div>
                <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                    @click="router.get(route('user.request-claim.index'))"
                    class="responsive-button" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Hotel Information -->
                    <Card class="shadow-lg">
                        <template #title>Hotel Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Hotel Name *</label>
                                    <InputText v-model="form.hotel_name" placeholder="Enter hotel name"
                                        class="w-full" :class="{ 'p-invalid': form.errors.hotel_name }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.hotel_name">{{ form.errors.hotel_name }}</small>
                                </div>

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
                        </template>
                    </Card>

                    <!-- Cost Details -->
                    <Card class="shadow-lg">
                        <template #title>Cost Details</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Currency *</label>
                                    <Select v-model="form.currency" :options="currencyOptions" optionLabel="label" optionValue="value"
                                        placeholder="Select Currency" class="w-full"
                                        :class="{ 'p-invalid': form.errors.currency }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.currency">{{ form.errors.currency }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Rate per Night *</label>
                                    <div class="relative">
                                        <InputNumber v-model="form.rate_per_night" mode="decimal" :min="0"
                                            placeholder="0.00" class="w-full"
                                            :class="{ 'p-invalid': form.errors.rate_per_night, 'p-invalid border-yellow-500': showRateWarning }" 
                                            @input="checkRateLimit" />
                                    </div>
                                    <small class="text-red-500 text-xs" v-if="form.errors.rate_per_night">
                                        {{ form.errors.rate_per_night }}
                                    </small>
                                    <small class="text-yellow-600 text-xs font-medium" v-if="showRateWarning">
                                        ⚠️ Your rate exceeds the recommended maximum for your job title
                                    </small>
                                    <small class="text-gray-500 text-xs">
                                        Max recommended: {{ formatCurrency(defaultRates[form.currency]?.max_rate) }}
                                        <span class="text-blue-600 ml-1">(Based on your job title: {{ userJobTitle }})</span>
                                    </small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Tax Percentage *</label>
                                    <div class="relative">
                                        <InputNumber v-model="form.tax_percentage" mode="decimal" :min="0" :max="100"
                                            placeholder="0.00" class="w-full"
                                            :class="{ 'p-invalid': form.errors.tax_percentage }" />
                                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                                    </div>
                                    <small class="text-red-500 text-xs" v-if="form.errors.tax_percentage">{{ form.errors.tax_percentage }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Service Charge Percentage *</label>
                                    <div class="relative">
                                        <InputNumber v-model="form.service_charge_percentage" mode="decimal" :min="0" :max="100"
                                            placeholder="0.00" class="w-full"
                                            :class="{ 'p-invalid': form.errors.service_charge_percentage }" />
                                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                                    </div>
                                    <small class="text-red-500 text-xs" v-if="form.errors.service_charge_percentage">{{ form.errors.service_charge_percentage }}</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Details -->
                    <Card class="shadow-lg">
                        <template #title>Travel Details</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Destination City *</label>
                                    <InputText v-model="form.destination_city" placeholder="Enter city"
                                        class="w-full" :class="{ 'p-invalid': form.errors.destination_city }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.destination_city">{{ form.errors.destination_city }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Destination Country *</label>
                                    <InputText v-model="form.destination_country" placeholder="Enter country"
                                        class="w-full" :class="{ 'p-invalid': form.errors.destination_country }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.destination_country">{{ form.errors.destination_country }}</small>
                                </div>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-sm font-medium text-gray-700">Purpose of Travel *</label>
                                <Textarea v-model="form.purpose" rows="3" placeholder="Describe the purpose of your travel..."
                                    class="w-full" :class="{ 'p-invalid': form.errors.purpose }" />
                                <small class="text-red-500 text-xs" v-if="form.errors.purpose">{{ form.errors.purpose }}</small>
                                <small class="text-gray-500 text-xs">
                                    {{ form.purpose.length }}/500 characters
                                </small>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-sm font-medium text-gray-700">Additional Remarks</label>
                                <Textarea v-model="form.remarks" rows="2" placeholder="Any additional information..."
                                    class="w-full" :class="{ 'p-invalid': form.errors.remarks }" />
                                <small class="text-red-500 text-xs" v-if="form.errors.remarks">{{ form.errors.remarks }}</small>
                                <small class="text-gray-500 text-xs">
                                    {{ form.remarks.length }}/1000 characters
                                </small>
                            </div>
                        </template>
                    </Card>

                    <!-- Claim Calculation -->
                    <Card class="shadow-lg">
                        <template #title>Claim Calculation</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Number of Nights</label>
                                    <InputNumber v-model="form.number_of_nights" mode="decimal" :min="0"
                                        placeholder="Auto-calculated" class="w-full"
                                        :class="{ 'p-invalid': form.errors.number_of_nights }"
                                        disabled />
                                    <small class="text-gray-500 text-xs">
                                        Calculated from check-in/check-out dates
                                    </small>
                                    <small class="text-red-500 text-xs" v-if="form.errors.number_of_nights">{{ form.errors.number_of_nights }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Subtotal Amount</label>
                                    <InputNumber v-model="form.subtotal_amount" mode="decimal" :min="0"
                                        placeholder="Auto-calculated" class="w-full"
                                        :class="{ 'p-invalid': form.errors.subtotal_amount }"
                                        disabled />
                                    <small class="text-red-500 text-xs" v-if="form.errors.subtotal_amount">{{ form.errors.subtotal_amount }}</small>
                                </div>
                            </div>

                            <!-- Detailed Cost Breakdown -->
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Subtotal:</span>
                                        <span class="text-sm font-medium">{{ formatCurrency(subtotalAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Tax ({{ form.tax_percentage }}%):</span>
                                        <span class="text-sm font-medium">{{ formatCurrency(taxAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Service Charge ({{ form.service_charge_percentage }}%):</span>
                                        <span class="text-sm font-medium">{{ formatCurrency(serviceChargeAmount) }}</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-gray-800">Total Claim Amount:</span>
                                        <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mt-2">
                                    Calculation: {{ numberOfNights }} nights × {{ formatCurrency(form.rate_per_night) }} + Taxes & Charges
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
                                    <h4 class="font-medium text-gray-700">Hotel Receipts</h4>
                                    
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Hotel Receipts</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onHotelReceiptsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- File List -->
                                    <div v-if="form.hotel_receipts.length > 0" class="space-y-2">
                                        <h4 class="text-sm font-medium text-gray-700">Selected Hotel Receipts:</h4>
                                        <div v-for="(file, index) in form.hotel_receipts" :key="index" 
                                             class="flex items-center justify-between p-2 bg-blue-50 rounded">
                                            <span class="text-sm text-gray-600">{{ file.name }}</span>
                                            <Button icon="pi pi-times" severity="danger" text rounded
                                                @click="removeHotelReceipt(index)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div class="space-y-4">
                                    <h4 class="font-medium text-gray-700">Other Supporting Documents</h4>
                                    
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Upload Supporting Documents</label>
                                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onSupportingDocumentsSelect"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
                                        <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                    </div>

                                    <!-- File List -->
                                    <div v-if="form.supporting_documents.length > 0" class="space-y-2">
                                        <h4 class="text-sm font-medium text-gray-700">Selected Supporting Documents:</h4>
                                        <div v-for="(file, index) in form.supporting_documents" :key="index" 
                                             class="flex items-center justify-between p-2 bg-green-50 rounded">
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
                                    <span class="text-sm text-gray-600">Hotel:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ form.hotel_name || '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Stay Period:</span>
                                    <span class="text-sm font-medium">{{ stayPeriod }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Nights:</span>
                                    <span class="text-sm font-medium">{{ numberOfNights }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate per Night:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(form.rate_per_night) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ form.currency }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Destination:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ form.destination_city || '—' }}, {{ form.destination_country || '—' }}
                                    </span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                                </div>
                                
                                <!-- Validation Status -->
                                <div v-if="!isFormValid" class="p-2 bg-yellow-50 border border-yellow-200 rounded">
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
                                    @click="openAcknowledgmentDialog" :loading="form.processing" :disabled="!isFormValid"
                                    class="w-full responsive-button" />
                                
                                <Button label="Save as Draft" icon="pi pi-save" severity="secondary" outlined
                                    @click="submitFormWithAgreement(true)" :loading="form.processing"
                                    class="w-full responsive-button" />
                                
                                <Button label="Cancel" icon="pi pi-times" severity="danger" text
                                    @click="router.get(route('user.request-claim.index'))"
                                    class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>

                    <!-- Help Tips -->
                    <Card class="shadow-lg border-l-4 border-purple-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Tips for Accommodation Claims:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li>Ensure check-out date is after check-in date</li>
                                    <li>Keep all hotel receipts and invoices</li>
                                    <li>Include tax and service charge details</li>
                                    <li>Take photos of receipts as backup</li>
                                    <li>Submit within 30 days of your stay</li>
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