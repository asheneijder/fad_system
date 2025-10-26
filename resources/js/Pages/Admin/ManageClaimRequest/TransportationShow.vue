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
import Textarea from "primevue/textarea";
import ConfirmDialog from "primevue/confirmdialog";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

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
const confirmOfficialBusiness = ref(false);

// Reset when dialog closes
watch(showApproveDialog, (newVal) => {
    if (!newVal) {
        confirmOfficialBusiness.value = false;
    }
});

// Computed properties
const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = ref([
    { label: 'Claim Requests', url: route('admin.claim-request.index') },
    { label: `Transportation Claim #${props.claim.id}` }
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

// Get trip type display text
const getTripTypeDisplay = (tripType) => {
    const tripTypeMap = {
        'one_way': 'One Way',
        'round_trip': 'Round Trip',
        'multiple': 'Multiple Trips'
    };
    return tripTypeMap[tripType] || tripType;
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

    router.put(route('admin.manage.claim-request.approve', {
        id: props.claim.id,
        type: 'transportation'  // Add this
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Approved",
                detail: "Transportation claim approved successfully",  // Fixed message
                life: 3000,
            });
            showApproveDialog.value = false;
            loading.value = false;
            router.reload();
        },
        onError: (errors) => {
            let errorMessage = "Failed to approve claim";
            if (errors.message) {
                errorMessage = errors.message;
            }
            toast.add({
                severity: "error",
                summary: "Error",
                detail: errorMessage,
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

    router.put(route('admin.manage.claim-request.reject', {
        id: props.claim.id,
        type: 'transportation'  // Add this
    }), {
        notes: rejectionReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Rejected",
                detail: "Transportation claim rejected successfully",  // Fixed message
                life: 3000,
            });
            showRejectDialog.value = false;
            rejectionReason.value = '';
            loading.value = false;
            router.reload();
        },
        onError: (errors) => {
            let errorMessage = "Failed to reject claim";
            if (errors.message) {
                errorMessage = errors.message;
            } else if (errors.notes) {
                errorMessage = errors.notes[0];
            }
            toast.add({
                severity: "error",
                summary: "Error",
                detail: errorMessage,
                life: 3000,
            });
            loading.value = false;
        }
    });
};
</script>

<template>

    <Head :title="`Transportation Claim #${claim.id}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Approve Dialog for Transportation Claim -->
        <Dialog v-model:visible="showApproveDialog" modal header="Approve Transportation Claim"
            :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Approve Confirmation</span>
                    </div>
                    <p class="text-sm text-blue-700 mt-1">
                        Are you sure you want to approve this transportation claim from {{ claim.user?.name }}?
                    </p>
                </div>

                <!-- Confirmation Checkbox -->
                <div class="flex items-start gap-3 p-3 border rounded-lg">
                    <Checkbox v-model="confirmOfficialBusiness" :binary="true" inputId="officialBusiness" />
                    <label for="officialBusiness" class="text-sm cursor-pointer">
                        <span class="font-medium">I confirm that this transportation is for official business
                            purposes.</span>
                        <br>
                        <span class="text-xs text-gray-600 italic">
                            Saya mengesahkan bahawa pengangkutan ini adalah atas urusan rasmi.
                        </span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button label="Cancel" severity="secondary" outlined @click="showApproveDialog = false"
                        :disabled="loading" class="text-sm" />
                    <Button label="Approve Claim" icon="pi pi-check" severity="success" @click="approveClaim"
                        :disabled="!confirmOfficialBusiness || loading" :loading="loading" class="text-sm" />
                </div>
            </div>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:visible="showRejectDialog" modal header="Reject Transportation Claim"
            :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                        <span class="text-sm font-medium text-red-800">Rejection Confirmation</span>
                    </div>
                    <p class="text-sm text-red-700 mt-1">
                        Are you sure you want to reject this transportation claim from {{ claim.user?.name }}?
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Rejection Reason *</label>
                    <Textarea v-model="rejectionReason" placeholder="Enter reason for rejection..." rows="3"
                        class="w-full" :disabled="loading" />
                    <small class="text-gray-500 text-xs">
                        Required for rejection. This will be visible to the user.
                    </small>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button label="Cancel" severity="secondary" outlined @click="showRejectDialog = false"
                        :disabled="loading" class="text-sm" />
                    <Button label="Reject Claim" icon="pi pi-times" severity="danger" @click="rejectClaim"
                        :loading="loading" class="text-sm" />
                </div>
            </div>
        </Dialog>

        <div class="p-3 sm:p-4 md:p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 hover:text-blue-800 cursor-pointer"
                        @click="router.get(item.url)">
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
                            Transportation Claim #{{ claim.id }}
                        </h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-500">Public transportation claim details</p>
                    </div>
                    <Tag :value="getStatusText(claim.status)" :severity="statusSeverity" class="text-sm font-medium" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canProcess" label="Approve Claim" icon="pi pi-check" severity="success"
                        @click="openApproveDialog" class="responsive-button" />

                    <Button v-if="canProcess" label="Reject Claim" icon="pi pi-times" severity="danger" outlined
                        @click="openRejectDialog" class="responsive-button" />

                    <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.get(route('admin.claim-request.index'))" class="responsive-button" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transportation Information -->
                    <Card class="shadow-lg">
                        <template #title>Transportation Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Transport Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.transport_type_display
                                        }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Trip Type</label>
                                        <p class="text-lg font-semibold text-gray-800">{{
                                            getTripTypeDisplay(claim.trip_type) }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">From Location</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.from_location }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">To Location</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.to_location }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Claim Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.claim_date)
                                        }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Travel Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.travel_date)
                                        }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Purpose</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.purpose }}</p>
                                </div>

                                <div v-if="claim.remarks" class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Remarks</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.remarks }}</p>
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
                                    <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(claim.amount,
                                        claim.currency) }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Total Claim Amount</p>
                                </div>

                                <Divider />

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Number of Trips</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.number_of_trips }} trips
                                        </p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Cost per Trip</label>
                                        <p class="text-lg font-semibold text-gray-800">{{
                                            formatCurrency(claim.cost_per_trip, claim.currency) }}/trip</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Distance</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.distance_km }} km</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Rate per KM</label>
                                        <p class="text-lg font-semibold text-gray-800">{{
                                            formatCurrency(claim.rate_per_km, claim.currency) }}/km</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Receipt Number</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.receipt_number || '—' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="text-sm text-gray-600 text-center">
                                        <div v-if="claim.distance_km && claim.rate_per_km">
                                            Distance Calculation: {{ claim.distance_km }} km × {{
                                                formatCurrency(claim.rate_per_km, claim.currency) }}/km
                                        </div>
                                        <div v-if="claim.number_of_trips && claim.cost_per_trip">
                                            Trip Calculation: {{ claim.number_of_trips }} trips × {{
                                                formatCurrency(claim.cost_per_trip, claim.currency) }}/trip
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
                                            <p class="text-xs text-gray-500">{{ (doc.size / 1024).toFixed(2) }} KB</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-eye" severity="info" text rounded
                                            @click="viewDocument(doc.url)" v-tooltip="'View document'" />
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

                                    <div v-if="claim.submitted_at" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Submitted:</span>
                                        <span class="font-medium">{{ formatDateTime(claim.updated_at) }}</span>
                                    </div>

                                    <div v-if="claim.approver" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ claim.status === 'approved' ? 'Approved' :
                                            'Rejected' }} By:</span>
                                        <span class="font-medium">{{ claim.approver?.name }}</span>
                                    </div>

                                    <div v-if="claim.approval_date" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ claim.status === 'approved' ? 'Approved' :
                                            'Rejected' }} Date:</span>
                                        <span class="font-medium">{{ formatDateTime(claim.approval_date) }}</span>
                                    </div>
                                </div>

                                <!-- Rejection Reason -->
                                <div v-if="claim.status === 'rejected' && claim.rejection_reason"
                                    class="p-3 bg-red-50 border border-red-200 rounded">
                                    <h5 class="font-semibold text-red-800 text-sm mb-1">Rejection Reason:</h5>
                                    <p class="text-red-700 text-sm">{{ claim.rejection_reason }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="space-y-2 pt-2">
                                    <Button v-if="canProcess" label="Approve Claim" icon="pi pi-check"
                                        severity="success" @click="openApproveDialog"
                                        class="w-full responsive-button" />

                                    <Button v-if="canProcess" label="Reject Claim" icon="pi pi-times" severity="danger"
                                        outlined @click="openRejectDialog" class="w-full responsive-button" />
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
                                    <span class="text-sm text-gray-600">Transport:</span>
                                    <span class="text-sm font-medium">{{ claim.transport_type_display }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trip Type:</span>
                                    <span class="text-sm font-medium">{{ getTripTypeDisplay(claim.trip_type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Trips:</span>
                                    <span class="text-sm font-medium">{{ claim.number_of_trips }} trips</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Distance:</span>
                                    <span class="text-sm font-medium">{{ claim.distance_km }} km</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(claim.rate_per_km,
                                        claim.currency) }}/km</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ claim.currency }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claim.amount,
                                        claim.currency) }}</span>
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
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-orange-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ claim.user?.name }}</div>
                                        <div class="text-sm text-gray-500">{{ claim.user?.email }}</div>
                                        <div class="text-xs text-gray-400">User ID: {{ claim.user_id }}</div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Submitted on {{ formatDate(claim.updated_at) }}
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