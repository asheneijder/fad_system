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

const toast = useToast();

const props = defineProps({
    logs: Object,
    filters: Object,
    logNames: Array,
    events: Array,
});

// Refs
const loading = ref(false);
const exporting = ref(false);
const clearing = ref(false);
const showClearDialog = ref(false);
const clearDays = ref(30);

// Filters
const filters = ref({
    search: props.filters.search,
    log_name: props.filters.log_name,
    event: props.filters.event,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    per_page: props.filters.per_page,
});

// Breadcrumb
const home = ref({
    icon: 'pi pi-home',
    route: '/dashboard'
});

const items = ref([
    { label: 'Admin', route: '/admin' },
    { label: 'Audit Logs' }
]);

// Methods
const applyFilters = () => {
    router.get(route('admin.audit-logs.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => loading.value = true,
        onFinish: () => loading.value = false,
    });
};

const clearFilters = () => {
    filters.value = {
        search: '',
        log_name: '',
        event: '',
        date_from: '',
        date_to: '',
        per_page: 25,
    };
    applyFilters();
};

const onPageChange = (event) => {
    filters.value.per_page = event.rows;
    filters.value.page = event.page + 1;
    applyFilters();
};

const onSort = (event) => {
    filters.value.sort = event.sortField;
    filters.value.direction = event.sortOrder === 1 ? 'asc' : 'desc';
    applyFilters();
};

const viewLog = (id) => {
    router.get(route('admin.audit-logs.show', id));
};

const exportLogs = () => {
    exporting.value = true;
    
    // Create a copy of filters to modify dates
    const params = { ...filters.value };
    
    // Helper to format date as YYYY-MM-DD
    const formatDateParam = (date) => {
        if (!date) return '';
        const d = new Date(date);
        return d.toISOString().split('T')[0];
    };

    if (params.date_from) params.date_from = formatDateParam(params.date_from);
    if (params.date_to) params.date_to = formatDateParam(params.date_to);

    const queryParams = new URLSearchParams(params).toString();
    window.location.href = route('admin.audit-logs.export') + '?' + queryParams;
    
    // Reset after a delay
    setTimeout(() => exporting.value = false, 2000);
};

const clearOldLogs = () => {
    clearing.value = true;
    router.post(route('admin.audit-logs.clear'), {
        days: clearDays.value
    }, {
        onSuccess: () => {
            showClearDialog.value = false;
            clearing.value = false;
            clearDays.value = 30;
            applyFilters(); // Refresh the list
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Old logs cleared successfully',
                life: 3000
            });
        },
        onError: () => {
            clearing.value = false;
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to clear logs',
                life: 3000
            });
        }
    });
};

const getEventSeverity = (event) => {
    const severityMap = {
        created: 'success',
        updated: 'info',
        deleted: 'danger',
        restored: 'warning',
    };
    return severityMap[event] || 'secondary';
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

// Watch for filter changes with debounce
let timeoutId;
watch(filters, () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        applyFilters();
    }, 500);
}, { deep: true });
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

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Audit Logs</h1>
                    <p class="text-gray-600 mt-1">Monitor system activities and user actions</p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        label="Export CSV" 
                        icon="pi pi-download" 
                        severity="secondary"
                        @click="exportLogs"
                        :loading="exporting"
                    />
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Search</label>
                            <InputText 
                                v-model="filters.search" 
                                placeholder="Search logs..." 
                                class="w-full"
                            />
                        </div>

                        <!-- Log Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Log Type</label>
                            <Select 
                                v-model="filters.log_name" 
                                :options="logNames" 
                                placeholder="All Types"
                                class="w-full"
                            />
                        </div>

                        <!-- Event -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Event</label>
                            <Select 
                                v-model="filters.event" 
                                :options="events" 
                                placeholder="All Events"
                                class="w-full"
                            />
                        </div>

                        <!-- Date Range -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Date Range</label>
                            <div class="flex gap-2">
                                <DatePicker 
                                    v-model="filters.date_from" 
                                    placeholder="From Date"
                                    class="flex-1"
                                    showIcon
                                />
                                <DatePicker 
                                    v-model="filters.date_to" 
                                    placeholder="To Date"
                                    class="flex-1"
                                    showIcon
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4">
                        <div class="flex gap-2">
                            <Button 
                                label="Apply Filters" 
                                icon="pi pi-filter" 
                                @click="applyFilters"
                            />
                            <Button 
                                label="Clear" 
                                icon="pi pi-refresh" 
                                severity="secondary"
                                @click="clearFilters"
                            />
                        </div>

                        <!-- Items per page -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Show:</label>
                            <Select 
                                v-model="filters.per_page" 
                                :options="[10, 25, 50, 100]" 
                                class="w-20"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Logs Table -->
            <Card>
                <template #content>
                    <DataTable 
                        :value="logs.data" 
                        :loading="loading"
                        paginator
                        :rows="filters.per_page"
                        :totalRecords="logs.total"
                        @page="onPageChange"
                        @sort="onSort"
                        removableSort
                        class="p-datatable-sm"
                    >
                        <!-- ID -->
                        <Column field="id" header="ID" sortable style="width: 80px">
                            <template #body="{ data }">
                                <span class="text-xs text-gray-500">#{{ data.id }}</span>
                            </template>
                        </Column>

                        <!-- Description -->
                        <Column field="description" header="Description" sortable>
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium text-gray-900">{{ data.description }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDate(data.created_at) }}</div>
                                </div>
                            </template>
                        </Column>

                        <!-- Causer -->
                        <Column field="causer.name" header="User" sortable>
                            <template #body="{ data }">
                                <div v-if="data.causer">
                                    <div class="font-medium">{{ data.causer.name }}</div>
                                    <div class="text-xs text-gray-500">{{ data.causer.email }}</div>
                                </div>
                                <span v-else class="text-gray-400">System</span>
                            </template>
                        </Column>

                        <!-- Event & Log Name -->
                        <Column header="Details">
                            <template #body="{ data }">
                                <div class="space-y-1">
                                    <Badge 
                                        :value="data.event" 
                                        :severity="getEventSeverity(data.event)"
                                        class="text-xs"
                                    />
                                    <div class="text-xs text-gray-500">{{ data.log_name }}</div>
                                </div>
                            </template>
                        </Column>

                        <!-- Subject -->
                        <Column header="Subject">
                            <template #body="{ data }">
                                <div v-if="data.subject_type">
                                    <div class="font-medium text-gray-900">
                                        {{ data.subject_type.split('\\').pop() }}
                                    </div>
                                    <div class="text-xs text-gray-500">ID: {{ data.subject_id }}</div>
                                </div>
                                <span v-else class="text-gray-400 italic">N/A</span>
                            </template>
                        </Column>

                        <!-- Actions -->
                        <Column header="Actions" style="width: 100px">
                            <template #body="{ data }">
                                <Button 
                                    icon="pi pi-eye" 
                                    severity="info" 
                                    text
                                    rounded
                                    @click="viewLog(data.id)"
                                    v-tooltip="'View Details'"
                                />
                            </template>
                        </Column>

                        <!-- Empty State -->
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500">No audit logs found</p>
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
