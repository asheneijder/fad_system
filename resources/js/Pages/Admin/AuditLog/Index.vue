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
import Calendar from 'primevue/calendar';
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
                    <h1 class="text-3xl font-bold text-gray-800">Audit Logs</h1>
                    <p class="mt-1 text-gray-500">Track all system activities and changes</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Logs</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ logs.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-history"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Created</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'created').length }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-plus-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Updated</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'updated').length }}
                                </p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-pencil"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Deleted</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ logs.data.filter(log => log.description === 'deleted').length }}
                                </p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-xl text-red-600 pi pi-trash"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                        <div class="text-sm text-gray-500">
                            Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of {{ logs.total }} audit logs
                        </div>

                        <div class="flex flex-col w-full gap-4 lg:flex-row lg:w-auto">
                            <!-- Date Range -->
                            <div class="flex flex-col gap-2 lg:flex-row">
                                <div class="w-full lg:w-48">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Start Date</label>
                                    <Calendar v-model="startDate" 
                                        placeholder="Start Date" 
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        class="w-full" />
                                </div>
                                <div class="w-full lg:w-48">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">End Date</label>
                                    <Calendar v-model="endDate" 
                                        placeholder="End Date" 
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        class="w-full" />
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="w-full lg:w-80">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Search</label>
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" 
                                        placeholder="Search logs, users, or models..." 
                                        class="w-full" />
                                </span>
                            </div>

                            <!-- Clear Filters -->
                            <div class="flex items-end">
                                <Button label="Clear Filters" 
                                    icon="pi pi-filter-slash" 
                                    severity="secondary" 
                                    outlined
                                    @click="clearFilters"
                                    class="h-[42px]" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="logs.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="logs.per_page" :totalRecords="logs.total"
                            :first="(logs.current_page - 1) * logs.per_page" @page="onPageChange"
                            :loading="loading" dataKey="id"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-history"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Audit Logs Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your filters or perform some actions in the system.</p>
                                </div>
                            </template>

                            <!-- Loading State -->
                            <template #loading>
                                <div class="flex items-center justify-center py-8">
                                    <i class="pi pi-spin pi-spinner text-2xl text-blue-500"></i>
                                    <span class="ml-2">Loading audit logs...</span>
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(logs.current_page - 1) * logs.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column header="Action" sortable field="description" style="width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getActionLabel(slotProps.data.description)"
                                        :severity="getActionSeverity(slotProps.data.description)"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Model" sortable field="subject_type" style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="font-medium text-gray-900">
                                        {{ formatModelName(slotProps.data.subject_type) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ slotProps.data.subject_id || 'N/A' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="User" sortable style="width: 180px;">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.causer">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.causer.name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ slotProps.data.causer.email }}
                                        </div>
                                    </div>
                                    <Badge v-else value="System" severity="info" />
                                </template>
                            </Column>

                            <Column header="Changes" style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
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

                            <Column header="Timestamp" sortable field="created_at" style="width: 160px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="width: 100px;">
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
            <Dialog v-model:visible="showLogDetails" modal header="Audit Log Details" :style="{ width: '800px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div v-if="selectedLog" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Action</p>
                            <Badge :value="getActionLabel(selectedLog.description)"
                                :severity="getActionSeverity(selectedLog.description)"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Model</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatModelName(selectedLog.subject_type) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Model ID</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ selectedLog.subject_id || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Timestamp</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(selectedLog.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">User</p>
                            <div v-if="selectedLog.causer" class="mt-1">
                                <p class="text-base font-semibold text-gray-900">{{ selectedLog.causer.name }}</p>
                                <p class="text-sm text-gray-500">{{ selectedLog.causer.email }}</p>
                            </div>
                            <Badge v-else value="System" severity="info" class="mt-1" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">IP Address</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ selectedLog.properties?.ip_address || 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Property Changes -->
                    <div v-if="getPropertyChanges(selectedLog)">
                        <p class="mb-3 text-sm font-medium text-gray-500">Property Changes</p>
                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                            <div v-if="selectedLog.description === 'updated'" class="grid grid-cols-3 text-sm border-b border-gray-200 bg-gray-50">
                                <div class="p-3 font-medium">Field</div>
                                <div class="p-3 font-medium text-red-600">Old Value</div>
                                <div class="p-3 font-medium text-green-600">New Value</div>
                            </div>
                            <div v-else class="grid grid-cols-2 text-sm border-b border-gray-200 bg-gray-50">
                                <div class="p-3 font-medium">Field</div>
                                <div class="p-3 font-medium" :class="selectedLog.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                    {{ selectedLog.description === 'created' ? 'Value' : 'Old Value' }}
                                </div>
                            </div>
                            
                            <div v-for="(value, key) in getPropertyChanges(selectedLog).new || getPropertyChanges(selectedLog).old" 
                                :key="key" class="grid border-b border-gray-100 last:border-b-0"
                                :class="selectedLog.description === 'updated' ? 'grid-cols-3' : 'grid-cols-2'">
                                
                                <div class="p-3 font-medium border-r border-gray-100 bg-gray-50 capitalize">
                                    {{ key.replace(/_/g, ' ') }}
                                </div>
                                
                                <template v-if="selectedLog.description === 'updated'">
                                    <div class="p-3 text-red-600 border-r border-gray-100">
                                        <span v-if="getPropertyChanges(selectedLog).old?.[key] !== undefined">
                                            {{ getPropertyChanges(selectedLog).old[key] }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </div>
                                    <div class="p-3 text-green-600">
                                        <span v-if="value !== undefined">
                                            {{ value }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </div>
                                </template>
                                
                                <template v-else>
                                    <div class="p-3" :class="selectedLog.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                        <span v-if="value !== undefined">
                                            {{ value }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Raw Properties (Fallback) -->
                    <div v-else-if="selectedLog.properties">
                        <p class="mb-3 text-sm font-medium text-gray-500">Properties</p>
                        <pre class="p-4 overflow-auto text-sm bg-gray-100 rounded-lg max-h-60">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
                    </div>

                    <!-- No Properties -->
                    <div v-else class="text-center text-gray-500 py-8">
                        <i class="pi pi-info-circle text-4xl mb-3"></i>
                        <p>No property data available for this log entry.</p>
                    </div>
                </div>

                <template #footer>
                    <Button label="Close" severity="secondary" @click="closeLogDetails" />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1.5rem;
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-calendar) {
    width: 100%;
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(to right, #667eea, #764ba2);
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    color: white;
    font-weight: 600;
}
</style>