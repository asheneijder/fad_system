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
import Card from "primevue/card";
import DatePicker from 'primevue/datepicker';
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 15, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Audit Logs' }];

const search = ref(props.filters?.search || "");
const startDate = ref(props.filters?.start_date ? new Date(props.filters.start_date) : null);
const endDate = ref(props.filters?.end_date ? new Date(props.filters.end_date) : null);
const logs = ref(props.logs);
const showLogDetails = ref(false);
const selectedLog = ref(null);
const loading = ref(false);

// Action type options for filter
const actionOptions = ref([
    { label: 'All Actions', value: '' },
    { label: 'Created', value: 'created' },
    { label: 'Updated', value: 'updated' },
    { label: 'Deleted', value: 'deleted' },
]);

// Watchers
watch(() => props.logs, (newLogs) => {
    logs.value = newLogs;
}, { immediate: true });

watch([search, startDate, endDate], ([newSearch, newStartDate, newEndDate], [oldSearch, oldStartDate, oldEndDate]) => {
    if (newSearch !== oldSearch || newStartDate !== oldStartDate || newEndDate !== oldEndDate) {
        loading.value = true;
        
        const filters = {
            search: newSearch,
            start_date: newStartDate ? newStartDate.toISOString().split('T')[0] : '',
            end_date: newEndDate ? newEndDate.toISOString().split('T')[0] : ''
        };

        router.get(route("admin.audit-logs.index"), filters, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            }
        });
    }
});

// Methods
const onPageChange = (event) => {
    loading.value = true;
    const page = event.page + 1;
    
    const filters = {
        search: search.value,
        start_date: startDate.value ? startDate.value.toISOString().split('T')[0] : '',
        end_date: endDate.value ? endDate.value.toISOString().split('T')[0] : '',
        page: page,
    };

    router.get(route("admin.audit-logs.index"), filters, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        }
    });
};

const viewLogDetails = (log) => {
    selectedLog.value = log;
    showLogDetails.value = true;
};

const closeLogDetails = () => {
    showLogDetails.value = false;
    selectedLog.value = null;
};

const getActionSeverity = (action) => {
    switch (action) {
        case 'created': return 'success';
        case 'updated': return 'warning';
        case 'deleted': return 'danger';
        default: return 'info';
    }
};

const getActionLabel = (description) => {
    const action = description.toLowerCase();
    if (action.includes('created')) return 'Created';
    if (action.includes('updated')) return 'Updated';
    if (action.includes('deleted')) return 'Deleted';
    return description;
};

const formatDate = (date) => {
    if (!date) return '—';
    try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return '—';
    }
};

const formatModelName = (model) => {
    if (!model) return '—';
    // Convert "App\Models\User" to "User"
    return model.split('\\').pop();
};

const getPropertyChanges = (log) => {
    if (!log.properties) return null;
    
    if (log.description === 'updated' && log.properties.attributes && log.properties.old) {
        return {
            old: log.properties.old,
            new: log.properties.attributes
        };
    }
    
    if (log.description === 'created' && log.properties.attributes) {
        return {
            new: log.properties.attributes
        };
    }
    
    if (log.description === 'deleted' && log.properties.old) {
        return {
            old: log.properties.old
        };
    }
    
    return log.properties;
};

const clearFilters = () => {
    search.value = "";
    startDate.value = null;
    endDate.value = null;
};
</script>

