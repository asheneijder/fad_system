<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Card from "primevue/card";
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Breadcrumb from 'primevue/breadcrumb';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import ProgressBar from 'primevue/progressbar';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

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
    { label: 'Manage Requests', url: route('admin.manage-request-items.index') },
    { label: 'Request Details' }
];

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

// Forms
const approveForm = useForm({
    approved_quantities: {},
    notes: props.requestItem.notes || '',
});

const rejectForm = useForm({
    rejection_reason: '',
});

// Initialize approved quantities with proper null checking
const initializeApprovedQuantities = () => {
    if (!props.requestItem.items) return;
    
    props.requestItem.items.forEach(item => {
        approveForm.approved_quantities[item.id] = item.approved_quantity || item.quantity;
    });
};

// Watch for requestItem changes and initialize when items are available
watch(() => props.requestItem, (newRequestItem) => {
    if (newRequestItem.items) {
        initializeApprovedQuantities();
    }
}, { immediate: true, deep: true });

// Computed properties with proper null checking
const totalRequested = computed(() => {
    if (!props.requestItem.items) return 0;
    return props.requestItem.items.reduce((sum, item) => sum + item.quantity, 0);
});

const totalApproved = computed(() => {
    return Object.values(approveForm.approved_quantities).reduce((sum, qty) => sum + (qty || 0), 0);
});

const totalCost = computed(() => {
    if (!props.requestItem.items) return 0;
    return props.requestItem.items.reduce((sum, item) => {
        const quantity = approveForm.approved_quantities[item.id] || item.quantity;
        return sum + (quantity * item.unit_price);
    }, 0);
});

const approvalRate = computed(() => {
    if (totalRequested.value === 0) return 100;
    return Math.round((totalApproved.value / totalRequested.value) * 100);
});

// Quick action methods with null checking
const approveAllItems = () => {
    if (!props.requestItem.items) return;
    props.requestItem.items.forEach(item => {
        approveForm.approved_quantities[item.id] = item.quantity;
    });
};

const approveAvailableOnly = () => {
    if (!props.requestItem.items) return;
    props.requestItem.items.forEach(item => {
        const availableStock = item.stationary_item?.current_stock || 0;
        approveForm.approved_quantities[item.id] = Math.min(item.quantity, availableStock);
    });
};

const resetToRequested = () => {
    if (!props.requestItem.items) return;
    props.requestItem.items.forEach(item => {
        approveForm.approved_quantities[item.id] = item.quantity;
    });
};

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

const submitApprove = () => {
    approveForm.post(route('admin.manage-request-items.approve', props.requestItem.id), {
        onSuccess: () => {
            showApproveDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Request approved successfully',
                life: 3000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to approve request',
                life: 3000
            });
        }
    });
};

const submitReject = () => {
    rejectForm.post(route('admin.manage-request-items.reject', props.requestItem.id), {
        onSuccess: () => {
            showRejectDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Request rejected successfully',
                life: 3000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.rejection_reason?.[0] || 'Failed to reject request',
                life: 3000
            });
        }
    });
};

const completeRequest = () => {
    confirm.require({
        message: "Are you sure you want to mark this request as completed? This will deduct items from stock.",
        header: "Complete Request Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-success",
        accept: () => {
            router.post(route('admin.manage-request-items.complete', props.requestItem.id), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Completed",
                        detail: "Request marked as completed and stock updated",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to complete request",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const updateQuantities = () => {
    const form = useForm({
        approved_quantities: approveForm.approved_quantities
    });

    form.post(route('admin.manage-request-items.update-quantities', props.requestItem.id), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Quantities updated successfully',
                life: 3000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update quantities',
                life: 3000
            });
        }
    });
};
</script>

