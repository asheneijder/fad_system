<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import Toast from "primevue/toast";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const toast = useToast();

const props = defineProps({
    transportationClaim: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Transportation Claim #' + props.transportationClaim.id }
];

// Declaration dialog state
const showAcknowledgmentDialog = ref(false);
const agreedToTerms = ref(false);
const hasScrolledToBottom = ref(false);
const loading = ref(false);

// Status severity for tags
const statusSeverity = computed(() => {
    const status = props.transportationClaim.status;
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'info';
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'paid': return 'help';
        default: return 'info';
    }
});

// Check if claim is editable
const isEditable = computed(() => {
    return ['draft', 'pending'].includes(props.transportationClaim.status);
});

// Check if claim can be submitted
const canSubmit = computed(() => {
    return props.transportationClaim.status === 'draft';
});

// Format date for display
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Format date with time
const formatDateTime = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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

// Check if transport type is distance-based
const isDistanceBased = computed(() => {
    return !['toll', 'parking'].includes(props.transportationClaim.transport_type);
});

// Calculate base amount for display
const baseAmount = computed(() => {
    if (!isDistanceBased.value) return props.transportationClaim.amount;
    
    let base = props.transportationClaim.distance_km * props.transportationClaim.rate_per_km;
    
    // Remove trip type multiplier for display
    if (props.transportationClaim.trip_type === 'round_trip') {
        base /= 2;
    }
    
    // Remove number of trips multiplier for display
    if (props.transportationClaim.number_of_trips > 1) {
        base /= props.transportationClaim.number_of_trips;
    }
    
    return base;
});

// Current date for declaration
const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Handle scroll in acknowledgment dialog
const onAcknowledgmentScroll = (event) => {
    const element = event.target;
    const buffer = 5; // 5px buffer
    const isAtBottom = element.scrollTop + element.clientHeight >= element.scrollHeight - buffer;
    
    if (isAtBottom) {
        hasScrolledToBottom.value = true;
    }
};

// Open acknowledgment dialog
const openAcknowledgmentDialog = () => {
    showAcknowledgmentDialog.value = true;
    agreedToTerms.value = false;
    hasScrolledToBottom.value = false;
};

// Submit claim for approval after agreement
const submitForApproval = () => {
    if (!agreedToTerms.value) {
        toast.add({
            severity: 'warn',
            summary: 'Acknowledgement Required',
            detail: 'Please agree to the terms before submitting',
            life: 3000
        });
        return;
    }

    loading.value = true;
    
    router.post(route('user.transportation-claims.submit', { 
        transportationClaim: props.transportationClaim.id 
    }), {
        onSuccess: () => {
            showAcknowledgmentDialog.value = false;
            loading.value = false;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Transportation claim submitted for approval successfully!',
                life: 5000
            });
        },
        onError: () => {
            loading.value = false;
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to submit transportation claim. Please try again.',
                life: 5000
            });
        }
    });
};

// Edit claim
const editClaim = () => {
    router.get(route('user.transportation-claims.edit', { 
        transportationClaim: props.transportationClaim.id 
    }));
};

// Delete claim
const deleteClaim = () => {
    if (confirm('Are you sure you want to delete this transportation claim? This action cannot be undone.')) {
        router.delete(route('user.transportation-claims.destroy', { 
            transportationClaim: props.transportationClaim.id 
        }), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Transportation claim deleted successfully!',
                    life: 5000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete transportation claim. Please try again.',
                    life: 5000
                });
            }
        });
    }
};

// Download document
const downloadDocument = (url, filename) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// View document in new tab
const viewDocument = (url) => {
    window.open(url, '_blank');
};
</script>

