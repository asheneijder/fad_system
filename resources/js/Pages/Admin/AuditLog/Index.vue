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
import Tooltip from 'primevue/tooltip';
import Toast from 'primevue/toast';
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 15, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '', action: '', model: '' })
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, created: 0, updated: 0, deleted: 0, today: 0 })
    },
    models: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();
const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Audit Logs' }];

// Filters
const search = ref(props.filters?.search || "");
const startDate = ref(props.filters?.start_date ? new Date(props.filters.start_date) : null);
const endDate = ref(props.filters?.end_date ? new Date(props.filters.end_date) : null);
const selectedAction = ref(props.filters?.action || "");
const selectedModel = ref(props.filters?.model || "");
const perPage = ref(props.logs.per_page || 15);

// UI State
const logs = ref(props.logs);
const showLogDetails = ref(false);
const selectedLog = ref(null);
const loading = ref(false);
const showExportDialog = ref(false);
const showCleanupDialog = ref(false);
const cleanupDays = ref(90);

// Options
const actionOptions = ref([
    { label: 'All Actions', value: '' },
    { label: 'Created', value: 'created' },
    { label: 'Updated', value: 'updated' },
    { label: 'Deleted', value: 'deleted' },
]);

const perPageOptions = ref([
    { label: '15 per page', value: 15 },
    { label: '25 per page', value: 25 },
    { label: '50 per page', value: 50 },
    { label: '100 per page', value: 100 },
]);

const modelOptions = ref([
    { label: 'All Models', value: '' },
    ...props.models
]);

// Watchers
watch(() => props.logs, (newLogs) => {
    logs.value = newLogs;
}, { immediate: true });

watch([search, startDate, endDate, selectedAction, selectedModel, perPage], 
    ([newSearch, newStartDate, newEndDate, newAction, newModel, newPerPage]) => {
    loading.value = true;
    
    const filters = {
        search: newSearch,
        start_date: newStartDate ? newStartDate.toISOString().split('T')[0] : '',
        end_date: newEndDate ? newEndDate.toISOString().split('T')[0] : '',
        action: newAction,
        model: newModel,
        per_page: newPerPage
    };

    router.get(route("admin.audit-logs.index"), filters, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        }
    });
}, { deep: true });

// Methods
const onPageChange = (event) => {
    loading.value = true;
    const page = event.page + 1;
    
    const filters = {
        search: search.value,
        start_date: startDate.value ? startDate.value.toISOString().split('T')[0] : '',
        end_date: endDate.value ? endDate.value.toISOString().split('T')[0] : '',
        action: selectedAction.value,
        model: selectedModel.value,
        per_page: perPage.value,
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
        case 'updated': return 'warn';
        case 'deleted': return 'danger';
        default: return 'info';
    }
};

const getActionIcon = (action) => {
    switch (action) {
        case 'created': return 'pi-plus-circle';
        case 'updated': return 'pi-pencil';
        case 'deleted': return 'pi-trash';
        default: return 'pi-circle';
    }
};

const getActionLabel = (description) => {
    return description ? description.charAt(0).toUpperCase() + description.slice(1) : 'Unknown';
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
    selectedAction.value = "";
    selectedModel.value = "";
    
    toast.add({
        severity: 'info',
        summary: 'Filters Cleared',
        detail: 'All filters have been reset',
        life: 3000
    });
};

const exportLogs = () => {
    const filters = {
        search: search.value,
        start_date: startDate.value ? startDate.value.toISOString().split('T')[0] : '',
        end_date: endDate.value ? endDate.value.toISOString().split('T')[0] : '',
        action: selectedAction.value,
        model: selectedModel.value,
    };

    // Create query string
    const queryString = new URLSearchParams(filters).toString();
    window.location.href = route('admin.audit-logs.export') + '?' + queryString;
    
    showExportDialog.value = false;
    toast.add({
        severity: 'success',
        summary: 'Export Started',
        detail: 'Your audit logs are being exported...',
        life: 3000
    });
};

const getChangeSummary = (log) => {
    if (!log.properties) return 'No changes';
    
    if (log.description === 'updated' && log.properties.old) {
        const count = Object.keys(log.properties.old).length;
        return `${count} field${count !== 1 ? 's' : ''} updated`;
    }
    
    if (log.description === 'created') {
        return 'New record created';
    }
    
    if (log.description === 'deleted') {
        return 'Record deleted';
    }
    
    return log.description;
};

const performCleanup = () => {
    router.post(route('admin.audit-logs.cleanup'), {
        days: cleanupDays.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCleanupDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Cleanup Completed',
                detail: 'Old audit logs have been cleaned up successfully',
                life: 5000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Cleanup Failed',
                detail: errors.days || 'An error occurred during cleanup',
                life: 5000
            });
        }
    });
};

