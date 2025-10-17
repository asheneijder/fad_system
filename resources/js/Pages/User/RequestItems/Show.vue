<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Card from "primevue/card";
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import ProgressBar from 'primevue/progressbar';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    requestItem: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'My Requests', url: route('user.request-items.index') },
    { label: 'Request Details' }
];

// Computed properties
const totalItems = computed(() => {
    if (!props.requestItem.items) return 0;
    return props.requestItem.items.reduce((sum, item) => sum + item.quantity, 0);
});

const totalApprovedItems = computed(() => {
    if (!props.requestItem.items) return 0;
    return props.requestItem.items.reduce((sum, item) => sum + (item.approved_quantity || item.quantity), 0);
});

const totalCost = computed(() => {
    if (!props.requestItem.items) return 0;
    return props.requestItem.items.reduce((sum, item) => {
        const quantity = item.approved_quantity || item.quantity;
        return sum + (quantity * item.unit_price);
    }, 0);
});

const approvalRate = computed(() => {
    if (totalItems.value === 0) return 100;
    return Math.round((totalApprovedItems.value / totalItems.value) * 100);
});

// Methods
const getStatusSeverity = (status) => {
    const statusMap = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        completed: 'info',
        cancelled: 'secondary'
    };
    return statusMap[status] || 'secondary';
};

const getStatusText = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const getPrioritySeverity = (priority) => {
    const priorityMap = {
        low: 'success',
        medium: 'warning',
        high: 'danger',
        urgent: 'danger'
    };
    return priorityMap[priority] || 'secondary';
};

const getPriorityIcon = (priority) => {
    const iconMap = {
        low: 'pi pi-arrow-down',
        medium: 'pi pi-minus',
        high: 'pi pi-arrow-up',
        urgent: 'pi pi-exclamation-triangle'
    };
    return iconMap[priority] || 'pi pi-circle';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const getDaysUntilNeeded = () => {
    if (!props.requestItem.needed_by) return null;
    const today = new Date();
    const neededDate = new Date(props.requestItem.needed_by);
    const diffTime = neededDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getUrgencyStatus = () => {
    const daysUntilNeeded = getDaysUntilNeeded();
    
    if (daysUntilNeeded === null) return { label: 'Not specified', severity: 'secondary' };
    
    if (daysUntilNeeded < 0) {
        return { label: 'Overdue', severity: 'danger', icon: 'pi pi-exclamation-circle' };
    } else if (daysUntilNeeded <= 2) {
        return { label: `${daysUntilNeeded} days`, severity: 'danger', icon: 'pi pi-exclamation-triangle' };
    } else if (daysUntilNeeded <= 5) {
        return { label: `${daysUntilNeeded} days`, severity: 'warning', icon: 'pi pi-clock' };
    } else {
        return { label: `${daysUntilNeeded} days`, severity: 'success', icon: 'pi pi-calendar' };
    }
};

const cancelRequest = () => {
    confirm.require({
        message: "Are you sure you want to cancel this request? This action cannot be undone.",
        header: "Cancel Request Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("user.request-items.destroy", props.requestItem.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Cancelled",
                        detail: "Request cancelled successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to cancel request",
                        life: 3000,
                    });
                }
            });
        },
    });
};
</script>