<template>
    <Head :title="`Transportation Claim #${transportationClaim.id}`" />
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
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(transportationClaim.amount, transportationClaim.currency) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(transportationClaim.amount, transportationClaim.currency) }}</p>
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
                    <Button label="Agree & Submit" severity="primary" 
                            @click="submitForApproval" 
                            :disabled="!agreedToTerms || !hasScrolledToBottom"
                            :loading="loading" />
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
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Transportation Claim #{{ transportationClaim.id }}</h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-500">View transportation expense claim details</p>
                    </div>
                    <Tag :value="transportationClaim.status_display" :severity="statusSeverity" class="text-sm font-medium" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="isEditable" label="Edit Claim" icon="pi pi-pencil" severity="warning" outlined
                        @click="editClaim"
                        class="responsive-button" />
                    
                    <Button v-if="canSubmit" label="Submit for Approval" icon="pi pi-send" severity="success"
                        @click="openAcknowledgmentDialog"
                        class="responsive-button" />
                    
                    <Button v-if="isEditable" label="Delete Claim" icon="pi pi-trash" severity="danger" text
                        @click="deleteClaim"
                        class="responsive-button" />
                    
                    <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.get(route('user.request-claim.index'))"
                        class="responsive-button" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transportation Details -->
                    <Card class="shadow-lg">
                        <template #title>Transportation Details</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(transportationClaim.claim_date) }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Transport Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.transport_type_display }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Purpose</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ transportationClaim.purpose }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">From Location</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.from_location }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">To Location</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.to_location }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Trip Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.trip_type_display }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Number of Trips</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.number_of_trips }}</p>
                                    </div>
                                </div>

                                <div v-if="transportationClaim.receipt_number" class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Receipt Number</label>
                                    <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.receipt_number }}</p>
                                </div>

                                <div v-if="transportationClaim.remarks" class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Remarks</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ transportationClaim.remarks }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Amount Details -->
                    <Card class="shadow-lg">
                        <template #title>Amount Details</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Currency</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.currency_display }}</p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Total Amount</label>
                                        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(transportationClaim.amount, transportationClaim.currency) }}</p>
                                    </div>
                                </div>

                                <!-- Distance-based Calculation Breakdown -->
                                <div v-if="isDistanceBased" class="border-t border-gray-200 pt-4">
                                    <h4 class="font-semibold text-gray-800 mb-3">Amount Calculation:</h4>
                                    <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="block text-sm font-medium text-gray-600">Distance</label>
                                                <p class="text-lg font-semibold text-gray-800">{{ transportationClaim.distance_km }} km</p>
                                            </div>
                                            
                                            <div class="space-y-1">
                                                <label class="block text-sm font-medium text-gray-600">Rate per KM</label>
                                                <p class="text-lg font-semibold text-gray-800">
                                                    {{ formatCurrency(transportationClaim.rate_per_km, transportationClaim.currency) }} / km
                                                </p>
                                            </div>
                                        </div>

                                        <Divider />

                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Base Amount:</span>
                                                <span class="font-semibold">{{ formatCurrency(baseAmount, transportationClaim.currency) }}</span>
                                            </div>
                                            
                                            <div v-if="transportationClaim.trip_type === 'round_trip'" class="flex justify-between items-center">
                                                <span class="text-gray-600">Round Trip Multiplier:</span>
                                                <span class="font-semibold">× 2</span>
                                            </div>
                                            
                                            <div v-if="transportationClaim.number_of_trips > 1" class="flex justify-between items-center">
                                                <span class="text-gray-600">Number of Trips:</span>
                                                <span class="font-semibold">× {{ transportationClaim.number_of_trips }}</span>
                                            </div>
                                            
                                            <Divider />
                                            
                                            <div class="flex justify-between items-center text-lg">
                                                <span class="font-semibold text-gray-800">Total Amount:</span>
                                                <span class="font-bold text-blue-600">{{ formatCurrency(transportationClaim.amount, transportationClaim.currency) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fixed Amount Note -->
                                <div v-else class="border-t border-gray-200 pt-4">
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <p class="text-sm text-blue-700">
                                            <i class="pi pi-info-circle mr-2"></i>
                                            This is a fixed amount claim for {{ transportationClaim.transport_type_display.toLowerCase() }}.
                                            The amount represents the actual expense incurred.
                                        </p>
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
                                <!-- Transport Receipts -->
                                <div class="space-y-3">
                                    <h4 class="text-sm font-medium text-gray-700">Transport Receipts</h4>
                                    
                                    <div v-if="transportationClaim.transport_receipts && transportationClaim.transport_receipts.length > 0" class="space-y-2">
                                        <div v-for="(doc, index) in transportationClaim.transport_receipts" :key="index" 
                                             class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center gap-3">
                                                <i class="pi pi-file text-gray-500 text-lg"></i>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">{{ doc.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ (doc.size / 1024).toFixed(2) }} KB</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-eye" severity="info" text rounded
                                                    @click="viewDocument(doc.url)"
                                                    v-tooltip="'View document'" />
                                                <Button icon="pi pi-download" severity="success" text rounded
                                                    @click="downloadDocument(doc.url, doc.name)"
                                                    v-tooltip="'Download document'" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="text-center py-4">
                                        <i class="pi pi-file-excel text-gray-300 text-4xl mb-2"></i>
                                        <p class="text-gray-500 text-sm">No transport receipts uploaded</p>
                                    </div>
                                </div>

                                <!-- Supporting Documents -->
                                <div class="space-y-3">
                                    <h4 class="text-sm font-medium text-gray-700">Supporting Documents</h4>
                                    
                                    <div v-if="transportationClaim.supporting_documents && transportationClaim.supporting_documents.length > 0" class="space-y-2">
                                        <div v-for="(doc, index) in transportationClaim.supporting_documents" :key="index" 
                                             class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center gap-3">
                                                <i class="pi pi-file text-gray-500 text-lg"></i>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">{{ doc.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ (doc.size / 1024).toFixed(2) }} KB</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-eye" severity="info" text rounded
                                                    @click="viewDocument(doc.url)"
                                                    v-tooltip="'View document'" />
                                                <Button icon="pi pi-download" severity="success" text rounded
                                                    @click="downloadDocument(doc.url, doc.name)"
                                                    v-tooltip="'Download document'" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="text-center py-4">
                                        <i class="pi pi-file-excel text-gray-300 text-4xl mb-2"></i>
                                        <p class="text-gray-500 text-sm">No supporting documents uploaded</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status & Actions -->
                    <Card class="shadow-lg">
                        <template #title>Claim Status</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <Tag :value="transportationClaim.status_display" :severity="statusSeverity" 
                                          class="text-base font-semibold px-4 py-2" />
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Created:</span>
                                        <span class="font-medium">{{ formatDateTime(transportationClaim.created_at) }}</span>
                                    </div>
                                    
                                    <div v-if="transportationClaim.updated_at !== transportationClaim.created_at" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Last Updated:</span>
                                        <span class="font-medium">{{ formatDateTime(transportationClaim.updated_at) }}</span>
                                    </div>
                                    
                                    <div v-if="transportationClaim.approval_date" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ transportationClaim.status === 'approved' ? 'Approved' : 'Rejected' }} Date:</span>
                                        <span class="font-medium">{{ formatDateTime(transportationClaim.approval_date) }}</span>
                                    </div>
                                    
                                    <div v-if="transportationClaim.approver" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ transportationClaim.status === 'approved' ? 'Approved' : 'Rejected' }} By:</span>
                                        <span class="font-medium">{{ transportationClaim.approver.name }}</span>
                                    </div>
                                </div>

                                <!-- Rejection Reason -->
                                <div v-if="transportationClaim.rejection_reason" class="p-3 bg-red-50 border border-red-200 rounded">
                                    <h5 class="font-semibold text-red-800 text-sm mb-1">Rejection Reason:</h5>
                                    <p class="text-red-700 text-sm">{{ transportationClaim.rejection_reason }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="space-y-2 pt-2">
                                    <Button v-if="isEditable" label="Edit Claim" icon="pi pi-pencil" severity="warning" outlined
                                        @click="editClaim"
                                        class="w-full responsive-button" />
                                    
                                    <Button v-if="canSubmit" label="Submit for Approval" icon="pi pi-send" severity="success"
                                        @click="openAcknowledgmentDialog"
                                        class="w-full responsive-button" />
                                    
                                    <Button v-if="isEditable" label="Delete Claim" icon="pi pi-trash" severity="danger" text
                                        @click="deleteClaim"
                                        class="w-full responsive-button" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Summary -->
                    <Card class="shadow-lg">
                        <template #title>Quick Summary</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Claim ID:</span>
                                    <span class="text-sm font-medium">#{{ transportationClaim.id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Transport Type:</span>
                                    <span class="text-sm font-medium">{{ transportationClaim.transport_type_display }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Route:</span>
                                    <span class="text-sm font-medium text-right" style="max-width: 150px; word-wrap: break-word;">
                                        {{ transportationClaim.route }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trip Type:</span>
                                    <span class="text-sm font-medium">{{ transportationClaim.trip_type_display }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trips:</span>
                                    <span class="text-sm font-medium">{{ transportationClaim.number_of_trips }}</span>
                                </div>
                                <div v-if="isDistanceBased" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Distance:</span>
                                    <span class="text-sm font-medium">{{ transportationClaim.distance_km }} km</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(transportationClaim.amount, transportationClaim.currency) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Help Information -->
                    <Card class="shadow-lg border-l-4 border-blue-500">
                        <template #content>
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-800 text-sm">Claim Information:</h4>
                                <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                    <li v-if="transportationClaim.status === 'draft'">This claim is in draft mode and can be edited</li>
                                    <li v-if="transportationClaim.status === 'submitted'">Claim has been submitted for approval</li>
                                    <li v-if="transportationClaim.status === 'pending'">Claim is pending review by approver</li>
                                    <li v-if="transportationClaim.status === 'approved'">Claim has been approved and will be processed for payment</li>
                                    <li v-if="transportationClaim.status === 'rejected'">Claim was rejected - see reason above</li>
                                    <li v-if="transportationClaim.status === 'paid'">Claim has been paid</li>
                                    <li>Contact administrator for any questions</li>
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

/* Custom styles for status tags */
:deep(.p-tag) {
    min-width: 100px;
    justify-content: center;
}
</style>