const refreshStatistics = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ['stats']
    });
    
    toast.add({
        severity: 'info',
        summary: 'Refreshing Statistics',
        detail: 'Updating audit log statistics...',
        life: 2000
    });
};
</script>

<template>
    <Head title="Audit Logs" />
    <AppLayout>
        <Toast />
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
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button label="Export Logs" icon="pi pi-download" severity="success" 
                        @click="showExportDialog = true" class="w-full sm:w-auto" />
                    <Button label="Cleanup" icon="pi pi-trash" severity="secondary" outlined
                        @click="showCleanupDialog = true" class="w-full sm:w-auto" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md cursor-pointer" @click="refreshStatistics">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Total</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ stats.total.toLocaleString() }}</p>
                            </div>
                            <div class="p-2 bg-blue-100 rounded-full">
                                <i class="text-lg text-blue-600 pi pi-database"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Today</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ stats.today.toLocaleString() }}</p>
                            </div>
                            <div class="p-2 bg-purple-100 rounded-full">
                                <i class="text-lg text-purple-600 pi pi-calendar"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Created</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ stats.created.toLocaleString() }}</p>
                            </div>
                            <div class="p-2 bg-green-100 rounded-full">
                                <i class="text-lg text-green-600 pi pi-plus-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Updated</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ stats.updated.toLocaleString() }}</p>
                            </div>
                            <div class="p-2 bg-yellow-100 rounded-full">
                                <i class="text-lg text-yellow-600 pi pi-pencil"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Deleted</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ stats.deleted.toLocaleString() }}</p>
                            </div>
                            <div class="p-2 bg-red-100 rounded-full">
                                <i class="text-lg text-red-600 pi pi-trash"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-indigo-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Per Page</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ logs.per_page }}</p>
                            </div>
                            <div class="p-2 bg-indigo-100 rounded-full">
                                <i class="text-lg text-indigo-600 pi pi-list"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Filters -->
                    <div class="flex flex-col gap-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of {{ logs.total.toLocaleString() }} logs
                            </div>
                            <Select v-model="perPage" :options="perPageOptions" optionLabel="label" 
                                optionValue="value" class="w-40" />
                        </div>

                        <!-- Filter Row 1 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Action Type</label>
                                <Select v-model="selectedAction" :options="actionOptions" 
                                    optionLabel="label" optionValue="value" class="w-full" 
                                    placeholder="Filter by action" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Model Type</label>
                                <Select v-model="selectedModel" :options="modelOptions" 
                                    optionLabel="label" optionValue="value" class="w-full" 
                                    placeholder="Filter by model" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Search</label>
                                <span class="p-input-icon-left block w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search logs, users, models..." class="w-full" />
                                </span>
                            </div>
                        </div>

                        <!-- Filter Row 2 -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Start Date</label>
                                <DatePicker v-model="startDate" placeholder="Start Date" 
                                    dateFormat="yy-mm-dd" showIcon class="w-full" />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">End Date</label>
                                <DatePicker v-model="endDate" placeholder="End Date" 
                                    dateFormat="yy-mm-dd" showIcon class="w-full" />
                            </div>
                            <div class="flex items-end gap-2">
                                <Button label="Clear Filters" icon="pi pi-filter-slash" 
                                    severity="secondary" outlined @click="clearFilters" 
                                    class="flex-1" />
                                <Button icon="pi pi-refresh" severity="help" outlined
                                    @click="refreshStatistics" v-tooltip="'Refresh Statistics'"
                                    :loading="loading" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <DataTable :value="logs.data" showGridlines stripedRows :rowHover="true" 
                        paginator :rows="logs.per_page" :totalRecords="logs.total"
                        :first="(logs.current_page - 1) * logs.per_page" @page="onPageChange"
                        :loading="loading" dataKey="id" responsiveLayout="scroll"
                        class="p-datatable-sm">

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <i class="pi pi-inbox text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Audit Logs Found</h3>
                                <p class="text-gray-500">Try adjusting your filters or check back later.</p>
                            </div>
                        </template>

                        <!-- Loading State -->
                        <template #loading>
                            <div class="flex items-center justify-center py-8">
                                <i class="pi pi-spin pi-spinner text-2xl text-blue-500 mr-2"></i>
                                <span class="text-gray-600">Loading audit logs...</span>
                            </div>
                        </template>

                        <!-- Columns -->
                        <Column header="#" style="width: 70px;">
                            <template #body="slotProps">
                                <Badge :value="(logs.current_page - 1) * logs.per_page + slotProps.index + 1"
                                    severity="contrast" />
                            </template>
                        </Column>

                        <Column header="Action" field="description" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="`pi ${getActionIcon(slotProps.data.description)}`"></i>
                                    <Badge :value="getActionLabel(slotProps.data.description)"
                                        :severity="getActionSeverity(slotProps.data.description)" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Model" field="subject_type" style="min-width: 150px;">
                            <template #body="slotProps">
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ formatModelName(slotProps.data.subject_type) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ slotProps.data.subject_id || 'N/A' }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="User" style="min-width: 200px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.causer" class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ slotProps.data.causer.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ slotProps.data.causer.name }}</div>
                                        <div class="text-xs text-gray-500">{{ slotProps.data.causer.email }}</div>
                                    </div>
                                </div>
                                <Badge v-else value="System" severity="info" />
                            </template>
                        </Column>

                        <Column header="Summary" style="min-width: 200px;">
                            <template #body="slotProps">
                                <div class="text-sm text-gray-600">
                                    {{ getChangeSummary(slotProps.data) }}
                                </div>
                            </template>
                        </Column>

                        <Column header="Date & Time" field="created_at" style="min-width: 180px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-clock text-gray-400"></i>
                                    <span class="text-sm text-gray-600">{{ formatDate(slotProps.data.created_at) }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 100px;">
                            <template #body="slotProps">
                                <Button icon="pi pi-eye" outlined rounded severity="info" 
                                    size="small" v-tooltip.top="'View Details'" 
                                    @click="viewLogDetails(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Log Details Dialog -->
            <Dialog v-model:visible="showLogDetails" modal header="Audit Log Details" 
                :style="{ width: '95vw', maxWidth: '900px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div v-if="selectedLog" class="space-y-6">
                    <!-- Header Info Card -->
                    <Card class="bg-gradient-to-r from-blue-50 to-purple-50">
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 rounded-full" 
                                        :class="{
                                            'bg-green-100': selectedLog.description === 'created',
                                            'bg-yellow-100': selectedLog.description === 'updated',
                                            'bg-red-100': selectedLog.description === 'deleted'
                                        }">
                                        <i class="text-2xl" 
                                            :class="[
                                                `pi ${getActionIcon(selectedLog.description)}`,
                                                {
                                                    'text-green-600': selectedLog.description === 'created',
                                                    'text-yellow-600': selectedLog.description === 'updated',
                                                    'text-red-600': selectedLog.description === 'deleted'
                                                }
                                            ]"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Action Type</p>
                                        <Badge :value="getActionLabel(selectedLog.description)"
                                            :severity="getActionSeverity(selectedLog.description)"
                                            class="mt-1 text-lg" />
                                    </div>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Log ID</p>
                                    <p class="mt-1 text-lg font-bold text-gray-900">#{{ selectedLog.id }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Card>
                            <template #content>
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-box text-xl text-blue-500 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-500">Model</p>
                                        <p class="mt-1 text-base font-semibold text-gray-900">
                                            {{ formatModelName(selectedLog.subject_type) }}
                                        </p>
                                        <p class="text-sm text-gray-500">ID: {{ selectedLog.subject_id || 'N/A' }}</p>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #content>
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-user text-xl text-purple-500 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-500">Performed By</p>
                                        <div v-if="selectedLog.causer" class="mt-1">
                                            <p class="text-base font-semibold text-gray-900">{{ selectedLog.causer.name }}</p>
                                            <p class="text-sm text-gray-500">{{ selectedLog.causer.email }}</p>
                                        </div>
                                        <Badge v-else value="System" severity="info" class="mt-1" />
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #content>
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-calendar text-xl text-green-500 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-500">Timestamp</p>
                                        <p class="mt-1 text-base font-semibold text-gray-900">
                                            {{ formatDate(selectedLog.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #content>
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-globe text-xl text-orange-500 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-500">IP Address</p>
                                        <p class="mt-1 text-base font-semibold text-gray-900">
                                            {{ selectedLog.properties?.ip_address || 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Property Changes -->
                    <Card v-if="getPropertyChanges(selectedLog)">
                        <template #header>
                            <div class="flex items-center gap-2 p-4 bg-gradient-to-r from-gray-50 to-gray-100">
                                <i class="pi pi-list text-xl text-gray-700"></i>
                                <h3 class="text-lg font-semibold text-gray-800">Property Changes</h3>
                            </div>
                        </template>
                        <template #content>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[500px]">
                                    <thead>
                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                            <th class="p-3 text-left font-semibold text-gray-700">Field</th>
                                            <th v-if="selectedLog.description === 'updated'" 
                                                class="p-3 text-left font-semibold text-red-600">Old Value</th>
                                            <th class="p-3 text-left font-semibold" 
                                                :class="selectedLog.description === 'created' ? 'text-green-600' : 
                                                        selectedLog.description === 'deleted' ? 'text-red-600' : 'text-green-600'">
                                                {{ selectedLog.description === 'created' ? 'Value' : 
                                                   selectedLog.description === 'deleted' ? 'Old Value' : 'New Value' }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(value, key) in getPropertyChanges(selectedLog).new || getPropertyChanges(selectedLog).old" 
                                            :key="key" class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="p-3 font-medium text-gray-700 capitalize bg-gray-50">
                                                {{ key.replace(/_/g, ' ') }}
                                            </td>
                                            <td v-if="selectedLog.description === 'updated'" 
                                                class="p-3 text-red-600 font-mono text-sm">
                                                <span v-if="getPropertyChanges(selectedLog).old?.[key] !== undefined">
                                                    {{ getPropertyChanges(selectedLog).old[key] }}
                                                </span>
                                                <span v-else class="text-gray-400">—</span>
                                            </td>
                                            <td class="p-3 font-mono text-sm" 
                                                :class="selectedLog.description === 'created' ? 'text-green-600' : 
                                                        selectedLog.description === 'deleted' ? 'text-red-600' : 'text-green-600'">
                                                <span v-if="value !== undefined && value !== null">
                                                    {{ value }}
                                                </span>
                                                <span v-else class="text-gray-400">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </Card>

                    <!-- User Agent Info -->
                    <Card v-if="selectedLog.properties?.user_agent">
                        <template #content>
                            <div class="flex items-start gap-3">
                                <i class="pi pi-desktop text-xl text-indigo-500 mt-1"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-500">User Agent</p>
                                    <p class="mt-1 text-sm text-gray-700 font-mono break-all">
                                        {{ selectedLog.properties.user_agent }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- No Properties -->
                    <div v-if="!getPropertyChanges(selectedLog) && !selectedLog.properties" 
                        class="text-center py-8">
                        <i class="pi pi-info-circle text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No detailed information available for this log entry.</p>
                    </div>
                </div>

                <template #footer>
                    <div class="flex gap-2">
                        <Button label="Close" severity="secondary" @click="closeLogDetails" class="flex-1" />
                    </div>
                </template>
            </Dialog>

            <!-- Export Dialog -->
            <Dialog v-model:visible="showExportDialog" modal header="Export Audit Logs" 
                :style="{ width: '450px' }">
                <div class="space-y-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <div class="flex items-start gap-3">
                            <i class="pi pi-info-circle text-blue-600 text-xl mt-1"></i>
                            <div>
                                <p class="font-semibold text-blue-900">Export Information</p>
                                <p class="text-sm text-blue-700 mt-1">
                                    This will export all audit logs matching your current filters to a CSV file.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Format:</span>
                            <span class="font-semibold text-gray-900">CSV</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total Records:</span>
                            <span class="font-semibold text-gray-900">{{ logs.total.toLocaleString() }}</span>
                        </div>
                        <div v-if="search || startDate || endDate || selectedAction || selectedModel" 
                            class="flex justify-between text-sm">
                            <span class="text-gray-600">Filters Applied:</span>
                            <Badge value="Yes" severity="info" />
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex gap-2">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showExportDialog = false" class="flex-1" />
                        <Button label="Download CSV" icon="pi pi-download" severity="success" 
                            @click="exportLogs" class="flex-1" />
                    </div>
                </template>
            </Dialog>

            <!-- Cleanup Dialog -->
            <Dialog v-model:visible="showCleanupDialog" modal header="Cleanup Audit Logs" 
                :style="{ width: '450px' }">
                <div class="space-y-4">
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                        <div class="flex items-start gap-3">
                            <i class="pi pi-exclamation-triangle text-orange-600 text-xl mt-1"></i>
                            <div>
                                <p class="font-semibold text-orange-900">Warning</p>
                                <p class="text-sm text-orange-700 mt-1">
                                    This action will permanently delete audit logs older than the specified number of days. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Delete logs older than (days)</label>
                        <InputText v-model="cleanupDays" type="number" min="30" 
                            placeholder="Enter number of days" class="w-full" />
                        <p class="text-xs text-gray-500">
                            Minimum: 30 days. Logs older than {{ cleanupDays }} days will be permanently deleted.
                        </p>
                    </div>
                </div>

                <template #footer>
                    <div class="flex gap-2">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showCleanupDialog = false" class="flex-1" />
                        <Button label="Cleanup Logs" icon="pi pi-trash" severity="danger" 
                            @click="performCleanup" class="flex-1" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1.25rem;
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
    padding: 1rem;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.875rem;
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    color: white;
    font-weight: 600;
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon) {
    color: white;
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon:hover) {
    background-color: rgba(255, 255, 255, 0.2);
}

:deep(.p-input-icon-left > i:first-of-type) {
    left: 0.75rem;
}

:deep(.p-input-icon-left > .p-inputtext) {
    padding-left: 2.5rem;
}

@media (max-width: 768px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 0.75rem 0.5rem;
    }
    
    :deep(.p-datatable.p-datatable-sm .p-datatable-tbody > tr > td) {
        padding: 0.5rem 0.25rem;
    }
}
</style>