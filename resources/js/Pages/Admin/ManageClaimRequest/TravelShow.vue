<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import Toast from "primevue/toast";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import ConfirmDialog from "primevue/confirmdialog";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    claim: {
        type: Object,
        required: true
    },
    userRole: {
        type: String,
        default: 'approver'
    }
});

// Dialog states
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const rejectionReason = ref('');
const loading = ref(false);

// Computed properties
const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = ref([
    { label: 'Claim Requests', url: route('admin.claim-request.index') },
    { label: `Travel Claim #${props.claim.id}` }
]);

const statusSeverity = computed(() => {
    const statusMap = {
        draft: 'secondary',
        submitted: 'warning',
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        paid: 'info'
    };
    return statusMap[props.claim.status] || 'secondary';
});

const getStatusText = (status) => {
    const statusMap = {
        draft: 'Draft',
        submitted: 'Submitted',
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        paid: 'Paid'
    };
    return statusMap[status] || status;
};

const canProcess = computed(() => {
    return (props.userRole === 'system-admin' || props.userRole === 'approver') && 
           (props.claim.status === 'submitted' || props.claim.status === 'pending');
});

// Format date for display
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
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

const formatCurrency = (amount, currency = 'MYR') => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: currency
    }).format(amount || 0);
};

const formatNumber = (number, decimals = 2) => {
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number || 0);
};

// Get vehicle type display
const getVehicleTypeDisplay = (type) => {
    const types = {
        'car': 'Car',
        'motorcycle': 'Motorcycle',
    };
    return types[type] || type;
};

// Get cubic capacity display
const getCubicCapacityDisplay = (capacity) => {
    if (!capacity) return '—';
    return `${formatNumber(capacity)} cc`;
};

// Calculate trip duration
const getTripDuration = (startDate, endDate, isMultipleDays) => {
    if (!startDate) return '—';
    
    if (!isMultipleDays || !endDate) {
        return '1 day';
    }
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    return `${diffDays} days`;
};

// View document
const viewDocument = (url) => {
    window.open(url, '_blank');
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

// Approve/Reject functions
const openApproveDialog = () => {
    showApproveDialog.value = true;
};

const openRejectDialog = () => {
    showRejectDialog.value = true;
};

const approveClaim = () => {
    loading.value = true;
    router.put(route('manage.claim-request.update', props.claim.id), {
        action: 'approve'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Approved",
                detail: "Travel claim approved successfully",
                life: 3000,
            });
            showApproveDialog.value = false;
            loading.value = false;
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to approve claim",
                life: 3000,
            });
            loading.value = false;
        }
    });
};

