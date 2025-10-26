<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Menu from 'primevue/menu';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    claims: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', type: '' })
    },
    statistics: {
        type: Object,
        default: () => ({})
    },
    claimTypes: {
        type: Array,
        default: () => []
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    typeOptions: {
        type: Array,
        default: () => []
    },
    userRole: {
        type: String,
        default: 'approver'
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Claim Requests Management' }];

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "all");
const typeFilter = ref(props.filters?.type || "all");
const selectedClaims = ref([]);
const actionMenu = ref();
const currentPerPage = ref(props.claims?.per_page || 10);
const showBulkActionDialog = ref(false);
const bulkAction = ref('');
const bulkNotes = ref('');
const loading = ref(false);

// Action menu items
const actionItems = ref([
    {
        label: 'Bulk Actions',
        items: [
            {
                label: 'Approve Selected',
                icon: 'pi pi-check',
                command: () => openBulkActionDialog('approve')
            },
            {
                label: 'Reject Selected',
                icon: 'pi pi-times',
                command: () => openBulkActionDialog('reject')
            }
        ]
    }
]);

// Computed properties
const userRoleDisplay = computed(() => {
    return props.userRole === 'system-admin' ? 'System Administrator' : 'Approver';
});

const canSeeAllClaims = computed(() => {
    return props.userRole === 'system-admin';
});

const totalPendingClaims = computed(() => {
    return props.claimTypes.reduce((sum, type) => sum + type.pending, 0);
});

const hasSelectedClaims = computed(() => {
    return selectedClaims.value.length > 0;
});

// Watchers
watch([search, statusFilter, typeFilter], ([newSearch, newStatus, newType], [oldSearch, oldStatus, oldType]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newType !== oldType) {
        router.get(route("admin.claim-request.index"), {
            search: newSearch,
            status: newStatus === 'all' ? '' : newStatus,
            type: newType === 'all' ? '' : newType,
            page: 1,
            per_page: currentPerPage.value
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }
});

const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;

    router.get(route("admin.claim-request.index"), {
        search: search.value,
        status: statusFilter.value === 'all' ? '' : statusFilter.value,
        type: typeFilter.value === 'all' ? '' : typeFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const getStatusSeverity = (status) => {
    const statusMap = {
        draft: 'secondary',
        submitted: 'warning',
        approved: 'success',
        rejected: 'danger',
        paid: 'info'
    };
    return statusMap[status] || 'secondary';
};

const getStatusText = (status) => {
    const statusMap = {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        rejected: 'Rejected',
        paid: 'Paid'
    };
    return statusMap[status] || status;
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

const viewClaim = (claim) => {
    const routeMap = {
        'travel': 'admin.manage.claim-request.travel.show',
        'daily': 'admin.manage.claim-request.daily.show',
        'accommodation': 'admin.manage.claim-request.accommodation.show',
        'transportation': 'admin.manage.claim-request.transportation.show'
    };

    const routeName = routeMap[claim.type];
    if (routeName) {
        router.get(route(routeName, claim.id));
    } else {
        // Fallback to generic show route
        router.get(route('admin.claim-request.show', claim.id));
    }
};

const openBulkActionDialog = (action) => {
    if (selectedClaims.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select claims first',
            life: 3000
        });
        return;
    }

    bulkAction.value = action;
    bulkNotes.value = '';
    showBulkActionDialog.value = true;
};

const submitBulkAction = () => {
    if (bulkAction.value === 'reject' && !bulkNotes.value.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Notes Required',
            detail: 'Please provide rejection notes',
            life: 3000
        });
        return;
    }

    loading.value = true;

    router.post(route('manage.claim-request.bulk-action'), {
        action: bulkAction.value,
        claim_ids: selectedClaims.value.map(claim => claim.id),
        notes: bulkNotes.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (response) => {
            toast.add({
                severity: "success",
                summary: "Bulk Action Completed",
                detail: response.props.flash.success || `${selectedClaims.value.length} claim(s) processed successfully`,
                life: 5000,
            });
            selectedClaims.value = [];
            showBulkActionDialog.value = false;
            loading.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Bulk Action Failed",
                detail: errors.message || "Failed to process claims",
                life: 5000,
            });
            loading.value = false;
        }
    });
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};

const getClaimTypeIcon = (type) => {
    const typeMap = {
        'travel': 'pi pi-car',
        'daily': 'pi pi-wallet',
        'accommodation': 'pi pi-building',
        'transportation': 'pi pi-map-marker'
    };
    return typeMap[type] || 'pi pi-file';
};