<template>
    <Head :title="`Request #${requestItem.id} Details`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:underline" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header with Actions -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Request #{{ requestItem.id }}</h1>
                    <div class="flex items-center gap-4 mt-2">
                        <Badge :value="getStatusText(requestItem.status)" 
                               :severity="getStatusSeverity(requestItem.status)"
                               class="text-sm" />
                        <Badge :value="requestItem.priority" 
                               :severity="getPrioritySeverity(requestItem.priority)"
                               class="text-sm capitalize">
                            <i :class="getPriorityIcon(requestItem.priority)" class="mr-1 text-xs"></i>
                            {{ requestItem.priority }}
                        </Badge>
                        <span class="text-sm text-gray-500">
                            Requested by {{ requestItem.user?.name }} on {{ formatDate(requestItem.created_at) }}
                        </span>
                    </div>
                </div>
                
                <div class="flex gap-3" v-if="requestItem.status === 'pending'">
                    <Button label="Approve Request" icon="pi pi-check" severity="success"
                        @click="showApproveDialog = true" />
                    <Button label="Reject Request" icon="pi pi-times" severity="danger" outlined
                        @click="showRejectDialog = true" />
                </div>
                
                <div class="flex gap-3" v-else-if="requestItem.status === 'approved'">
                    <Button label="Mark as Completed" icon="pi pi-check-square" severity="help"
                        @click="completeRequest" />
                </div>

                <Button label="Back to List" icon="pi pi-arrow-left" severity="secondary" outlined
                    @click="router.get(route('admin.manage-request-items.index'))" />
            </div>

            <!-- Request Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ requestItem.items?.length || 0 }}</div>
                            <div class="text-sm text-blue-700">Items Requested</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-green-50 border-green-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ totalRequested }}</div>
                            <div class="text-sm text-green-700">Total Quantity</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-purple-50 border-purple-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ formatCurrency(totalCost) }}</div>
                            <div class="text-sm text-purple-700">Total Value</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-orange-50 border-orange-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600" v-if="getUrgencyStatus().severity !== 'secondary'">
                                {{ getUrgencyStatus().label }}
                            </div>
                            <div class="text-2xl font-bold text-gray-600" v-else>—</div>
                            <div class="text-sm text-orange-700">Time Remaining</div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Request Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Purpose and Notes -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Purpose</label>
                                    <p class="mt-1 text-gray-900 text-lg">{{ requestItem.purpose }}</p>
                                </div>
                                <div v-if="requestItem.notes">
                                    <label class="block text-sm font-medium text-gray-500">Additional Notes</label>
                                    <p class="mt-1 text-gray-900 whitespace-pre-wrap bg-gray-50 p-3 rounded">{{ requestItem.notes }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Priority</label>
                                        <Badge :value="requestItem.priority" 
                                               :severity="getPrioritySeverity(requestItem.priority)"
                                               class="mt-1 capitalize">
                                            <i :class="getPriorityIcon(requestItem.priority)" class="mr-1 text-xs"></i>
                                            {{ requestItem.priority }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Needed By</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <p class="text-gray-900">{{ formatDate(requestItem.needed_by) }}</p>
                                            <Badge v-if="getUrgencyStatus().severity !== 'secondary'"
                                                :value="getUrgencyStatus().label"
                                                :severity="getUrgencyStatus().severity"
                                                class="text-xs">
                                                <i :class="getUrgencyStatus().icon" class="mr-1"></i>
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="requestItem.rejection_reason" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <label class="block text-sm font-medium text-red-700">Rejection Reason</label>
                                    <p class="mt-1 text-red-600">{{ requestItem.rejection_reason }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Requested Items -->
                    <Card class="shadow-lg">
                        <template #content>
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-800">Requested Items</h3>
                                <div class="flex gap-2">
                                    <Button v-if="requestItem.status === 'pending'" 
                                        label="Update Quantities" icon="pi pi-refresh" severity="secondary" size="small"
                                        @click="updateQuantities" />
                                </div>
                            </div>
                            
                            <!-- Loading State -->
                            <div v-if="!requestItem.items" class="text-center py-8">
                                <div class="p-6 mb-4 bg-gray-100 rounded-full inline-block">
                                    <i class="text-4xl text-gray-400 pi pi-spin pi-spinner"></i>
                                </div>
                                <p class="text-gray-500">Loading items...</p>
                            </div>
                            
                            <!-- Items List -->
                            <div v-else class="space-y-4">
                                <div v-for="item in requestItem.items" :key="item.id" 
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 text-lg">{{ item.stationary_item?.name }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ item.stationary_item?.description }}</p>
                                            <p class="text-sm text-gray-500 mt-2">
                                                <i class="pi pi-box text-blue-500 mr-1"></i>
                                                Stock: {{ item.stationary_item?.current_stock }} {{ item.stationary_item?.unit }} • 
                                                <i class="pi pi-dollar text-green-500 mr-1 ml-2"></i>
                                                Price: {{ formatCurrency(item.unit_price) }}
                                            </p>
                                        </div>
                                        <Badge v-if="item.stationary_item?.current_stock < item.quantity" 
                                            value="Low Stock" severity="warning" />
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div class="text-center p-3 bg-gray-50 rounded">
                                            <label class="block text-sm font-medium text-gray-500 mb-1">Requested</label>
                                            <p class="font-semibold text-gray-900 text-lg">{{ item.quantity }}</p>
                                            <p class="text-xs text-gray-500">{{ item.stationary_item?.unit }}</p>
                                        </div>
                                        <div class="text-center p-3 bg-gray-50 rounded">
                                            <label class="block text-sm font-medium text-gray-500 mb-1">Available</label>
                                            <p class="font-semibold text-lg" :class="{
                                                'text-green-600': item.stationary_item?.current_stock >= item.quantity,
                                                'text-red-600': item.stationary_item?.current_stock < item.quantity
                                            }">
                                                {{ item.stationary_item?.current_stock }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ item.stationary_item?.unit }}</p>
                                        </div>
                                        <div class="text-center p-3 bg-blue-50 rounded">
                                            <label class="block text-sm font-medium text-gray-500 mb-1">
                                                Approved {{ requestItem.status === 'pending' ? '*' : '' }}
                                            </label>
                                            <div class="flex items-center justify-center gap-2">
                                                <InputNumber v-if="requestItem.status === 'pending'"
                                                    v-model="approveForm.approved_quantities[item.id]"
                                                    :min="0" 
                                                    :max="Math.min(item.quantity, item.stationary_item?.current_stock || 0)"
                                                    class="w-20"
                                                    size="small" />
                                                <span v-else class="font-semibold text-gray-900 text-lg">
                                                    {{ item.approved_quantity || item.quantity }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ item.stationary_item?.unit }}</p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="item.notes" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                        <label class="block text-sm font-medium text-yellow-700">Item Notes</label>
                                        <p class="text-sm text-yellow-600 mt-1">{{ item.notes }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Summary -->
                            <div v-if="requestItem.items" class="border-t mt-6 pt-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-500">Total Requested</span>
                                        <span class="block text-2xl font-bold text-gray-900 mt-1">{{ totalRequested }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-500">Total Approved</span>
                                        <span class="block text-2xl font-bold text-blue-600 mt-1">{{ totalApproved }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-500">Total Cost</span>
                                        <span class="block text-2xl font-bold text-green-600 mt-1">{{ formatCurrency(totalCost) }}</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="flex justify-between items-center text-sm mb-2">
                                        <span class="font-medium">Approval Rate</span>
                                        <span class="font-bold">{{ approvalRate }}%</span>
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
                <div class="space-y-6">
                    <!-- Request Summary -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Summary</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Status</label>
                                    <Badge :value="getStatusText(requestItem.status)" 
                                           :severity="getStatusSeverity(requestItem.status)"
                                           class="mt-1 text-base" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Requested By</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="pi pi-user text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ requestItem.user?.name }}</p>
                                            <p class="text-sm text-gray-500">{{ requestItem.user?.email }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1 font-medium text-gray-900">{{ requestItem.user?.department || '—' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Request Date</label>
                                    <p class="mt-1 font-medium text-gray-900">{{ formatDate(requestItem.created_at) }}</p>
                                </div>
                                <div v-if="requestItem.approved_by">
                                    <label class="block text-sm font-medium text-gray-500">Approved By</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="pi pi-user-check text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ requestItem.approved_by?.name }}</p>
                                            <p class="text-sm text-gray-500">{{ formatDate(requestItem.approved_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-lg" v-if="requestItem.status === 'pending' && requestItem.items">
                        <template #content>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <Button label="Approve All Items" icon="pi pi-check" severity="success" 
                                    @click="approveAllItems"
                                    class="w-full" outlined />
                                <Button label="Approve Available Only" icon="pi pi-filter" severity="warning" 
                                    @click="approveAvailableOnly"
                                    class="w-full" outlined />
                                <Button label="Reset to Requested" icon="pi pi-refresh" severity="secondary" 
                                    @click="resetToRequested"
                                    class="w-full" outlined />
                            </div>
                        </template>
                    </Card>

                    <!-- Approval Statistics -->
                    <Card class="shadow-lg">
                        <template #content>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Approval Summary</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Items:</span>
                                    <span class="font-medium">{{ requestItem.items?.length || 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Requested Qty:</span>
                                    <span class="font-medium">{{ totalRequested }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Approved Qty:</span>
                                    <span class="font-medium text-blue-600">{{ totalApproved }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Reduction:</span>
                                    <span class="font-medium" :class="{
                                        'text-green-600': totalApproved === totalRequested,
                                        'text-yellow-600': totalApproved < totalRequested && totalApproved > 0,
                                        'text-red-600': totalApproved === 0
                                    }">
                                        {{ totalRequested - totalApproved }} items
                                    </span>
                                </div>
                                <div class="pt-2 border-t">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 font-semibold">Approval Rate:</span>
                                        <span class="font-bold text-lg" :class="{
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
                </div>
            </div>

            <!-- Approve Dialog -->
            <Dialog v-model:visible="showApproveDialog" modal header="Approve Request" :style="{ width: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-4">
                    <p class="text-gray-700">You are about to approve this request. Please review the approved quantities and add any final notes.</p>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Admin Notes (Optional)</label>
                        <Textarea v-model="approveForm.notes" rows="3" 
                            placeholder="Add any notes for the requester..."
                            class="w-full" />
                    </div>

                    <div class="border rounded-lg p-4 bg-gray-50">
                        <h4 class="font-semibold text-gray-900 mb-2">Approval Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Total Items:</span>
                                <span class="font-medium">{{ requestItem.items?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Requested Quantity:</span>
                                <span class="font-medium">{{ totalRequested }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Approved Quantity:</span>
                                <span class="font-medium text-blue-600">{{ totalApproved }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Approval Rate:</span>
                                <span class="font-medium">{{ approvalRate }}%</span>
                            </div>
                            <div class="flex justify-between text-lg font-semibold border-t pt-2 mt-2">
                                <span>Total Cost:</span>
                                <span class="text-blue-600">{{ formatCurrency(totalCost) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showApproveDialog = false"
                            :disabled="approveForm.processing" />
                        <Button label="Approve Request" icon="pi pi-check" severity="success" 
                            @click="submitApprove" :loading="approveForm.processing" />
                    </div>
                </template>
            </Dialog>

            <!-- Reject Dialog -->
            <Dialog v-model:visible="showRejectDialog" modal header="Reject Request" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-4">
                    <p class="text-gray-700">You are about to reject this request. Please provide a reason for rejection.</p>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">
                            Rejection Reason <span class="text-red-500">*</span>
                        </label>
                        <Textarea v-model="rejectForm.rejection_reason" rows="3" 
                            placeholder="Explain why this request is being rejected..."
                            class="w-full"
                            :class="{ 'p-invalid': rejectForm.errors.rejection_reason }" />
                        <small class="text-red-500 text-xs" v-if="rejectForm.errors.rejection_reason">
                            {{ rejectForm.errors.rejection_reason }}
                        </small>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showRejectDialog = false"
                            :disabled="rejectForm.processing" />
                        <Button label="Reject Request" icon="pi pi-times" severity="danger" 
                            @click="submitReject" :loading="rejectForm.processing" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>