const rejectClaim = () => {
    if (!rejectionReason.value.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Notes Required',
            detail: 'Please provide rejection reason',
            life: 3000
        });
        return;
    }

    loading.value = true;
    router.put(route('manage.claim-request.update', props.claim.id), {
        action: 'reject',
        notes: rejectionReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Rejected",
                detail: "Travel claim rejected successfully",
                life: 3000,
            });
            showRejectDialog.value = false;
            rejectionReason.value = '';
            loading.value = false;
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to reject claim",
                life: 3000,
            });
            loading.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Travel Claim #${claim.id}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Approve Dialog -->
        <Dialog v-model:visible="showApproveDialog" modal header="Approve Travel Claim" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Approve Confirmation</span>
                    </div>
                    <p class="text-sm text-blue-700 mt-1">
                        Are you sure you want to approve this travel claim from {{ claim.user?.name }}?
                    </p>
                </div>
                
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button label="Cancel" severity="secondary" outlined 
                            @click="showApproveDialog = false"
                            :disabled="loading"
                            class="text-sm" />
                    <Button label="Approve Claim" icon="pi pi-check" severity="success" 
                            @click="approveClaim"
                            :loading="loading"
                            class="text-sm" />
                </div>
            </div>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:visible="showRejectDialog" modal header="Reject Travel Claim" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                        <span class="text-sm font-medium text-red-800">Rejection Confirmation</span>
                    </div>
                    <p class="text-sm text-red-700 mt-1">
                        Are you sure you want to reject this travel claim from {{ claim.user?.name }}?
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Rejection Reason *</label>
                    <Textarea v-model="rejectionReason" 
                            placeholder="Enter reason for rejection..."
                            rows="3"
                            class="w-full" 
                            :disabled="loading" />
                    <small class="text-gray-500 text-xs">
                        Required for rejection. This will be visible to the user.
                    </small>
                </div>
                
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button label="Cancel" severity="secondary" outlined 
                            @click="showRejectDialog = false"
                            :disabled="loading"
                            class="text-sm" />
                    <Button label="Reject Claim" icon="pi pi-times" severity="danger" 
                            @click="rejectClaim"
                            :loading="loading"
                            class="text-sm" />
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
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                            Travel Claim #{{ claim.id }}
                        </h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-500">Vehicle travel claim details</p>
                    </div>
                    <Tag :value="getStatusText(claim.status)" :severity="statusSeverity" class="text-sm font-medium" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canProcess" label="Approve Claim" icon="pi pi-check" severity="success"
                        @click="openApproveDialog"
                        class="responsive-button" />
                    
                    <Button v-if="canProcess" label="Reject Claim" icon="pi pi-times" severity="danger" outlined
                        @click="openRejectDialog"
                        class="responsive-button" />
                    
                    <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.get(route('admin.claim-request.index'))"
                        class="responsive-button" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Vehicle Information -->
                    <Card class="shadow-lg">
                        <template #title>Vehicle Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Vehicle Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getVehicleTypeDisplay(claim.vehicle_type) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Registration Plate</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.registration_plate_number || '—' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Cubic Capacity</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getCubicCapacityDisplay(claim.cubic_capacity) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Multiple Days Trip</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.is_multiple_days ? 'Yes' : 'No' }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Information -->
                    <Card class="shadow-lg">
                        <template #title>Travel Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Travel Start Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.date_of_travel) }}</p>
                                    </div>
                                    <div v-if="claim.is_multiple_days" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Travel End Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.end_date_of_travel) }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Trip Duration</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getTripDuration(claim.date_of_travel, claim.end_date_of_travel, claim.is_multiple_days) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.claim_date) }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Travel From</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.travel_from || '—' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Travel To</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.travel_to || '—' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Purpose of Travel</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.purpose || '—' }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Travel Legs Information -->
                    <Card v-if="claim.travel_legs_data && claim.travel_legs_data.length > 0" class="shadow-lg">
                        <template #title>Travel Legs Details</template>
                        <template #content>
                            <div class="space-y-3">
                                <div v-for="(leg, index) in claim.travel_legs_data" :key="index" 
                                     class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">From</label>
                                            <p class="text-sm font-semibold text-gray-800">{{ leg.from }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">To</label>
                                            <p class="text-sm font-semibold text-gray-800">{{ leg.to }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">Distance</label>
                                            <p class="text-sm font-semibold text-blue-600">{{ formatNumber(leg.distance) }} km</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-blue-800">Total Distance from Legs:</span>
                                    <span class="text-lg font-bold text-blue-600">
                                        {{ formatNumber(claim.calculated_total_distance) }} km
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Amount Details -->
                    <Card class="shadow-lg">
                        <template #title>Amount Details</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(claim.total_cost) }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Total Claim Amount</p>
                                </div>

                                <Divider />

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Total Distance</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatNumber(claim.total_distance) }} km</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Rate per KM</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.rate_per_km) }}/km</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Calculated Amount</label>
                                        <p class="text-lg font-semibold text-green-600">{{ formatCurrency(claim.total_cost) }}</p>
                                    </div>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="text-sm text-gray-600 text-center">
                                        <strong>Calculation Formula:</strong><br>
                                        {{ formatNumber(claim.total_distance) }} km × {{ formatCurrency(claim.rate_per_km) }}/km = {{ formatCurrency(claim.total_cost) }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Timeline & Approval Information -->
                    <Card class="shadow-lg">
                        <template #title>Timeline & Approval Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Created Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDateTime(claim.created_at) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Last Updated</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDateTime(claim.updated_at) }}</p>
                                    </div>
                                </div>

                                <div v-if="claim.approval_date" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approval Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDateTime(claim.approval_date) }}</p>
                                    </div>
                                    <div v-if="claim.approver" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approved By</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.approver?.name }}</p>
                                    </div>
                                </div>

                                <!-- Rejection Information -->
                                <div v-if="claim.status === 'rejected'" class="p-3 bg-red-50 border border-red-200 rounded">
                                    <h5 class="font-semibold text-red-800 text-sm mb-2">Rejection Details:</h5>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="block text-xs font-medium text-red-700">Rejection Reason:</label>
                                            <p class="text-red-700 text-sm">{{ claim.rejection_reason || '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments -->
                    <Card v-if="claim.documents && claim.documents.length > 0" class="shadow-lg">
                        <template #title>Attachments</template>
                        <template #content>
                            <div class="space-y-3">
                                <div v-for="(doc, index) in claim.documents" :key="index" 
                                     class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-file text-gray-500 text-lg"></i>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">{{ doc.name }}</p>
                                            <p class="text-xs text-gray-500">{{ (doc.size / 1024).toFixed(2) }} KB • {{ doc.mime_type }}</p>
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
                                    <Tag :value="getStatusText(claim.status)" :severity="statusSeverity" 
                                          class="text-base font-semibold px-4 py-2" />
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Created:</span>
                                        <span class="font-medium">{{ formatDateTime(claim.created_at) }}</span>
                                    </div>
                                    
                                    <div v-if="claim.updated_at" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Submitted:</span>
                                        <span class="font-medium">{{ formatDateTime(claim.updated_at) }}</span>
                                    </div>
                                    
                                    <div v-if="claim.approver" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ claim.status === 'approved' ? 'Approved' : 'Rejected' }} By:</span>
                                        <span class="font-medium">{{ claim.approver?.name }}</span>
                                    </div>

                                    <div v-if="claim.approval_date" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ claim.status === 'approved' ? 'Approved' : 'Rejected' }} Date:</span>
                                        <span class="font-medium">{{ formatDateTime(claim.approval_date) }}</span>
                                    </div>
                                </div>

                                <!-- Rejection Reason -->
                                <div v-if="claim.status === 'rejected' && claim.rejection_reason" class="p-3 bg-red-50 border border-red-200 rounded">
                                    <h5 class="font-semibold text-red-800 text-sm mb-1">Rejection Reason:</h5>
                                    <p class="text-red-700 text-sm">{{ claim.rejection_reason }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="space-y-2 pt-2">
                                    <Button v-if="canProcess" label="Approve Claim" icon="pi pi-check" severity="success"
                                        @click="openApproveDialog"
                                        class="w-full responsive-button" />
                                    
                                    <Button v-if="canProcess" label="Reject Claim" icon="pi pi-times" severity="danger" outlined
                                        @click="openRejectDialog"
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
                                    <span class="text-sm font-medium">#{{ claim.id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Vehicle Type:</span>
                                    <span class="text-sm font-medium">{{ getVehicleTypeDisplay(claim.vehicle_type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Registration:</span>
                                    <span class="text-sm font-medium">{{ claim.registration_plate_number || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Capacity:</span>
                                    <span class="text-sm font-medium">{{ getCubicCapacityDisplay(claim.cubic_capacity) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Multiple Days:</span>
                                    <span class="text-sm font-medium">{{ claim.is_multiple_days ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Distance:</span>
                                    <span class="text-sm font-medium">{{ formatNumber(claim.total_distance) }} km</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate per KM:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(claim.rate_per_km) }}/km</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claim.total_cost) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- User Information -->
                    <Card class="shadow-lg">
                        <template #title>User Information</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-purple-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ claim.user?.name }}</div>
                                        <div class="text-sm text-gray-500">{{ claim.user?.email }}</div>
                                        <div class="text-xs text-gray-400">User ID: {{ claim.user_id }}</div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Submitted on {{ claim.updated_at ? formatDate(claim.updated_at) : 'Not submitted' }}
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Calculation Breakdown -->
                    <Card class="shadow-lg">
                        <template #title>Calculation Breakdown</template>
                        <template #content>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Distance:</span>
                                    <span class="font-medium">{{ formatNumber(claim.total_distance) }} km</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Rate per KM:</span>
                                    <span class="font-medium">{{ formatCurrency(claim.rate_per_km) }}/km</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Calculation:</span>
                                    <span class="font-medium">{{ claim.total_distance }} × {{ claim.rate_per_km }}</span>
                                </div>
                                <hr class="my-1">
                                <div class="flex justify-between text-sm font-semibold">
                                    <span class="text-gray-800">Claim Amount:</span>
                                    <span class="text-blue-600">{{ formatCurrency(claim.total_cost) }}</span>
                                </div>
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

:deep(.p-tag) {
    min-width: 100px;
    justify-content: center;
}
</style>