<template>
    <Head :title="`My Request #${requestItem.id}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:underline text-sm sm:text-base" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                <div class="w-full sm:w-auto">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Request #{{ requestItem.id }}</h1>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-2">
                        <Badge :value="getStatusText(requestItem.status)" 
                               :severity="getStatusSeverity(requestItem.status)"
                               class="text-xs sm:text-sm" />
                        <Badge :value="requestItem.priority" 
                               :severity="getPrioritySeverity(requestItem.priority)"
                               class="text-xs sm:text-sm capitalize">
                            <i :class="getPriorityIcon(requestItem.priority)" class="mr-1 text-xs"></i>
                            {{ requestItem.priority }}
                        </Badge>
                        <span class="text-xs sm:text-sm text-gray-500">
                            Requested on {{ formatDate(requestItem.created_at) }}
                        </span>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.get(route('user.request-items.index'))"
                        class="flex-1 sm:flex-initial" size="small" />
                    <Button v-if="requestItem.status === 'pending'" 
                        label="Cancel" icon="pi pi-times" severity="danger" outlined
                        @click="cancelRequest"
                        class="flex-1 sm:flex-initial" size="small" />
                </div>
            </div>

            <!-- Request Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4">
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600">{{ requestItem.items?.length || 0 }}</div>
                            <div class="text-xs sm:text-sm text-blue-700">Items Requested</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-green-50 border-green-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-green-600">{{ totalItems }}</div>
                            <div class="text-xs sm:text-sm text-green-700">Total Quantity</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-purple-50 border-purple-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-sm sm:text-base md:text-2xl font-bold text-purple-600">{{ formatCurrency(totalCost) }}</div>
                            <div class="text-xs sm:text-sm text-purple-700">Total Value</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-orange-50 border-orange-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600" v-if="getUrgencyStatus().severity !== 'secondary'">
                                {{ getUrgencyStatus().label }}
                            </div>
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-gray-600" v-else>—</div>
                            <div class="text-xs sm:text-sm text-orange-700">Time Remaining</div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Request Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Purpose and Notes -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Request Information</h3>
                            <div class="space-y-3 sm:space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Purpose</label>
                                    <p class="mt-1 text-gray-900 text-base sm:text-lg">{{ requestItem.purpose }}</p>
                                </div>
                                <div v-if="requestItem.notes">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Additional Notes</label>
                                    <p class="mt-1 text-sm sm:text-base text-gray-900 whitespace-pre-wrap bg-gray-50 p-2 sm:p-3 rounded">{{ requestItem.notes }}</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <div>
                                        <label class="block text-xs sm:text-sm font-medium text-gray-500">Priority</label>
                                        <Badge :value="requestItem.priority" 
                                               :severity="getPrioritySeverity(requestItem.priority)"
                                               class="mt-1 capitalize">
                                            <i :class="getPriorityIcon(requestItem.priority)" class="mr-1 text-xs"></i>
                                            {{ requestItem.priority }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <label class="block text-xs sm:text-sm font-medium text-gray-500">Needed By</label>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <p class="text-sm sm:text-base text-gray-900">{{ formatDate(requestItem.needed_by) }}</p>
                                            <Badge v-if="getUrgencyStatus().severity !== 'secondary'"
                                                :value="getUrgencyStatus().label"
                                                :severity="getUrgencyStatus().severity"
                                                class="text-xs">
                                                <i :class="getUrgencyStatus().icon" class="mr-1"></i>
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="requestItem.rejection_reason" class="p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <label class="block text-xs sm:text-sm font-medium text-red-700">Rejection Reason</label>
                                    <p class="mt-1 text-sm sm:text-base text-red-600">{{ requestItem.rejection_reason }}</p>
                                </div>
                                <div v-if="requestItem.approved_by" class="p-3 sm:p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <label class="block text-xs sm:text-sm font-medium text-green-700">Approved By</label>
                                    <p class="mt-1 text-sm sm:text-base text-green-600">{{ requestItem.approved_by?.name }} on {{ formatDate(requestItem.approved_at) }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Requested Items -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Requested Items</h3>
                            
                            <!-- Loading State -->
                            <div v-if="!requestItem.items" class="text-center py-6 sm:py-8">
                                <div class="p-4 sm:p-6 mb-3 sm:mb-4 bg-gray-100 rounded-full inline-block">
                                    <i class="text-3xl sm:text-4xl text-gray-400 pi pi-spin pi-spinner"></i>
                                </div>
                                <p class="text-sm sm:text-base text-gray-500">Loading items...</p>
                            </div>
                            
                            <!-- Items List -->
                            <div v-else class="space-y-3 sm:space-y-4">
                                <div v-for="item in requestItem.items" :key="item.id" 
                                    class="border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                    <div class="flex flex-col sm:flex-row items-start justify-between gap-2 sm:gap-0 mb-2 sm:mb-3">
                                        <div class="flex-1 w-full">
                                            <h4 class="font-semibold text-gray-900 text-base sm:text-lg">{{ item.stationary_item?.name }}</h4>
                                            <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ item.stationary_item?.description }}</p>
                                            <p class="text-xs sm:text-sm text-gray-500 mt-1 sm:mt-2">
                                                <i class="pi pi-dollar text-green-500 mr-1"></i>
                                                Price: {{ formatCurrency(item.unit_price) }} per {{ item.stationary_item?.unit }}
                                            </p>
                                        </div>
                                        <Badge v-if="item.stationary_item?.current_stock < item.quantity" 
                                            value="Low Stock" severity="warning" class="text-xs sm:text-sm" />
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-xs sm:text-sm">
                                        <div class="text-center p-2 sm:p-3 bg-gray-50 rounded">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Requested</label>
                                            <p class="font-semibold text-gray-900 text-base sm:text-lg">{{ item.quantity }}</p>
                                            <p class="text-xs text-gray-500">{{ item.stationary_item?.unit }}</p>
                                        </div>
                                        <div class="text-center p-2 sm:p-3 bg-blue-50 rounded">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Approved</label>
                                            <p class="font-semibold text-blue-600 text-base sm:text-lg">
                                                {{ item.approved_quantity || item.quantity }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ item.stationary_item?.unit }}</p>
                                        </div>
                                        <div class="text-center p-2 sm:p-3" :class="{
                                            'bg-green-50': item.approved_quantity === item.quantity,
                                            'bg-yellow-50': item.approved_quantity && item.approved_quantity < item.quantity,
                                            'bg-red-50': !item.approved_quantity
                                        }">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                                            <p class="font-semibold text-sm sm:text-lg" :class="{
                                                'text-green-600': item.approved_quantity === item.quantity,
                                                'text-yellow-600': item.approved_quantity && item.approved_quantity < item.quantity,
                                                'text-red-600': !item.approved_quantity
                                            }">
                                                {{ item.approved_quantity === item.quantity ? 'Full' : 
                                                   item.approved_quantity ? 'Partial' : 'Pending' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ item.approved_quantity ? item.approved_quantity + ' OK' : 'Waiting' }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="item.notes" class="mt-2 sm:mt-3 p-2 sm:p-3 bg-yellow-50 border border-yellow-200 rounded">
                                        <label class="block text-xs font-medium text-yellow-700">Item Notes</label>
                                        <p class="text-xs sm:text-sm text-yellow-600 mt-1">{{ item.notes }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Summary -->
                            <div v-if="requestItem.items" class="border-t mt-4 sm:mt-6 pt-4 sm:pt-6">
                                <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                                    <div>
                                        <span class="block text-xs sm:text-sm font-semibold text-gray-500">Total Requested</span>
                                        <span class="block text-lg sm:text-2xl font-bold text-gray-900 mt-1">{{ totalItems }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs sm:text-sm font-semibold text-gray-500">Total Approved</span>
                                        <span class="block text-lg sm:text-2xl font-bold text-blue-600 mt-1">{{ totalApprovedItems }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs sm:text-sm font-semibold text-gray-500">Total Cost</span>
                                        <span class="block text-sm sm:text-2xl font-bold text-green-600 mt-1">{{ formatCurrency(totalCost) }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 sm:mt-4">
                                    <div class="flex justify-between items-center text-xs sm:text-sm mb-2">
                                        <span class="font-medium">Approval Rate</span>
                                        <span class="font-bold" :class="{
                                            'text-green-600': approvalRate >= 80,
                                            'text-yellow-600': approvalRate >= 50 && approvalRate < 80,
                                            'text-red-600': approvalRate < 50
                                        }">
                                            {{ approvalRate }}%
                                        </span>
                                    </div>
                                    <ProgressBar :value="approvalRate" 
                                                :showValue="false"
                                                :class="{
                                                    'p-progressbar-success': approvalRate >= 80,
                                                    'p-progressbar-warning': approvalRate >= 50 && approvalRate < 80,
                                                    'p-progressbar-danger': approvalRate < 50
                                                }" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Request Summary -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Request Summary</h3>
                            <div class="space-y-3 sm:space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Status</label>
                                    <Badge :value="getStatusText(requestItem.status)" 
                                           :severity="getStatusSeverity(requestItem.status)"
                                           class="mt-1 text-sm sm:text-base" />
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Request ID</label>
                                    <p class="mt-1 text-sm sm:text-base font-medium text-gray-900">#{{ requestItem.id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Request Date</label>
                                    <p class="mt-1 text-sm sm:text-base font-medium text-gray-900">{{ formatDate(requestItem.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Last Updated</label>
                                    <p class="mt-1 text-sm sm:text-base font-medium text-gray-900">{{ formatDate(requestItem.updated_at) }}</p>
                                </div>
                                <div v-if="requestItem.approved_by">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Approved By</label>
                                    <div class="mt-1 flex items-center gap-2 sm:gap-3">
                                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="pi pi-user-check text-green-600 text-xs sm:text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm sm:text-base font-medium text-gray-900">{{ requestItem.approved_by?.name }}</p>
                                            <p class="text-xs sm:text-sm text-gray-500">{{ formatDate(requestItem.approved_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Approval Statistics -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Approval Summary</h3>
                            <div class="space-y-2 sm:space-y-3">
                                <div class="flex justify-between items-center text-xs sm:text-sm">
                                    <span class="text-gray-600">Items:</span>
                                    <span class="font-medium">{{ requestItem.items?.length || 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs sm:text-sm">
                                    <span class="text-gray-600">Requested Qty:</span>
                                    <span class="font-medium">{{ totalItems }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs sm:text-sm">
                                    <span class="text-gray-600">Approved Qty:</span>
                                    <span class="font-medium text-blue-600">{{ totalApprovedItems }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs sm:text-sm">
                                    <span class="text-gray-600">Reduction:</span>
                                    <span class="font-medium" :class="{
                                        'text-green-600': totalApprovedItems === totalItems,
                                        'text-yellow-600': totalApprovedItems < totalItems && totalApprovedItems > 0,
                                        'text-red-600': totalApprovedItems === 0
                                    }">
                                        {{ totalItems - totalApprovedItems }} items
                                    </span>
                                </div>
                                <div class="pt-2 border-t">
                                    <div class="flex justify-between items-center text-xs sm:text-sm">
                                        <span class="text-gray-600 font-semibold">Approval Rate:</span>
                                        <span class="font-bold text-base sm:text-lg" :class="{
                                            'text-green-600': approvalRate >= 80,
                                            'text-yellow-600': approvalRate >= 50 && approvalRate < 80,
                                            'text-red-600': approvalRate < 50
                                        }">
                                            {{ approvalRate }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Next Steps -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Next Steps</h3>
                            <div class="space-y-2 sm:space-y-3">
                                <div v-if="requestItem.status === 'pending'" class="flex items-start gap-2 sm:gap-3">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-yellow-100 rounded-full flex items-center justify-center mt-0.5">
                                        <i class="pi pi-clock text-yellow-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-900">Under Review</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Your request is being reviewed by the admin team.</p>
                                    </div>
                                </div>
                                
                                <div v-if="requestItem.status === 'approved'" class="flex items-start gap-2 sm:gap-3">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full flex items-center justify-center mt-0.5">
                                        <i class="pi pi-check text-green-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-900">Approved</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Your request has been approved and will be processed soon.</p>
                                    </div>
                                </div>
                                
                                <div v-if="requestItem.status === 'completed'" class="flex items-start gap-2 sm:gap-3">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                        <i class="pi pi-check-square text-blue-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-900">Completed</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Your request has been fulfilled and items have been issued.</p>
                                    </div>
                                </div>
                                
                                <div v-if="requestItem.status === 'rejected'" class="flex items-start gap-2 sm:gap-3">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-red-100 rounded-full flex items-center justify-center mt-0.5">
                                        <i class="pi pi-times text-red-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-900">Rejected</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Please review the rejection reason and contact admin if needed.</p>
                                    </div>
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
:deep(.p-card-body) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-badge) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-badge) {
        font-size: 0.875rem;
    }
}

:deep(.p-button) {
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-button) {
        font-size: 1rem;
    }
}

:deep(.p-progressbar) {
    height: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-progressbar) {
        height: 1rem;
    }
}

:deep(.p-progressbar-success .p-progressbar-value) {
    background-color: #22c55e;
}

:deep(.p-progressbar-warning .p-progressbar-value) {
    background-color: #f59e0b;
}

:deep(.p-progressbar-danger .p-progressbar-value) {
    background-color: #ef4444;
}

/* Responsive text utilities */
@media (max-width: 640px) {
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
}
</style>