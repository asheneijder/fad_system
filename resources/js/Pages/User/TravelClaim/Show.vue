<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, nextTick } from "vue";

const toast = useToast();

const props = defineProps({
    travelClaim: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Claim Details' }
];

const loading = ref(false);
const showAcknowledgmentDialog = ref(false);
const hasScrolledToBottom = ref(false);
const agreedToTerms = ref(false);

const getStatusSeverity = (status) => {
    const statusMap = {
        draft: 'secondary',
        submitted: 'info',
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        paid: 'info',
        cancelled: 'secondary'
    };
    return statusMap[status] || 'secondary';
};

const getStatusText = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const editClaim = () => {
    router.get(route('user.travel-claims.edit', { travelClaim: props.travelClaim.id }));
};

const goBack = () => {
    router.get(route('user.request-claim.index'));
};

const deleteClaim = () => {
    if (confirm('Are you sure you want to delete this travel claim? This action cannot be undone.')) {
        loading.value = true;
        router.delete(route('user.travel-claims.destroy', { travelClaim: props.travelClaim.id }), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Travel claim deleted successfully',
                    life: 3000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete travel claim',
                    life: 3000
                });
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    }
};

const openAcknowledgmentDialog = () => {
    showAcknowledgmentDialog.value = true;
    hasScrolledToBottom.value = false;
    agreedToTerms.value = false;
    
    // Reset scroll position and enable scrolling after dialog is shown
    nextTick(() => {
        const content = document.getElementById('acknowledgment-content');
        if (content) {
            content.scrollTop = 0;
        }
    });
};

const onAcknowledgmentScroll = (event) => {
    const element = event.target;
    const scrollPosition = element.scrollTop + element.clientHeight;
    const totalHeight = element.scrollHeight;
    const isAtBottom = Math.abs(scrollPosition - totalHeight) < 5; // Allow small tolerance
    
    if (isAtBottom) {
        hasScrolledToBottom.value = true;
    }
};

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
    showAcknowledgmentDialog.value = false;
    
    // Use the new submit route
    router.post(route('user.travel-claims.submit', { travelClaim: props.travelClaim.id }), {}, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Travel claim submitted for approval',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to submit travel claim',
                life: 3000
            });
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

const printClaim = () => {
    // Open PDF in new tab
    window.open(route('user.travel-claims.pdf', { travelClaim: props.travelClaim.id }), '_blank');
};

const downloadDocument = (document) => {
    if (document.url) {
        window.open(document.url, '_blank');
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Download Unavailable',
            detail: 'Document download is not available',
            life: 3000
        });
    }
};

// Computed properties for travel legs
const travelLegs = ref(props.travelClaim.travel_legs_data || []);
const vehicleTypeLabel = computed(() => {
    const types = {
        car: 'Car',
        motorcycle: 'Motorcycle',
        bicycle: 'Bicycle',
        other: 'Other Vehicle'
    };
    return types[props.travelClaim.vehicle_type] || props.travelClaim.vehicle_type;
});

const totalDistanceFromLegs = computed(() => {
    return travelLegs.value.reduce((total, leg) => total + (parseFloat(leg.distance) || 0), 0);
});

const totalCostFromLegs = computed(() => {
    return totalDistanceFromLegs.value * (parseFloat(props.travelClaim.rate_per_km) || 0);
});

const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});
</script>

