<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Breadcrumb from 'primevue/breadcrumb';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Badge from 'primevue/badge';
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

// Debug: Log when component loads
console.log('AssetReport component loaded');

const props = defineProps({
    report: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Debug: Log the props
console.log('Props received:', props);
console.log('Report data:', props.report);
console.log('Filters:', props.filters);

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Asset Management Report' }
];

const dateRanges = ref([
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last_7_days', label: 'Last 7 Days' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'this_quarter', label: 'This Quarter' },
    { value: 'this_year', label: 'This Year' },
]);

const selectedDateRange = ref(props.filters?.date_range || 'last_30_days');
const loading = ref(false);

const refreshReport = () => {
    console.log('Refreshing report with date range:', selectedDateRange.value);
    loading.value = true;
    
    router.get(route('admin.reports.asset-report'), {
        date_range: selectedDateRange.value,
        report_type: 'asset'
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
            console.log('Refresh completed');
        }
    });
};

const exportReport = () => {
    router.get(route('admin.reports.asset-report'), {
        date_range: selectedDateRange.value,
        report_type: 'asset',
        export: 'true'
    });
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'available': return 'success';
        case 'assigned': return 'info';
        case 'maintenance': return 'warning';
        case 'retired': return 'danger';
        default: return 'secondary';
    }
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat().format(num);
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

// Safe data accessors
const getSummary = () => {
    return props.report?.summary || {
        total_assets: 0,
        assigned_assets: 0,
        available_assets: 0,
        maintenance_assets: 0,
        total_value: 0,
        assets_added_this_period: 0
    };
};

const getStatusDistribution = () => {
    return props.report?.status_distribution || {};
};

const getAssignmentAnalytics = () => {
    return props.report?.assignment_analytics || {
        total_assignments: 0,
        active_assignments: 0,
        completed_assignments: 0,
        avg_assignment_duration: 0
    };
};

const getMaintenanceAnalytics = () => {
    return props.report?.maintenance_analytics || {
        total_maintenance: 0,
        pending_maintenance: 0,
        completed_maintenance: 0,
        maintenance_cost: 0
    };
};

const getRecentAssignments = () => {
    return props.report?.recent_assignments || [];
};

const getTopModels = () => {
    return props.report?.top_models || [];
};

onMounted(() => {
    console.log('AssetReport component mounted');
    console.log('Full report data:', props.report);
});
</script>

