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
    { label: `Accommodation Claim #${props.claim.id}` }
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

const formatPercentage = (percentage) => {
    if (!percentage) return '—';
    return `${percentage}%`;
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
    
    // Use the specific travel claim approve route
    router.put(route('admin.manage.claim-request.approve', props.claim.id), {}, {
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
            
            // Refresh the page to show updated status
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
    
    // Use the specific travel claim reject route
    router.put(route('admin.manage.claim-request.reject', props.claim.id), {
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
            
            // Refresh the page to show updated status
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
    <Head :title="`Accommodation Claim #${claim.id}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Approve Dialog -->
        <Dialog v-model:visible="showApproveDialog" modal header="Approve Accommodation Claim" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Approve Confirmation</span>
                    </div>
                    <p class="text-sm text-blue-700 mt-1">
                        Are you sure you want to approve this accommodation claim from {{ claim.user?.name }}?
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
        <Dialog v-model:visible="showRejectDialog" modal header="Reject Accommodation Claim" 
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">
            <div class="space-y-4">
                <div class="p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                        <span class="text-sm font-medium text-red-800">Rejection Confirmation</span>
                    </div>
                    <p class="text-sm text-red-700 mt-1">
                        Are you sure you want to reject this accommodation claim from {{ claim.user?.name }}?
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
                            Accommodation Claim #{{ claim.id }}
                        </h1>
                        <p class="mt-1 text-sm sm:text-base text-gray-500">Hotel accommodation claim details</p>
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
                    <!-- Accommodation Information -->
                    <Card class="shadow-lg">
                        <template #title>Accommodation Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Hotel Name</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.hotel_name }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Number of Nights</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.number_of_nights }} nights</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Check-in Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.check_in_date) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Check-out Date</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(claim.check_out_date) }}</p>
                                    </div>
                                </div>

                                <div v-if="claim.hotel_address" class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Hotel Address</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.hotel_address }}</p>
                                </div>

                                <div v-if="claim.hotel_city || claim.hotel_country" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="claim.hotel_city" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Hotel City</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.hotel_city }}</p>
                                    </div>
                                    <div v-if="claim.hotel_country" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Hotel Country</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.hotel_country }}</p>
                                    </div>
                                </div>

                                <div v-if="claim.destination_city || claim.destination_country" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="claim.destination_city" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Destination City</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.destination_city }}</p>
                                    </div>
                                    <div v-if="claim.destination_country" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Destination Country</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.destination_country }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-600">Purpose</label>
                                    <p class="text-lg text-gray-800 leading-relaxed">{{ claim.purpose }}</p>
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
                                    <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(claim.amount, claim.currency) }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Total Claim Amount</p>
                                </div>

                                <Divider />

                                <!-- Basic Amount Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Number of Nights</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ claim.number_of_nights }} nights</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Rate per Night</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.rate_per_night, claim.currency) }}/night</p>
                                    </div>
                                </div>

                                <!-- Currency Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Currency</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ getCurrencyDisplay(claim.currency) }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Subtotal Amount</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.subtotal_amount, claim.currency) }}</p>
                                    </div>
                                </div>

                                <!-- Tax and Service Charge -->
                                <div v-if="claim.tax_percentage || claim.service_charge_percentage" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="claim.tax_percentage" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Tax Percentage</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatPercentage(claim.tax_percentage) }}</p>
                                    </div>
                                    <div v-if="claim.service_charge_percentage" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Service Charge Percentage</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatPercentage(claim.service_charge_percentage) }}</p>
                                    </div>
                                </div>

                                <div v-if="claim.tax_amount || claim.service_charge_amount" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="claim.tax_amount" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Tax Amount</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.tax_amount, claim.currency) }}</p>
                                    </div>
                                    <div v-if="claim.service_charge_amount" class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-600">Service Charge Amount</label>
                                        <p class="text-lg font-semibold text-gray-800">{{ formatCurrency(claim.service_charge_amount, claim.currency) }}</p>
                                    </div>
                                </div>

                                <!-- Calculation Summary -->
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="text-sm text-gray-600">
                                        <div class="font-medium mb-2">Calculation Breakdown:</div>
                                        <div class="space-y-1">
                                            <div class="flex justify-between">
                                                <span>Base Amount:</span>
                                                <span>{{ formatCurrency(claim.rate_per_night * claim.number_of_nights, claim.currency) }}</span>
                                            </div>
                                            <div v-if="claim.tax_amount" class="flex justify-between">
                                                <span>Tax ({{ claim.tax_percentage }}%):</span>
                                                <span>{{ formatCurrency(claim.tax_amount, claim.currency) }}</span>
                                            </div>
                                            <div v-if="claim.service_charge_amount" class="flex justify-between">
                                                <span>Service Charge ({{ claim.service_charge_percentage }}%):</span>
                                                <span>{{ formatCurrency(claim.service_charge_amount, claim.currency) }}</span>
                                            </div>
                                            <div class="flex justify-between font-semibold border-t pt-1">
                                                <span>Total:</span>
                                                <span>{{ formatCurrency(claim.amount, claim.currency) }}</span>
                                            </div>
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
                                    
                                 <div v-if="claim.approver && (claim.status === 'approved' || claim.status === 'rejected')" class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ claim.status === 'approved' ? 'Approved' : 'Rejected' }} By:</span>
                                    <span class="font-medium">{{ claim.approver?.name }}</span>
                                </div>
                                </div>

                             <div v-if="claim.approved_at && claim.status === 'approved'" class="flex justify-between text-sm">
                                <span class="text-gray-600">Approved On:</span>
                                <span class="font-medium">{{ formatDateTime(claim.approved_at) }}</span>
                            </div>

                            <div v-if="claim.rejected_at && claim.status === 'rejected'" class="flex justify-between text-sm">
                                <span class="text-gray-600">Rejected On:</span>
                                <span class="font-medium">{{ formatDateTime(claim.rejected_at) }}</span>
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
                                    <span class="text-sm text-gray-600">Hotel:</span>
                                    <span class="text-sm font-medium">{{ claim.hotel_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Nights:</span>
                                    <span class="text-sm font-medium">{{ claim.number_of_nights }} nights</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Currency:</span>
                                    <span class="text-sm font-medium">{{ claim.currency }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rate:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(claim.rate_per_night, claim.currency) }}/night</span>
                                </div>
                                <div v-if="claim.tax_percentage" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Tax:</span>
                                    <span class="text-sm font-medium">{{ claim.tax_percentage }}%</span>
                                </div>
                                <div v-if="claim.service_charge_percentage" class="flex justify-between">
                                    <span class="text-sm text-gray-600">Service Charge:</span>
                                    <span class="text-sm font-medium">{{ claim.service_charge_percentage }}%</span>
                                </div>
                                <hr class="my-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claim.amount, claim.currency) }}</span>
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