const getClaimTypeColor = (type) => {
    const typeMap = {
        'travel': 'text-blue-600',
        'daily': 'text-green-600',
        'accommodation': 'text-purple-600',
        'transportation': 'text-orange-600'
    };
    return typeMap[type] || 'text-gray-600';
};

const getClaimTypeBgColor = (type) => {
    const typeMap = {
        'travel': 'bg-blue-100',
        'daily': 'bg-green-100',
        'accommodation': 'bg-purple-100',
        'transportation': 'bg-orange-100'
    };
    return typeMap[type] || 'bg-gray-100';
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    typeFilter.value = 'all';
};
</script>

<template>

    <Head title="Manage Claim Requests" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />
        <Menu ref="actionMenu" :model="actionItems" :popup="true" />

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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Claim Requests Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">
                        Manage and approve user claim requests
                        <Badge :value="userRoleDisplay" severity="info" class="ml-2 text-xs" />
                    </p>
                    <div v-if="!canSeeAllClaims" class="mt-1">
                        <p class="text-xs text-blue-600">
                            <i class="pi pi-info-circle mr-1"></i>
                            You can only view claims assigned to you for approval
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" :disabled="!hasSelectedClaims"
                        class="flex-1 min-w-fit text-xs sm:text-sm" />
                    <Button label="Clear Filters" icon="pi pi-filter-slash" severity="secondary" text
                        @click="clearFilters" class="flex-1 min-w-fit text-xs sm:text-sm" />
                </div>
            </div>

            <!-- Claim Type Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div v-for="claimType in claimTypes" :key="claimType.id"
                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                    :class="{ 'ring-2 ring-blue-500': typeFilter === claimType.id }"
                    @click="typeFilter = claimType.id; statusFilter = 'all'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div :class="`p-2 rounded-lg ${getClaimTypeBgColor(claimType.id)}`">
                                <i :class="`${claimType.icon} ${getClaimTypeColor(claimType.id)} text-lg`"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-sm">{{ claimType.name }}</h3>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ claimType.description }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-900">{{ claimType.total }}</div>
                            <div class="text-xs text-yellow-600 font-medium" v-if="claimType.pending > 0">
                                {{ claimType.pending }} pending
                            </div>
                            <div class="text-xs text-gray-500" v-else>
                                No pending
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 xs:grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                <Card class="border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500">Total Claims</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ statistics.total }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-sm hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500">Pending</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ statistics.pending }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-sm hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500">Approved</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ statistics.approved }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-sm hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500">Rejected</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ statistics.rejected }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-gray-500 shadow-sm hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500">Draft</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ statistics.draft }}</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-3">
                            <div class="flex flex-wrap gap-1 sm:gap-2 w-full sm:w-auto">
                                <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                                    @click="toggleActionMenu" :disabled="!hasSelectedClaims"
                                    class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <span v-if="hasSelectedClaims"
                                    class="text-xs text-blue-600 font-medium flex items-center">
                                    {{ selectedClaims.length }} selected
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                                <div class="w-full sm:w-48">
                                    <Select v-model="statusFilter" :options="statusOptions" optionLabel="label"
                                        optionValue="value" placeholder="Filter by status"
                                        class="w-full text-xs sm:text-sm" />
                                </div>
                                <div class="w-full sm:w-48">
                                    <Select v-model="typeFilter" :options="typeOptions" optionLabel="label"
                                        optionValue="value" placeholder="Filter by type"
                                        class="w-full text-xs sm:text-sm" />
                                </div>
                                <div class="w-full sm:w-64 md:w-80">
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText v-model="search" placeholder="Search claims..."
                                            class="w-full text-xs sm:text-sm" />
                                    </IconField>
                                </div>
                            </div>
                        </div>

                        <!-- Active Filters -->
                        <div v-if="search || statusFilter !== 'all' || typeFilter !== 'all'"
                            class="flex flex-wrap gap-2 items-center p-2 bg-gray-50 rounded-lg">
                            <span class="text-xs font-medium text-gray-700">Active Filters:</span>
                            <Badge v-if="search" :value="`Search: ${search}`" severity="info" class="text-xs" />
                            <Badge v-if="statusFilter !== 'all'"
                                :value="`Status: ${statusOptions.find(s => s.value === statusFilter)?.label}`"
                                severity="warning" class="text-xs" />
                            <Badge v-if="typeFilter !== 'all'"
                                :value="`Type: ${typeOptions.find(t => t.value === typeFilter)?.label}`"
                                severity="success" class="text-xs" />
                            <Button icon="pi pi-times" severity="secondary" text rounded size="small"
                                @click="clearFilters" v-tooltip="'Clear all filters'" />
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="claims.data" showGridlines stripedRows :rowHover="true" paginator
                            :rows="claims.per_page" :totalRecords="claims.total"
                            :first="(claims.current_page - 1) * claims.per_page" @page="onPageChange"
                            v-model:selection="selectedClaims" dataKey="id" :rowsPerPageOptions="[5, 10, 20, 50, 100]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} claims"
                            responsiveLayout="scroll" class="p-datatable-custom" :loading="loading"
                            :globalFilterFields="['purpose', 'user.name', 'user.email']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-file"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No
                                        Claims Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">
                                        No claim requests match your search criteria.
                                    </p>
                                    <Button label="Clear Filters" icon="pi pi-filter-slash" severity="secondary"
                                        outlined @click="clearFilters" class="text-sm" />
                                </div>
                            </template>

                            <template #loading>
                                <div class="flex items-center justify-center py-8">
                                    <i class="pi pi-spin pi-spinner text-2xl text-blue-500 mr-2"></i>
                                    <span class="text-gray-600">Loading claims...</span>
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(claims.current_page - 1) * claims.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="user.name" header="User" sortable style="min-width: 180px;">
                                <template #body="slotProps">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="pi pi-user text-blue-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">
                                                {{ slotProps.data.user?.name || 'Unknown User' }}
                                            </div>
                                            <div class="text-xs text-gray-500 break-all">
                                                {{ slotProps.data.user?.email || 'No email' }}
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Claim Type" sortable style="min-width: 140px;">
                                <template #body="slotProps">
                                    <div class="flex items-center space-x-2">
                                        <i
                                            :class="`${getClaimTypeIcon(slotProps.data.type)} ${getClaimTypeColor(slotProps.data.type)} text-sm`"></i>
                                        <span class="text-xs sm:text-sm font-medium text-gray-900">
                                            {{ slotProps.data.type_display }}
                                        </span>
                                    </div>
                                </template>
                            </Column>

                            <Column field="purpose" header="Purpose" sortable style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-900 line-clamp-2">
                                        {{ slotProps.data.purpose || 'No purpose specified' }}
                                    </div>
                                    <div v-if="slotProps.data.details"
                                        class="text-xs text-gray-500 mt-1 flex flex-wrap gap-1">
                                        <Badge v-for="(value, key) in slotProps.data.details" :key="key" :value="value"
                                            severity="secondary" class="text-xs" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Amount" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm font-semibold text-blue-600">
                                        {{ formatCurrency(slotProps.data.amount) }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data.status)"
                                        :severity="getStatusSeverity(slotProps.data.status)"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 140px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View Details'" @click="viewClaim(slotProps.data)"
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Bulk Action Dialog -->
            <Dialog v-model:visible="showBulkActionDialog" modal
                :header="bulkAction === 'approve' ? 'Approve Selected Claims' : 'Reject Selected Claims'"
                :style="{ width: '95vw', maxWidth: '500px' }" :closable="!loading">

                <div class="space-y-4">
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="text-sm font-medium text-blue-800">Selected Claims:</div>
                        <div class="text-lg font-semibold">{{ selectedClaims.length }} claim(s)</div>
                        <div class="text-xs text-blue-600 mt-1">
                            Total amount: {{formatCurrency(selectedClaims.reduce((sum, claim) => sum + (claim.amount ||
                                0), 0))}}
                        </div>
                    </div>

                    <div v-if="bulkAction === 'reject'" class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Rejection Notes *</label>
                        <Textarea v-model="bulkNotes" placeholder="Enter reason for rejection..." rows="3"
                            class="w-full" :disabled="loading" />
                        <small class="text-gray-500 text-xs">
                            Required for rejection. This will be visible to the users.
                        </small>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showBulkActionDialog = false"
                            :disabled="loading" class="text-sm" />
                        <Button :label="bulkAction === 'approve' ? 'Approve All' : 'Reject All'"
                            :icon="bulkAction === 'approve' ? 'pi pi-check' : 'pi pi-times'"
                            :severity="bulkAction === 'approve' ? 'success' : 'danger'" @click="submitBulkAction"
                            :loading="loading" class="text-sm" />
                    </div>
                </div>
            </Dialog>
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    font-weight: 600;
    color: #374151;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f9fafb;
}
</style>