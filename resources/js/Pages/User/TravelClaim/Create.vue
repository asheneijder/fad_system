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
    defaultRates: {
        type: Object,
        default: () => ({})
    },
    vehicleTypes: {
        type: Array,
        default: () => []
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Travel Claim' }
];

// Multiple travel legs for complex itineraries
const travelLegs = ref([
    { from: '', to: '', date: null, distance: null }
]);

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);

// Add new travel leg
const addTravelLeg = () => {
    travelLegs.value.push({ from: '', to: '', date: null, distance: null });
};

// Remove travel leg
const removeTravelLeg = (index) => {
    if (travelLegs.value.length > 1) {
        travelLegs.value.splice(index, 1);
    }
};

// Calculate total distance from all legs
const totalDistance = computed(() => {
    return travelLegs.value.reduce((sum, leg) => sum + (parseFloat(leg.distance) || 0), 0);
});

const form = useForm({
    vehicle_type: 'car',
    registration_plate_number: '',
    cubic_capacity: null,
    is_multiple_legs: false,
    travel_legs: [],
    purpose: '',
    total_distance: 0,
    rate_per_km: props.defaultRates.car || 0.60,
    attachments: [],
    save_as_draft: false,
});

// Watch vehicle type to update default rate
watch(() => form.vehicle_type, (newVehicleType) => {
    form.rate_per_km = props.defaultRates[newVehicleType] || 0.50;
});

// Update total distance when legs change
watch(totalDistance, (newTotal) => {
    form.total_distance = parseFloat(newTotal.toFixed(2));
});

// Update travel legs in form data
watch(travelLegs, (newLegs) => {
    form.travel_legs = newLegs.map(leg => ({
        ...leg,
        date: leg.date ? new Date(leg.date).toISOString().split('T')[0] : null,
        distance: parseFloat(leg.distance) || 0
    }));
}, { deep: true });

// Calculate total cost
const totalCost = computed(() => {
    if (form.total_distance && form.rate_per_km) {
        return form.total_distance * form.rate_per_km;
    }
    return 0;
});

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
    const buffer = 5; // 5px buffer
    const isAtBottom = element.scrollTop + element.clientHeight >= element.scrollHeight - buffer;
    
    if (isAtBottom) {
        hasScrolledToBottom.value = true;
    }
};

// Submit form after agreement
const submitFormWithAgreement = (saveAsDraft = false) => {
    // if (!agreedToTerms.value) {
    //     toast.add({
    //         severity: 'warn',
    //         summary: 'Acknowledgement Required',
    //         detail: 'Please agree to the terms before submitting',
    //         life: 3000
    //     });
    //     return;
    // }

    form.save_as_draft = saveAsDraft;
    showAcknowledgmentDialog.value = false;
    
    form.post(route('user.travel-claims.store'), {
        onSuccess: () => {
            const message = saveAsDraft 
                ? 'Travel claim saved as draft successfully!'
                : 'Travel claim submitted successfully!';
                
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
    // Check vehicle type
    if (!form.vehicle_type) {
        return false;
    }

    // Check travel legs
    if (travelLegs.value.length === 0) {
        return false;
    }

    for (const leg of travelLegs.value) {
        if (!leg.from || !leg.to || !leg.date || !leg.distance) {
            return false;
        }
    }

    // Check purpose
    if (!form.purpose.trim()) {
        return false;
    }

    // Check total distance
    if (form.total_distance <= 0) {
        return false;
    }

    return true;
};

// Get rate display text based on vehicle type
const getRateDisplayText = computed(() => {
    const rates = {
        car: '0.60 RM/km (Car)',
        motorcycle: '0.40 RM/km (Motorcycle)',
        bicycle: '0.20 RM/km (Bicycle)',
        other: '0.50 RM/km (Other Vehicle)'
    };
    return rates[form.vehicle_type] || '0.50 RM/km';
});

// Get route summary for display
const routeSummary = computed(() => {
    if (travelLegs.value.length === 1) {
        return `${travelLegs.value[0].from || '—'} → ${travelLegs.value[0].to || '—'}`;
    } else {
        const locations = travelLegs.value.map(leg => leg.from).filter(Boolean);
        const lastDestination = travelLegs.value[travelLegs.value.length - 1].to;
        if (lastDestination) locations.push(lastDestination);
        return locations.join(' → ');
    }
});

// Format date for display
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
    }).format(amount || 0);
};