<template>
    <Head :title="report?.title || 'Asset Management Report'" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Debug Info (remove in production) -->
            <Card class="bg-red-50 border-red-200" v-if="false">
                <template #content>
                    <div class="text-sm">
                        <p class="font-semibold text-red-700">Debug Info:</p>
                        <p>Has Report Data: {{ !!report }}</p>
                        <p>Report Keys: {{ report ? Object.keys(report) : 'No report' }}</p>
                        <p>Summary: {{ getSummary() }}</p>
                    </div>
                </template>
            </Card>

            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:text-blue-800" @click="router.visit(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">{{ report?.title || 'Asset Management Report' }}</h1>
                    <p class="mt-1 text-gray-500" v-if="report?.period">
                        Period: {{ report.period }} | Generated: {{ report.generated_at }}
                    </p>
                    <p class="mt-1 text-gray-500" v-else>
                        Loading report data...
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex gap-2">
                        <Dropdown
                            v-model="selectedDateRange"
                            :options="dateRanges"
                            optionLabel="label"
                            optionValue="value"
                            @change="refreshReport"
                            class="min-w-[200px]"
                            :disabled="loading"
                        />
                        <Button
                            icon="pi pi-refresh"
                            severity="secondary"
                            outlined
                            @click="refreshReport"
                            :loading="loading"
                            v-tooltip="'Refresh Report'"
                        />
                    </div>
                    <Button
                        label="Export Report"
                        icon="pi pi-download"
                        severity="primary"
                        @click="exportReport"
                        :disabled="loading"
                    />
                </div>
            </div>

            <!-- Show loading state -->
            <div v-if="loading" class="text-center py-8">
                <i class="pi pi-spin pi-spinner text-4xl text-blue-500 mb-3"></i>
                <p class="text-gray-600">Loading report data...</p>
            </div>

            <!-- Show error state if no data -->
            <Card v-else-if="!report || Object.keys(report).length === 0" class="bg-yellow-50 border-yellow-200">
                <template #content>
                    <div class="text-center py-8">
                        <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-3"></i>
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Report Data Available</h3>
                        <p class="text-yellow-600">The report data could not be loaded. This might be because:</p>
                        <ul class="text-yellow-600 text-sm mt-2 text-left max-w-md mx-auto">
                            <li>• There are no assets in the system yet</li>
                            <li>• The date range selected has no data</li>
                            <li>• There was an error generating the report</li>
                        </ul>
                        <Button 
                            label="Try Again" 
                            icon="pi pi-refresh" 
                            @click="refreshReport" 
                            class="mt-4"
                            severity="warning"
                        />
                    </div>
                </template>
            </Card>

            <!-- Main Report Content -->
            <div v-else>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card class="border-l-4 border-blue-500">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Assets</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ formatNumber(getSummary().total_assets) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ formatNumber(getSummary().assets_added_this_period) }} added this period
                                    </p>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-full">
                                    <i class="pi pi-desktop text-blue-600 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="border-l-4 border-green-500">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Assigned Assets</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ formatNumber(getSummary().assigned_assets) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ ((getSummary().assigned_assets / getSummary().total_assets) * 100 || 0).toFixed(1) }}% utilization
                                    </p>
                                </div>
                                <div class="p-3 bg-green-100 rounded-full">
                                    <i class="pi pi-user text-green-600 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="border-l-4 border-yellow-500">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Available Assets</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ formatNumber(getSummary().available_assets) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Ready for assignment
                                    </p>
                                </div>
                                <div class="p-3 bg-yellow-100 rounded-full">
                                    <i class="pi pi-check-circle text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="border-l-4 border-purple-500">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Value</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ formatCurrency(getSummary().total_value) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Asset inventory value
                                    </p>
                                </div>
                                <div class="p-3 bg-purple-100 rounded-full">
                                    <i class="pi pi-dollar text-purple-600 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Status Distribution -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-pie text-blue-500"></i>
                                <span>Asset Status Distribution</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div
                                    v-for="(count, status) in getStatusDistribution()"
                                    :key="status"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                >
                                    <div class="flex items-center gap-3">
                                        <Badge :value="count" :severity="getStatusSeverity(status)" />
                                        <span class="font-medium text-gray-700 capitalize">{{ status }}</span>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ ((count / getSummary().total_assets) * 100 || 0).toFixed(1) }}%
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Assignment Analytics -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-sync text-green-500"></i>
                                <span>Assignment Analytics</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                    <span class="font-medium text-gray-700">Total Assignments</span>
                                    <span class="font-bold text-green-600">
                                        {{ formatNumber(getAssignmentAnalytics().total_assignments) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                    <span class="font-medium text-gray-700">Active Assignments</span>
                                    <span class="font-bold text-blue-600">
                                        {{ formatNumber(getAssignmentAnalytics().active_assignments) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-700">Avg. Assignment Duration</span>
                                    <span class="font-bold text-gray-600">
                                        {{ getAssignmentAnalytics().avg_assignment_duration?.toFixed(1) || '0.0' }} days
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Maintenance Analytics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-wrench text-orange-500"></i>
                            <span>Maintenance Analytics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-orange-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500">Total Maintenance</p>
                                <p class="mt-1 text-2xl font-bold text-orange-600">
                                    {{ formatNumber(getMaintenanceAnalytics().total_maintenance) }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="mt-1 text-2xl font-bold text-yellow-600">
                                    {{ formatNumber(getMaintenanceAnalytics().pending_maintenance) }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="mt-1 text-2xl font-bold text-green-600">
                                    {{ formatNumber(getMaintenanceAnalytics().completed_maintenance) }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500">Maintenance Cost</p>
                                <p class="mt-1 text-2xl font-bold text-purple-600">
                                    {{ formatCurrency(getMaintenanceAnalytics().maintenance_cost) }}
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Recent Assignments -->
                <Card v-if="getRecentAssignments().length > 0">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-history text-gray-500"></i>
                            <span>Recent Assignments</span>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="getRecentAssignments()" showGridlines stripedRows class="p-datatable-sm">
                            <Column field="asset.name" header="Asset" style="min-width: 200px">
                                <template #body="slotProps">
                                    <div class="font-medium text-gray-900">{{ slotProps.data.asset?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ slotProps.data.asset?.asset_tag }}</div>
                                </template>
                            </Column>
                            <Column field="user.name" header="Assigned To" style="min-width: 150px">
                                <template #body="slotProps">
                                    {{ slotProps.data.user?.name || 'N/A' }}
                                </template>
                            </Column>
                            <Column field="assigned_at" header="Assigned Date" style="min-width: 150px">
                                <template #body="slotProps">
                                    {{ new Date(slotProps.data.assigned_at).toLocaleDateString() }}
                                </template>
                            </Column>
                            <Column field="condition_assigned" header="Condition" style="min-width: 120px">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.condition_assigned" severity="info" />
                                </template>
                            </Column>
                            <Column header="Status" style="min-width: 100px">
                                <template #body="slotProps">
                                    <Badge
                                        :value="slotProps.data.returned_at ? 'Returned' : 'Active'"
                                        :severity="slotProps.data.returned_at ? 'success' : 'warning'"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Top Models -->
                <Card v-if="getTopModels().length > 0">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-star text-yellow-500"></i>
                            <span>Top Asset Models</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <div
                                v-for="(model, index) in getTopModels()"
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-sm">{{ index + 1 }}</span>
                                    </div>
                                    <span class="font-medium text-gray-700">{{ model.model_name }}</span>
                                </div>
                                <Badge :value="model.asset_count" severity="info" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>