<template>
    <Head title="Audit Logs" />
    <AppLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Audit Logs</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Track all system activities and changes</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Total Logs</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">{{ logs.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full">
                                <i class="text-lg sm:text-xl text-blue-600 pi pi-history"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Created</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'created').length }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full">
                                <i class="text-lg sm:text-xl text-green-600 pi pi-plus-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Updated</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'updated').length }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full">
                                <i class="text-lg sm:text-xl text-yellow-600 pi pi-pencil"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Deleted</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'deleted').length }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full">
                                <i class="text-lg sm:text-xl text-red-600 pi pi-trash"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-4">
                        <div class="text-xs sm:text-sm text-gray-500">
                            Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of {{ logs.total }} audit logs
                        </div>

                        <div class="flex flex-col gap-3 sm:gap-4">
                            <!-- Date Range -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="w-full">
                                    <label class="block mb-1 text-xs sm:text-sm font-medium text-gray-700">Start Date</label>
                                    <DatePicker v-model="startDate" 
                                        placeholder="Start Date" 
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        class="w-full" />
                                </div>
                                <div class="w-full">
                                    <label class="block mb-1 text-xs sm:text-sm font-medium text-gray-700">End Date</label>
                                    <DatePicker v-model="endDate" 
                                        placeholder="End Date" 
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        class="w-full" />
                                </div>
                            </div>

                            <!-- Search and Clear -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block mb-1 text-xs sm:text-sm font-medium text-gray-700">Search</label>
                                    <span class="p-input-icon-left block w-full">
                                        <i class="pi pi-search" />
                                        <InputText v-model="search" 
                                            placeholder="Search logs, users, or models..." 
                                            class="w-full pl-10" />
                                    </span>
                                </div>

                                <!-- Clear Filters -->
                                <div class="flex items-end">
                                    <Button label="Clear" 
                                        icon="pi pi-filter-slash" 
                                        severity="secondary" 
                                        outlined
                                        @click="clearFilters"
                                        class="w-full sm:w-auto"
                                        size="small" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto">
                        <DataTable :value="logs.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="logs.per_page" :totalRecords="logs.total"
                            :first="(logs.current_page - 1) * logs.per_page" @page="onPageChange"
                            :loading="loading" dataKey="id"
                            responsiveLayout="scroll" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
                                    <div class="p-4 sm:p-6 mb-3 sm:mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-history"></i>
                                    </div>
                                    <h3 class="mb-2 text-lg sm:text-xl font-semibold text-gray-700">No Audit Logs Found</h3>
                                    <p class="mb-3 sm:mb-4 text-sm sm:text-base text-gray-500 text-center">Try adjusting your filters or perform some actions in the system.</p>
                                </div>
                            </template>

                            <!-- Loading State -->
                            <template #loading>
                                <div class="flex items-center justify-center py-6 sm:py-8">
                                    <i class="pi pi-spin pi-spinner text-xl sm:text-2xl text-blue-500"></i>
                                    <span class="ml-2 text-sm sm:text-base">Loading audit logs...</span>
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(logs.current_page - 1) * logs.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column header="Action" sortable field="description" style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getActionLabel(slotProps.data.description)"
                                        :severity="getActionSeverity(slotProps.data.description)"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Model" sortable field="subject_type" style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="font-medium text-sm sm:text-base text-gray-900">
                                        {{ formatModelName(slotProps.data.subject_type) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ slotProps.data.subject_id || 'N/A' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="User" sortable style="min-width: 180px;">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.causer">
                                        <div class="font-medium text-sm sm:text-base text-gray-900">
                                            {{ slotProps.data.causer.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 break-all">
                                            {{ slotProps.data.causer.email }}
                                        </div>
                                    </div>
                                    <Badge v-else value="System" severity="info" />
                                </template>
                            </Column>

                            <Column header="Changes" style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div v-if="slotProps.data.description === 'updated'" class="text-gray-600">
                                            {{ Object.keys(getPropertyChanges(slotProps.data)?.old || {}).length }} fields updated
                                        </div>
                                        <div v-else-if="slotProps.data.description === 'created'" class="text-gray-600">
                                            New record created
                                        </div>
                                        <div v-else-if="slotProps.data.description === 'deleted'" class="text-gray-600">
                                            Record deleted
                                        </div>
                                        <div v-else class="text-gray-600">
                                            {{ slotProps.data.description }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Timestamp" sortable field="created_at" style="min-width: 160px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                        v-tooltip.top="'View Details'" @click="viewLogDetails(slotProps.data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Log Details Dialog -->
            <Dialog v-model:visible="showLogDetails" modal header="Audit Log Details" 
                :style="{ width: '95vw', maxWidth: '800px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div v-if="selectedLog" class="space-y-4 sm:space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Action</p>
                            <Badge :value="getActionLabel(selectedLog.description)"
                                :severity="getActionSeverity(selectedLog.description)"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Model</p>
                            <p class="mt-1 text-sm sm:text-base font-semibold text-gray-900">
                                {{ formatModelName(selectedLog.subject_type) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Model ID</p>
                            <p class="mt-1 text-sm sm:text-base font-semibold text-gray-900">
                                {{ selectedLog.subject_id || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Timestamp</p>
                            <p class="mt-1 text-sm sm:text-base font-semibold text-gray-900">
                                {{ formatDate(selectedLog.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">User</p>
                            <div v-if="selectedLog.causer" class="mt-1">
                                <p class="text-sm sm:text-base font-semibold text-gray-900">{{ selectedLog.causer.name }}</p>
                                <p class="text-xs sm:text-sm text-gray-500 break-all">{{ selectedLog.causer.email }}</p>
                            </div>
                            <Badge v-else value="System" severity="info" class="mt-1" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">IP Address</p>
                            <p class="mt-1 text-sm sm:text-base font-semibold text-gray-900">
                                {{ selectedLog.properties?.ip_address || 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Property Changes -->
                    <div v-if="getPropertyChanges(selectedLog)">
                        <p class="mb-2 sm:mb-3 text-xs sm:text-sm font-medium text-gray-500">Property Changes</p>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <div class="min-w-[500px]">
                                <div v-if="selectedLog.description === 'updated'" class="grid grid-cols-3 text-xs sm:text-sm border-b border-gray-200 bg-gray-50">
                                    <div class="p-2 sm:p-3 font-medium">Field</div>
                                    <div class="p-2 sm:p-3 font-medium text-red-600">Old Value</div>
                                    <div class="p-2 sm:p-3 font-medium text-green-600">New Value</div>
                                </div>
                                <div v-else class="grid grid-cols-2 text-xs sm:text-sm border-b border-gray-200 bg-gray-50">
                                    <div class="p-2 sm:p-3 font-medium">Field</div>
                                    <div class="p-2 sm:p-3 font-medium" :class="selectedLog.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                        {{ selectedLog.description === 'created' ? 'Value' : 'Old Value' }}
                                    </div>
                                </div>
                                
                                <div v-for="(value, key) in getPropertyChanges(selectedLog).new || getPropertyChanges(selectedLog).old" 
                                    :key="key" class="grid border-b border-gray-100 last:border-b-0"
                                    :class="selectedLog.description === 'updated' ? 'grid-cols-3' : 'grid-cols-2'">
                                    
                                    <div class="p-2 sm:p-3 text-xs sm:text-sm font-medium border-r border-gray-100 bg-gray-50 capitalize break-words">
                                        {{ key.replace(/_/g, ' ') }}
                                    </div>
                                    
                                    <template v-if="selectedLog.description === 'updated'">
                                        <div class="p-2 sm:p-3 text-xs sm:text-sm text-red-600 border-r border-gray-100 break-words">
                                            <span v-if="getPropertyChanges(selectedLog).old?.[key] !== undefined">
                                                {{ getPropertyChanges(selectedLog).old[key] }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                        <div class="p-2 sm:p-3 text-xs sm:text-sm text-green-600 break-words">
                                            <span v-if="value !== undefined">
                                                {{ value }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                    </template>
                                    
                                    <template v-else>
                                        <div class="p-2 sm:p-3 text-xs sm:text-sm break-words" :class="selectedLog.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                            <span v-if="value !== undefined">
                                                {{ value }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Raw Properties (Fallback) -->
                    <div v-else-if="selectedLog.properties">
                        <p class="mb-2 sm:mb-3 text-xs sm:text-sm font-medium text-gray-500">Properties</p>
                        <pre class="p-3 sm:p-4 overflow-auto text-xs sm:text-sm bg-gray-100 rounded-lg max-h-60">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
                    </div>

                    <!-- No Properties -->
                    <div v-else class="text-center text-gray-500 py-6 sm:py-8">
                        <i class="pi pi-info-circle text-3xl sm:text-4xl mb-2 sm:mb-3"></i>
                        <p class="text-sm sm:text-base">No property data available for this log entry.</p>
                    </div>
                </div>

                <template #footer>
                    <Button label="Close" severity="secondary" @click="closeLogDetails" class="w-full sm:w-auto" />
                </template>
            </Dialog>
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

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 1rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-datatable .p-paginator) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-paginator) {
        padding: 1rem;
    }
}

:deep(.p-calendar) {
    width: 100%;
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(to right, #667eea, #764ba2);
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-header) {
        padding: 1.5rem;
    }
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-header .p-dialog-title) {
        font-size: 1.125rem;
    }
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1.5rem;
    }
}

/* Fix search icon alignment */
:deep(.p-input-icon-left > i:first-of-type) {
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    position: absolute;
}

:deep(.p-input-icon-left > .p-inputtext) {
    padding-left: 2.5rem;
}

:deep(.p-input-icon-left) {
    position: relative;
    display: block;
}

:deep(.p-badge) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-badge) {
        font-size: 0.875rem;
    }
}

:deep(.p-inputtext) {
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-inputtext) {
        font-size: 1rem;
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

/* Mobile table improvements */
@media (max-width: 768px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 0.75rem 0.5rem;
    }
    
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        padding: 0.75rem 0.5rem;
    }
}
</style>