<template>
    <Head :title="`Travel Claim #${travelClaim.id}`" />
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
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(travelClaim.total_cost) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(travelClaim.total_cost) }}</p>
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
                        class="responsive-icon-button" v-tooltip="'Back to Claims'" />
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Travel Claim #{{ travelClaim.id }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :value="getStatusText(travelClaim.status)"
                                :severity="getStatusSeverity(travelClaim.status)"
                                class="capitalize responsive-badge" />
                            <span class="text-sm text-gray-500">Created on {{ formatDateTime(travelClaim.created_at) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button v-if="travelClaim.status === 'draft'" 
                        label="Edit Claim" icon="pi pi-pencil" severity="warning"
                        @click="editClaim" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="travelClaim.status === 'draft'"
                        label="Submit for Approval" icon="pi pi-send" severity="primary"
                        @click="openAcknowledgmentDialog" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="travelClaim.status === 'draft'"
                        label="Delete" icon="pi pi-trash" severity="danger" outlined
                        @click="deleteClaim" :loading="loading"
                        class="responsive-button" />
                </div>
            </div>

            <!-- Claim Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Requester Information -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-user text-blue-500"></i>
                                <span class="text-lg font-semibold">Requester Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Submitted By</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.user?.name || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.user?.department || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Position</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.user?.job_title || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Approver Name</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.user?.approver?.name || 'No Approver Assigned' }}</p>
                                    <p v-if="travelClaim.user?.approver?.email" class="text-sm text-gray-500">
                                        {{ travelClaim.user.approver.email }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Vehicle Information -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-car text-green-500"></i>
                                <span class="text-lg font-semibold">Vehicle Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Vehicle Type</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ vehicleTypeLabel }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Registration Plate</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.registration_plate_number || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Cubic Capacity</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ travelClaim.cubic_capacity ? `${travelClaim.cubic_capacity} cc` : '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Rate per KM</label>
                                    <p class="text-lg font-semibold text-blue-600">{{ formatCurrency(travelClaim.rate_per_km) }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Legs Details -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-orange-500"></i>
                                <span class="text-lg font-semibold">Travel Journey</span>
                                <Badge v-if="travelClaim.is_multiple_days" value="Multiple Days" severity="info" class="ml-2" />
                                <Badge v-else value="Single Day" severity="success" class="ml-2" />
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <!-- Travel Legs Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3">#</th>
                                                <th class="px-4 py-3">Date</th>
                                                <th class="px-4 py-3">From</th>
                                                <th class="px-4 py-3">To</th>
                                                <th class="px-4 py-3 text-right">Distance (km)</th>
                                                <th class="px-4 py-3 text-right">Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(leg, index) in travelLegs" :key="index" 
                                                class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    {{ index + 1 }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ formatDate(leg.date) }}
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    {{ leg.from }}
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    {{ leg.to }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    {{ parseFloat(leg.distance).toFixed(1) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold">
                                                    {{ formatCurrency(leg.distance * travelClaim.rate_per_km) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 font-semibold">
                                            <tr>
                                                <td class="px-4 py-3 text-right" colspan="4">Total:</td>
                                                <td class="px-4 py-3 text-right text-blue-600">
                                                    {{ totalDistanceFromLegs.toFixed(1) }} km
                                                </td>
                                                <td class="px-4 py-3 text-right text-blue-600">
                                                    {{ formatCurrency(totalCostFromLegs) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Travel Summary -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700">Travel Period</label>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ formatDate(travelClaim.date_of_travel) }}
                                            <span v-if="travelClaim.end_date_of_travel">
                                                - {{ formatDate(travelClaim.end_date_of_travel) }}
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700">Total Journey</label>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ travelClaim.travel_from }} → {{ travelClaim.travel_to }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Purpose & Notes -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-file text-purple-500"></i>
                                <span class="text-lg font-semibold">Purpose & Additional Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Purpose of Travel</label>
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ travelClaim.purpose || 'No purpose specified' }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Cost Summary -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-money-bill text-red-500"></i>
                                <span class="text-lg font-semibold">Cost Summary</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Total Distance:</span>
                                        <span class="font-semibold">{{ travelClaim.total_distance }} km</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Rate per KM:</span>
                                        <span class="font-semibold">{{ formatCurrency(travelClaim.rate_per_km) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center border-t pt-2">
                                        <span class="text-lg font-bold text-gray-800">Total Claim Amount:</span>
                                        <span class="text-lg font-bold text-blue-600">{{ formatCurrency(travelClaim.total_cost) }}</span>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="pi pi-calculator text-green-600"></i>
                                        <span class="font-semibold text-green-800">Calculation</span>
                                    </div>
                                    <p class="text-sm text-green-700">
                                        {{ travelClaim.total_distance }} km × {{ formatCurrency(travelClaim.rate_per_km) }} = {{ formatCurrency(travelClaim.total_cost) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Status & Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-orange-500"></i>
                                <span class="text-lg font-semibold">Claim Status</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <Badge :value="getStatusText(travelClaim.status)"
                                        :severity="getStatusSeverity(travelClaim.status)"
                                        class="capitalize text-lg px-4 py-2" />
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Created:</span>
                                        <span class="font-medium">{{ formatDateTime(travelClaim.created_at) }}</span>
                                    </div>
                                    <div v-if="travelClaim.updated_at && travelClaim.updated_at !== travelClaim.created_at" class="flex justify-between">
                                        <span class="text-gray-500">Last Updated:</span>
                                        <span class="font-medium">{{ formatDateTime(travelClaim.updated_at) }}</span>
                                    </div>
                                    <div v-if="travelClaim.claim_date" class="flex justify-between">
                                        <span class="text-gray-500">Claim Date:</span>
                                        <span class="font-medium">{{ formatDate(travelClaim.claim_date) }}</span>
                                    </div>
                                    <div v-if="travelClaim.approval_date" class="flex justify-between">
                                        <span class="text-gray-500">Approval Date:</span>
                                        <span class="font-medium">{{ formatDateTime(travelClaim.approval_date) }}</span>
                                    </div>
                                    <div v-if="travelClaim.approver" class="flex justify-between">
                                        <span class="text-gray-500">Approved By:</span>
                                        <span class="font-medium">{{ travelClaim.approver.name }}</span>
                                    </div>
                                    <div v-if="travelClaim.rejection_reason" class="flex justify-between">
                                        <span class="text-gray-500">Rejection Reason:</span>
                                        <span class="font-medium text-red-600">{{ travelClaim.rejection_reason }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Supporting Documents -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-paperclip text-green-500"></i>
                                <span class="text-lg font-semibold">Supporting Documents</span>
                                <Badge v-if="travelClaim.documents && travelClaim.documents.length > 0" 
                                    :value="travelClaim.documents.length" 
                                    class="ml-2 responsive-badge" />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="travelClaim.documents && travelClaim.documents.length > 0" class="space-y-2">
                                <div v-for="(doc, index) in travelClaim.documents" :key="index" 
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <i class="pi pi-file text-gray-500 flex-shrink-0"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ doc.name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ (doc.size / 1024).toFixed(1) }} KB • {{ doc.type }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button icon="pi pi-download" text rounded severity="secondary"
                                        v-tooltip="'Download document'" 
                                        @click="downloadDocument(doc)"
                                        class="flex-shrink-0" />
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No documents attached</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-bolt text-red-500"></i>
                                <span class="text-lg font-semibold">Quick Actions</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-2">
                                <Button v-if="travelClaim.status === 'draft'" 
                                    label="Submit for Approval" icon="pi pi-send" severity="primary"
                                    @click="openAcknowledgmentDialog" :loading="loading"
                                    class="w-full responsive-button" />
                                
                                <Button v-if="travelClaim.status === 'approved'"
                                    label="Print Claim" icon="pi pi-print" severity="secondary" outlined
                                    @click="printClaim" :loading="loading"
                                    class="w-full responsive-button" />
                                
                                <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" text
                                    @click="goBack"
                                    class="w-full responsive-button" />
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

.responsive-badge :deep(.p-badge) {
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
}

@media (min-width: 640px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
}

@media (min-width: 1024px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.875rem;
        padding: 0.35rem 0.7rem;
    }
}

table {
    border-collapse: collapse;
    width: 100%;
}

table th, table td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
}

table th {
    background-color: #f9fafb;
    font-weight: 600;
    text-align: left;
}

table th.text-right {
    text-align: right;
}

table td.text-right {
    text-align: right;
}

/* Custom styles for travel legs */
.travel-leg-connector {
    position: relative;
}

.travel-leg-connector::before {
    content: "→";
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}
</style>