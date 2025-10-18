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
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

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
const currentPerPage = ref(props.requests?.per_page || 10);

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
watch([search, statusFilter, priorityFilter], ([newSearch, newStatus, newPriority]) => {
    router.get(route("admin.manage-request-items.index"), {
        search: newSearch,
        status: newStatus,
        priority: newPriority,
        page: 1,
        per_page: currentPerPage.value
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.manage-request-items.index"), {
        search: search.value,
        status: statusFilter.value,
        priority: priorityFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
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
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
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
</script>

<template>
    <Head title="Manage Requests" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 md:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-2 sm:mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-xs sm:text-sm">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Stationary Requests Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Review and manage all stationary item requests from users</p>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-xs text-gray-500">Pending Requests</div>
                    <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-500">{{ statistics.pending }}</div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-orange-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'pending'">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Pending Review</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.pending }}</p>
                                <p class="text-xs text-gray-400 mt-1">Needs attention</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'approved'">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Approved</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.approved }}</p>
                                <p class="text-xs text-gray-400 mt-1">Ready for completion</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = ''">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Requests</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                                <p class="text-xs text-gray-400 mt-1">All time</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                      @click="statusFilter = 'completed'">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Completed</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.completed }}</p>
                                <p class="text-xs text-gray-400 mt-1">Fulfilled</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-check-square"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="shadow-lg">
                <template #content>
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800">All Requests</h2>
                        
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            <div class="w-full sm:w-48">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Status" class="w-full text-xs sm:text-sm" />
                            </div>
                            <div class="w-full sm:w-48">
                                <Select v-model="priorityFilter" :options="priorityOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Priority" class="w-full text-xs sm:text-sm" />
                            </div>
                            <IconField iconPosition="left" class="w-full sm:flex-1">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="search" placeholder="Search requests, users..." 
                                    class="w-full text-xs sm:text-sm" />
                            </IconField>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Requests Table -->
            <Card class="shadow-lg">
                <template #content>
                    <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="requests.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="requests.per_page" 
                            :totalRecords="requests.total"
                            :first="(requests.current_page - 1) * requests.per_page" 
                            @page="onPageChange"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['user.name', 'user.email', 'purpose']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-inbox"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Requests Found</h3>
                                    <p class="text-xs sm:text-sm text-gray-500 text-center">No stationary requests match your current filters.</p>
                                </div>
                            </template>

                            <!-- Requestor Column -->
                            <Column header="Requestor" style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="pi pi-user text-blue-600 text-xs sm:text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-xs sm:text-sm text-gray-900 truncate">{{ slotProps.data.user?.name }}</div>
                                            <div class="text-xs text-gray-500 truncate">{{ slotProps.data.user?.email }}</div>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Request Details Column -->
                            <Column header="Request Details" style="min-width: 280px;">
                                <template #body="slotProps">
                                    <div>
                                        <div class="font-semibold text-xs sm:text-sm text-gray-900 mb-1 truncate">{{ slotProps.data.purpose }}</div>
                                        <div class="flex flex-col gap-1 sm:gap-2 text-xs sm:text-sm text-gray-600 mb-2">
                                            <span class="flex items-center gap-1">
                                                <i class="pi pi-box text-xs"></i>
                                                {{ getTotalItems(slotProps.data) }} items
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <i class="pi pi-money-bill text-xs"></i>
                                                {{ formatCurrency(getTotalCost(slotProps.data)) }}
                                            </span>
                                        </div>
                                        <div v-if="slotProps.data.status === 'pending'" class="flex items-center gap-2">
                                            <ProgressBar :value="getApprovalRate(slotProps.data)" 
                                                        :showValue="false"
                                                        class="h-1 sm:h-2 flex-1" />
                                            <span class="text-xs text-gray-500">{{ getApprovalRate(slotProps.data) }}%</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Priority & Urgency Column -->
                            <Column header="Priority & Urgency" style="min-width: 140px;">
                                <template #body="slotProps">
                                    <div class="space-y-1 sm:space-y-2">
                                        <Badge :value="slotProps.data.priority"
                                            :severity="getPrioritySeverity(slotProps.data.priority)"
                                            class="capitalize w-full justify-center text-xs sm:text-sm">
                                            <i :class="getPriorityIcon(slotProps.data.priority)" class="mr-1 text-xs"></i>
                                            {{ slotProps.data.priority }}
                                        </Badge>
                                        <div v-if="getUrgencyBadge(slotProps.data)">
                                            <Badge :value="getUrgencyBadge(slotProps.data).label"
                                                :severity="getUrgencyBadge(slotProps.data).severity"
                                                class="w-full justify-center text-xs">
                                                <i :class="getUrgencyBadge(slotProps.data).icon" class="mr-1 text-xs"></i>
                                                {{ getUrgencyBadge(slotProps.data).label }}
                                            </Badge>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Status Column -->
                            <Column header="Status" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Tag :value="getStatusText(slotProps.data.status)"
                                        :severity="getStatusSeverity(slotProps.data.status)"
                                        class="capitalize font-semibold text-xs sm:text-sm" />
                                </template>
                            </Column>

                            <!-- Timeline Column -->
                            <Column header="Timeline" style="min-width: 130px;">
                                <template #body="slotProps">
                                    <div class="text-xs space-y-1">
                                        <div class="flex justify-between gap-2">
                                            <span class="text-gray-500">Requested:</span>
                                            <span class="font-medium text-gray-900">{{ formatDate(slotProps.data.created_at) }}</span>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <span class="text-gray-500">Needed:</span>
                                            <span class="font-medium text-gray-900">{{ formatDate(slotProps.data.needed_by) }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Quick Actions Column -->
                            <Column header="Actions" style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="flex flex-col gap-1 sm:gap-2">
                                        <Button label="View" icon="pi pi-eye" severity="info" size="small"
                                            @click="router.get(route('admin.manage-request-items.show', slotProps.data.id))"
                                            class="w-full text-xs sm:text-sm" />

                                        <Button v-if="slotProps.data.status === 'approved'" 
                                            label="Complete" icon="pi pi-check-square" severity="help" size="small"
                                            @click="router.get(route('admin.manage-request-items.show', slotProps.data.id))"
                                            class="w-full text-xs sm:text-sm" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ requests.current_page }} of {{ Math.ceil(requests.total / requests.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Pending Requests Highlight Section -->
            <Card v-if="statistics.pending > 0 && statusFilter !== 'pending'" class="border-l-4 border-orange-500 shadow-lg">
                <template #content>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-gray-800">⚠️ Attention Required</h3>
                            <p class="text-xs sm:text-sm text-gray-600">You have {{ statistics.pending }} pending requests waiting for your review.</p>
                        </div>
                        <Button label="View Pending" icon="pi pi-arrow-right" severity="warning"
                            @click="statusFilter = 'pending'" size="small" class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </template>
            </Card>
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

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 0.875rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-odd) {
    background-color: #ffffff;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-even) {
    background-color: #f8f9fa;
}

:deep(.p-paginator) {
    flex-wrap: wrap;
    gap: 0.5rem;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 0 0 0.375rem 0.375rem;
}

@media (min-width: 640px) {
    :deep(.p-paginator) {
        padding: 1rem;
    }
}

:deep(.p-paginator .p-paginator-pages) {
    flex-wrap: wrap;
}

:deep(.p-paginator-current) {
    font-size: 0.75rem;
    align-self: center;
}

@media (min-width: 640px) {
    :deep(.p-paginator-current) {
        font-size: 0.875rem;
    }
}

:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        font-size: 0.875rem;
    }
}

:deep(.p-badge), :deep(.p-tag) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-badge), :deep(.p-tag) {
        font-size: 0.875rem;
    }
}

:deep(.p-progressbar) {
    height: 0.25rem;
}

@media (min-width: 640px) {
    :deep(.p-progressbar) {
        height: 0.5rem;
    }
}

@media (max-width: 640px) {
    :deep(.p-datatable .p-datatable-wrapper) {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    :deep(.p-button.p-button-icon-only) {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    :deep(.p-paginator .p-paginator-pages) {
        width: 100%;
    }
}
</style>