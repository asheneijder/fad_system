<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Card from "primevue/card";
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    requests: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    statistics: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', priority: '' })
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Manage Requests' }];

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const priorityFilter = ref(props.filters?.priority || "");

// Filter options
const statusOptions = ref([
    { label: 'All Status', value: '' },
    { label: '🟡 Pending', value: 'pending' },
    { label: '🟢 Approved', value: 'approved' },
    { label: '🔴 Rejected', value: 'rejected' },
    { label: '🔵 Completed', value: 'completed' },
]);

const priorityOptions = ref([
    { label: 'All Priorities', value: '' },
    { label: '🟢 Low', value: 'low' },
    { label: '🟡 Medium', value: 'medium' },
    { label: '🟠 High', value: 'high' },
    { label: '🔴 Urgent', value: 'urgent' },
]);

// Watchers for filters
watch([search, statusFilter, priorityFilter], ([newSearch, newStatus, newPriority], [oldSearch, oldStatus, oldPriority]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newPriority !== oldPriority) {
        router.get(route("admin.manage-request-items.index"), {
            search: newSearch,
            status: newStatus,
            priority: newPriority
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("admin.manage-request-items.index"), {
        search: search.value,
        status: statusFilter.value,
        priority: priorityFilter.value,
        page: page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

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

const getTotalItems = (request) => {
    return request.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
};

const getTotalCost = (request) => {
    return request.items?.reduce((sum, item) => {
        const quantity = item.approved_quantity || item.quantity;
        return sum + (quantity * item.unit_price);
    }, 0) || 0;
};

const getApprovalRate = (request) => {
    const totalRequested = request.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
    const totalApproved = request.items?.reduce((sum, item) => sum + (item.approved_quantity || item.quantity), 0) || 0;
    
    if (totalRequested === 0) return 100;
    return Math.round((totalApproved / totalRequested) * 100);
};

const getDaysUntilNeeded = (neededBy) => {
    if (!neededBy) return null;
    const today = new Date();
    const neededDate = new Date(neededBy);
    const diffTime = neededDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getUrgencyBadge = (request) => {
    const daysUntilNeeded = getDaysUntilNeeded(request.needed_by);
    
    if (daysUntilNeeded === null) return null;
    
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

const quickApprove = (requestId) => {
    confirm.require({
        message: "Are you sure you want to approve this request with all requested quantities?",
        header: "Quick Approve Confirmation",
        icon: "pi pi-check-circle",
        acceptClass: "p-button-success",
        accept: () => {
            router.post(route('admin.manage-request-items.approve', requestId), {
                approved_quantities: {},
                notes: 'Quick approved by admin'
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Approved",
                        detail: "Request approved successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to approve request",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const quickReject = (requestId) => {
    confirm.require({
        message: "Are you sure you want to reject this request?",
        header: "Quick Reject Confirmation",
        icon: "pi pi-times-circle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.post(route('admin.manage-request-items.reject', requestId), {
                rejection_reason: 'Rejected by admin'
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Rejected",
                        detail: "Request rejected successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to reject request",
                        life: 3000,
                    });
                }
            });
        },
    });
};
</script>

<template>
    <Head title="Manage Requests" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Stationary Requests Management</h1>
                    <p class="mt-1 text-gray-500">Review and manage all stationary item requests from users</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Pending Requests</div>
                    <div class="text-2xl font-bold text-orange-500">{{ statistics.pending }}</div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card class="border-l-4 border-orange-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'pending'">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Review</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.pending }}</p>
                                <p class="text-xs text-gray-400 mt-1">Needs attention</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="text-xl text-orange-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'approved'">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.approved }}</p>
                                <p class="text-xs text-gray-400 mt-1">Ready for completion</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = ''">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                                <p class="text-xs text-gray-400 mt-1">All time</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'completed'">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.completed }}</p>
                                <p class="text-xs text-gray-400 mt-1">Fulfilled</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-check-square"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="shadow-lg">
                <template #content>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <h2 class="text-xl font-bold text-gray-800">All Requests</h2>
                        
                        <div class="flex flex-col lg:flex-row gap-3">
                            <div class="w-full lg:w-48">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Status" class="w-full" />
                            </div>
                            <div class="w-full lg:w-48">
                                <Select v-model="priorityFilter" :options="priorityOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Priority" class="w-full" />
                            </div>
                            <div class="w-full lg:w-80">
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search requests, users..." class="w-full" />
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Requests Table -->
            <Card class="shadow-lg">
                <template #content>
                    <DataTable :value="requests.data" showGridlines stripedRows
                        :rowHover="true" paginator :rows="requests.per_page" :totalRecords="requests.total"
                        :first="(requests.current_page - 1) * requests.per_page" @page="onPageChange"
                        responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                    <i class="text-6xl text-gray-400 pi pi-inbox"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-700">No Requests Found</h3>
                                <p class="text-gray-500">No stationary requests match your current filters.</p>
                            </div>
                        </template>

                        <!-- Requestor Column -->
                        <Column header="Requestor" style="min-width: 200px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ slotProps.data.user?.name }}</div>
                                        <div class="text-sm text-gray-500">{{ slotProps.data.user?.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Request Details Column -->
                        <Column header="Request Details" style="min-width: 300px;">
                            <template #body="slotProps">
                                <div>
                                    <div class="font-semibold text-gray-900 mb-1">{{ slotProps.data.purpose }}</div>
                                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-2">
                                        <span class="flex items-center gap-1">
                                            <i class="pi pi-box"></i>
                                            {{ getTotalItems(slotProps.data) }} items
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="pi pi-dollar"></i>
                                            {{ formatCurrency(getTotalCost(slotProps.data)) }}
                                        </span>
                                    </div>
                                    <div v-if="slotProps.data.status === 'pending'" class="flex items-center gap-2">
                                        <ProgressBar :value="getApprovalRate(slotProps.data)" 
                                                    :showValue="false"
                                                    class="h-2 flex-1" />
                                        <span class="text-xs text-gray-500">{{ getApprovalRate(slotProps.data) }}%</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Priority & Urgency Column -->
                        <Column header="Priority & Urgency" style="width: 180px;">
                            <template #body="slotProps">
                                <div class="space-y-2">
                                    <Badge :value="slotProps.data.priority"
                                        :severity="getPrioritySeverity(slotProps.data.priority)"
                                        class="capitalize w-full justify-center">
                                        <i :class="getPriorityIcon(slotProps.data.priority)" class="mr-1 text-xs"></i>
                                        {{ slotProps.data.priority }}
                                    </Badge>
                                    <div v-if="getUrgencyBadge(slotProps.data)">
                                        <Badge :value="getUrgencyBadge(slotProps.data).label"
                                            :severity="getUrgencyBadge(slotProps.data).severity"
                                            class="w-full justify-center text-xs">
                                            <i :class="getUrgencyBadge(slotProps.data).icon" class="mr-1"></i>
                                            {{ getUrgencyBadge(slotProps.data).label }}
                                        </Badge>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Status Column -->
                        <Column header="Status" sortable style="width: 130px;">
                            <template #body="slotProps">
                                <Tag :value="getStatusText(slotProps.data.status)"
                                    :severity="getStatusSeverity(slotProps.data.status)"
                                    class="capitalize font-semibold" />
                            </template>
                        </Column>

                        <!-- Timeline Column -->
                        <Column header="Timeline" style="width: 150px;">
                            <template #body="slotProps">
                                <div class="text-sm space-y-1">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Requested:</span>
                                        <span class="font-medium">{{ formatDate(slotProps.data.created_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Needed:</span>
                                        <span class="font-medium">{{ formatDate(slotProps.data.needed_by) }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Quick Actions Column -->
                        <Column header="Actions" style="width: 180px;">
                            <template #body="slotProps">
                                <div class="flex flex-col gap-2">
                                    <!-- View Details Button -->
                                    <Button label="View Details" icon="pi pi-eye" severity="info" size="small"
                                        @click="router.get(route('admin.manage-request-items.show', slotProps.data.id))"
                                        class="w-full" />

                                    <!-- Quick Actions for Pending Requests -->
                                    <div v-if="slotProps.data.status === 'pending'" class="flex gap-1">
                                        <Button icon="pi pi-check" severity="success" size="small"
                                            v-tooltip.top="'Quick Approve'" 
                                            @click="quickApprove(slotProps.data.id)"
                                            class="flex-1" />
                                        <Button icon="pi pi-times" severity="danger" size="small"
                                            v-tooltip.top="'Quick Reject'" 
                                            @click="quickReject(slotProps.data.id)"
                                            class="flex-1" />
                                    </div>

                                    <!-- Complete Action for Approved Requests -->
                                    <Button v-if="slotProps.data.status === 'approved'" 
                                        label="Complete" icon="pi pi-check-square" severity="help" size="small"
                                        @click="router.get(route('admin.manage-request-items.show', slotProps.data.id))"
                                        class="w-full" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Pending Requests Highlight Section -->
            <Card v-if="statistics.pending > 0 && statusFilter !== 'pending'" class="border-l-4 border-orange-500 shadow-lg">
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">⚠️ Attention Required</h3>
                            <p class="text-gray-600">You have {{ statistics.pending }} pending requests waiting for your review.</p>
                        </div>
                        <Button label="View Pending Requests" icon="pi pi-arrow-right" severity="warning"
                            @click="statusFilter = 'pending'" />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>