// Check if a leg is valid
const isLegValid = (leg) => {
    return leg.from && leg.to && leg.date && leg.distance;
};

// Check if all legs are valid
const allLegsValid = computed(() => {
    return travelLegs.value.every(isLegValid);
});

// Current date for declaration
const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});
</script>

<template>
    <Head title="Create Travel Claim" />
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
                                Perjalanan pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(totalCost) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(totalCost) }}</p>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Create Travel Claim</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Submit your travel expense claim</p>
                </div>
                <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                    @click="router.get(route('user.request-claim.index'))"
                    class="responsive-button" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Vehicle Information -->
                    <Card class="shadow-lg">
                        <template #title>Vehicle Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Type *</label>
                                    <Select v-model="form.vehicle_type" :options="vehicleTypes" optionLabel="label" optionValue="value"
                                        placeholder="Select Vehicle Type" class="w-full"
                                        :class="{ 'p-invalid': form.errors.vehicle_type }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.vehicle_type">{{ form.errors.vehicle_type }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Registration Plate</label>
                                    <InputText v-model="form.registration_plate_number" placeholder="e.g., ABC1234"
                                        class="w-full" />
                                    <small class="text-red-500 text-xs uppercase" v-if="form.errors.registration_plate_number">{{ form.errors.registration_plate_number }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Cubic Capacity (CC)</label>
                                    <InputNumber v-model="form.cubic_capacity" mode="decimal" :min="0"
                                        placeholder="e.g., 1500" class="w-full" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.cubic_capacity">{{ form.errors.cubic_capacity }}</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Details -->
                    <Card class="shadow-lg">
                        <template #title>Travel Details</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="form.is_multiple_legs" inputId="multiple_legs" :binary="true" />
                                    <label for="multiple_legs" class="text-sm font-medium text-gray-700">Multiple Destinations / Round Trip</label>
                                </div>

                                <!-- Travel Legs -->
                                <div class="space-y-4">
                                    <div v-for="(leg, index) in travelLegs" :key="index" 
                                         class="border border-gray-200 rounded-lg p-4 space-y-4"
                                         :class="isLegValid(leg) ? 'bg-green-50 border-green-200' : 'bg-gray-50'">
                                        <div class="flex justify-between items-center">
                                            <h4 class="font-medium text-gray-800">Leg {{ index + 1 }}</h4>
                                            <Button v-if="travelLegs.length > 1" icon="pi pi-times" severity="danger" text rounded
                                                @click="removeTravelLeg(index)"
                                                class="responsive-icon-button" 
                                                v-tooltip="'Remove this leg'" />
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700">From Location *</label>
                                                <InputText v-model="leg.from" :placeholder="`e.g., ${index === 0 ? 'Kuala Lumpur' : 'Previous Location'}`"
                                                    class="w-full" :class="{ 'p-invalid': !leg.from && form.errors.travel_legs }" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700">To Location *</label>
                                                <InputText v-model="leg.to" placeholder="e.g., Next Destination"
                                                    class="w-full" :class="{ 'p-invalid': !leg.to && form.errors.travel_legs }" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700">Travel Date *</label>
                                                <DatePicker v-model="leg.date" dateFormat="yy-mm-dd" showIcon
                                                    class="w-full" :class="{ 'p-invalid': !leg.date && form.errors.travel_legs }" />
                                            </div>
                                        </div>
                                       <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">Distance (KM) *</label>
                                            <InputNumber 
                                                v-model="leg.distance" 
                                                mode="decimal" 
                                                :min="0" 
                                                :max="9999"
                                                :minFractionDigits="1"
                                                :maxFractionDigits="2"
                                                inputmode="decimal"
                                                placeholder="e.g., 150.5" 
                                                class="w-full" 
                                                :class="{ 'p-invalid': !leg.distance && form.errors.travel_legs }" 
                                            />
                                        </div>
                                    </div>

                                    <Button label="Add Another Leg" icon="pi pi-plus" severity="secondary" outlined
                                        @click="addTravelLeg"
                                        class="responsive-button" />
                                    
                                    <small class="text-red-500 text-xs block" v-if="form.errors.travel_legs">
                                        {{ form.errors.travel_legs }}
                                    </small>
                                    <small class="text-gray-500 text-xs block">
                                        Use multiple legs for round trips or multi-destination journeys
                                    </small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Purpose of Travel *</label>
                                    <Textarea v-model="form.purpose" rows="3" placeholder="Describe the purpose of your travel..."
                                        class="w-full" :class="{ 'p-invalid': form.errors.purpose }" />
                                    <small class="text-red-500 text-xs" v-if="form.errors.purpose">{{ form.errors.purpose }}</small>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Claim Calculation -->
                    <Card class="shadow-lg">
                        <template #title>Claim Calculation</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Total Distance (KM) *</label>
                                    <InputNumber v-model="form.total_distance" mode="decimal" :min="0" :max="9999"
                                        placeholder="Auto-calculated" class="w-full"
                                        :class="{ 'p-invalid': form.errors.total_distance }"
                                        disabled />
                                    <small class="text-gray-500 text-xs">
                                        Total from all legs: {{ totalDistance.toFixed(2) }} km
                                    </small>
                                    <small class="text-red-500 text-xs" v-if="form.errors.total_distance">{{ form.errors.total_distance }}</small>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Rate per KM (RM) *</label>
                                    <div class="flex items-center gap-2">
                                        <InputNumber v-model="form.rate_per_km" mode="decimal" :min="0" :max="10"
                                            :fractionDigits="2" class="w-full"
                                            :class="{ 'p-invalid': form.errors.rate_per_km }"
                                            disabled />
                                        <span class="text-sm text-gray-500 whitespace-nowrap">
                                            {{ getRateDisplayText }}
                                        </span>
                                    </div>
                                    <small class="text-gray-500 text-xs">
                                        Rate is automatically set based on vehicle type
                                    </small>
                                    <small class="text-red-500 text-xs" v-if="form.errors.rate_per_km">{{ form.errors.rate_per_km }}</small>
                                </div>
                            </div>

                            <!-- Total Cost Display -->
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-gray-800">Total Claim Amount:</span>
                                    <span class="text-2xl font-bold text-blue-600">{{ totalCost.toFixed(2) }} MYR</span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    Calculated as: {{ form.total_distance || 0 }} km × {{ form.rate_per_km }} RM/km
                                </div>
                                <div v-if="form.is_multiple_legs && travelLegs.length > 1" class="text-sm text-gray-600 mt-1">
                                    {{ travelLegs.length }} legs combined
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card class="shadow-lg">
                        <template #title>Attachments</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Upload Receipts & Supporting Documents</label>
                                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="onFileSelect"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    <small class="text-gray-500 text-xs">Supported formats: JPG, PNG, PDF. Max file size: 10MB each.</small>
                                </div>

                                <!-- File List -->
                                <div v-if="form.attachments.length > 0" class="space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700">Selected Files:</h4>
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
                                    <span class="text-sm text-gray-600">Vehicle Type:</span>
                                    <span class="text-sm font-medium">{{ form.vehicle_type ? vehicleTypes.find(v => v.value === form.vehicle_type)?.label : '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trip Type:</span>
                                    <span class="text-sm font-medium">{{ form.is_multiple_legs ? 'Multiple Legs' : 'Single Trip' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Route:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ routeSummary }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Distance:</span>
                                    <span class="text-sm font-medium">{{ form.total_distance || 0 }} km</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate:</span>
                                    <span class="text-sm font-medium">{{ form.rate_per_km }} RM/km</span>
                                </div>
                                <div v-if="form.is_multiple_legs" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Number of Legs:</span>
                                    <span class="text-sm font-medium">{{ travelLegs.length }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ totalCost.toFixed(2) }} MYR</span>
                                </div>
                                
                                <!-- Validation Status -->
                                <div v-if="!allLegsValid" class="p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="text-xs text-yellow-700 text-center">
                                        ⚠️ Please complete all travel legs
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
                                    @click="openAcknowledgmentDialog" :loading="form.processing" :disabled="!allLegsValid || !form.purpose"
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
                    <Card class="shadow-lg border-l-4 border-blue-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Tips for Travel Claims:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li>Use multiple legs for round trips or complex itineraries</li>
                                    <li>Ensure your odometer reading or GPS distance is accurate</li>
                                    <li>Keep all fuel and toll receipts</li>
                                    <li>Take photos of your vehicle's odometer if possible</li>
                                    <li>Submit within 30 days of travel</li>
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