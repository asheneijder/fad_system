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
    { label: `Daily Allowance #${props.claim.id}` }
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

// Get allowance type display
const getAllowanceTypeDisplay = (type) => {
    const types = {
        'full_day': 'Full Day (100%) - Kelayakan Penuh',
        'breakfast': 'Breakfast (20%) - Sarapan',
        'lunch': 'Lunch (40%) - Makan Tengahari',
        'dinner': 'Dinner (40%) - Makan Malam'
    };
    return types[type] || type;
};

// Get currency display
const getCurrencyDisplay = (currency) => {
    const currencies = {
        'MYR': 'MYR - Malaysian Ringgit',
        'USD': 'USD - US Dollar',
        'SGD': 'SGD - Singapore Dollar',
        'EUR': 'EUR - Euro'
    };
    return currencies[currency] || currency;
};

// Get percentage display with description
const getPercentageDisplay = (percentage, allowanceType) => {
    const percentageMap = {
        100: '100% - Full Day Allowance',
        40: '40% - Lunch/Dinner Allowance',
        20: '20% - Breakfast Allowance'
    };
    return percentageMap[percentage] || `${percentage}%`;
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
                detail: "Daily allowance claim approved successfully",
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
                detail: "Daily allowance claim rejected successfully",
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
    <Head :title="`Daily Allowance #${claim.id}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Approve Dialog -->
        <Dialog v-model:visible="showApproveDialog" modal header="Approve Daily Allowance" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Approve Confirmation</span>
                    </div>
                    <p class="text-sm text-blue-700 mt-1">
                        Are you sure you want to approve this daily allowance claim from {{ claim.user?.name }}?
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
        <Dialog v-model:visible="showRejectDialog" modal header="Reject Daily Allowance" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                        <span class="text-sm font-medium text-red-800">Rejection Confirmation</span>
                    </div>
                    <p class="text-sm text-red-700 mt-1">
                        Are you sure you want to reject this daily allowance claim from {{ claim.user?.name }}?
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
                            Daily Allowance #{{ claim.id }}
                        </h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-500">Meal allowance claim details</p>
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
                    <!-- Basic Information -->
                    <Card class="shadow-lg">
                        <template #title>Basic Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.claim_date) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Allowance Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getAllowanceTypeDisplay(claim.allowance_type) }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Currency</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getCurrencyDisplay(claim.currency) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Destination</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.destination || '—' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Purpose</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.purpose || '—' }}</p>
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
                                    <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(claim.claim_amount, claim.currency) }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Total Claim Amount</p>
                                </div>

                                <Divider />

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Daily Rate</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.daily_rate, claim.currency) }}</p>
                                        <p class="text-xs text-gray-500">Standard daily rate</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim Percentage</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.claim_percentage }}%</p>
                                        <p class="text-xs text-gray-500">{{ getPercentageDisplay(claim.claim_percentage, claim.allowance_type) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Calculated Amount</label>
                                        <p class="text-lg font-semibold text-green-600">{{ formatCurrency(claim.claim_amount, claim.currency) }}</p>
                                        <p class="text-xs text-gray-500">Final claim amount</p>
                                    </div>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="text-sm text-gray-600 text-center">
                                        <strong>Calculation Formula:</strong><br>
                                        {{ formatCurrency(claim.daily_rate, claim.currency) }} × {{ claim.claim_percentage }}% = {{ formatCurrency(claim.claim_amount, claim.currency) }}
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

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="claim.approval_date" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approval Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDateTime(claim.approval_date) }}</p>
                                    </div>
                                </div>

                                <div v-if="claim.approver" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approver</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.approver?.name }}</p>
                                        <p class="text-xs text-gray-500">Approver ID: {{ claim.approver_id }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approver Email</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.approver?.email || '—' }}</p>
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
                                        <div v-if="claim.rejected_at">
                                            <label class="block text-xs font-medium text-red-700">Rejected Date:</label>
                                            <p class="text-red-700 text-sm">{{ formatDateTime(claim.rejected_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- User & System Information -->
                    <Card class="shadow-lg">
                        <template #title>User & System Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">User ID</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.user_id }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claimant Name</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.user?.name }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claimant Email</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.user?.email }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Department</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.user?.department || '—' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Approver ID</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.approver_id || '—' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim ID</label>
                                        <p class="text-lg font-semibold text-gray-800">#{{ claim.id }}</p>
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
                                    <span class="text-sm text-gray-600">Type:</span>
                                    <span class="text-sm font-medium">{{ claim.type_display }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Allowance Type:</span>
                                    <span class="text-sm font-medium">{{ getAllowanceTypeDisplay(claim.allowance_type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ claim.currency }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Daily Rate:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(claim.daily_rate, claim.currency) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Percentage:</span>
                                    <span class="text-sm font-medium">{{ claim.claim_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Destination:</span>
                                    <span class="text-sm font-medium">{{ claim.destination || '—' }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claim.claim_amount, claim.currency) }}</span>
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
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-green-600"></i>
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
                                    <span class="text-gray-600">Daily Rate:</span>
                                    <span class="font-medium">{{ formatCurrency(claim.daily_rate, claim.currency) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Percentage:</span>
                                    <span class="font-medium">{{ claim.claim_percentage }}%</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Calculation:</span>
                                    <span class="font-medium">{{ claim.daily_rate }} × {{ claim.claim_percentage }}%</span>
                                </div>
                                <hr class="my-1">
                                <div class="flex justify-between text-sm font-semibold">
                                    <span class="text-gray-800">Claim Amount:</span>
                                    <span class="text-blue-600">{{ formatCurrency(claim.claim_amount, claim.currency) }}</span>
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