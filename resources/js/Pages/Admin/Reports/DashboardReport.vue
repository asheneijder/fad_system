<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Breadcrumb from 'primevue/breadcrumb';
import Badge from 'primevue/badge';
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    report: Object,
    filters: Object
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Comprehensive Dashboard Report' }
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

const refreshReport = () => {
    router.get(route('admin.reports.dashboard-report'), {
        date_range: selectedDateRange.value
    });
};

const exportReport = () => {
    router.get(route('admin.reports.dashboard-report'), {
        date_range: selectedDateRange.value,
        export: 'true'
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0);
};

const getAlertSeverity = (count) => {
    if (count === 0) return 'success';
    if (count <= 5) return 'warning';
    return 'danger';
};
</script>

<template>
    <Head :title="report?.title" />
    <AppLayout>
        <div class="p-6 space-y-6">
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
                    <h1 class="text-3xl font-bold text-gray-800">{{ report?.title }}</h1>
                    <p class="mt-1 text-gray-500">
                        Period: {{ report?.period }} | Generated: {{ report?.generated_at }}
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
                        />
                        <Button
                            icon="pi pi-refresh"
                            severity="secondary"
                            outlined
                            @click="refreshReport"
                            v-tooltip="'Refresh Report'"
                        />
                    </div>
                    <Button
                        label="Export Report"
                        icon="pi pi-download"
                        severity="primary"
                        @click="exportReport"
                    />
                </div>
            </div>

            <!-- System Overview -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-bar text-blue-500"></i>
                        <span>System Overview</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <i class="pi pi-desktop text-blue-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Assets</p>
                            <p class="mt-1 text-xl font-bold text-blue-600">
                                {{ formatNumber(report?.overview?.total_assets) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <i class="pi pi-key text-green-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Licenses</p>
                            <p class="mt-1 text-xl font-bold text-green-600">
                                {{ formatNumber(report?.overview?.total_licenses) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <i class="pi pi-users text-purple-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Users</p>
                            <p class="mt-1 text-xl font-bold text-purple-600">
                                {{ formatNumber(report?.overview?.total_users) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <i class="pi pi-shopping-cart text-orange-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Stationary</p>
                            <p class="mt-1 text-xl font-bold text-orange-600">
                                {{ formatNumber(report?.overview?.total_stationary_items) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <i class="pi pi-sync text-red-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Assignments</p>
                            <p class="mt-1 text-xl font-bold text-red-600">
                                {{ formatNumber(report?.overview?.total_assignments) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-indigo-50 rounded-lg">
                            <i class="pi pi-wrench text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Maintenance</p>
                            <p class="mt-1 text-xl font-bold text-indigo-600">
                                {{ formatNumber(report?.overview?.total_maintenance) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- System Alerts -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-500"></i>
                        <span>System Alerts</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                            <i class="pi pi-clock text-yellow-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Expiring Licenses</p>
                            <p class="mt-1 text-xl font-bold text-yellow-600">
                                {{ formatNumber(report?.alerts?.expiring_licenses) }}
                            </p>
                            <Badge 
                                :value="report?.alerts?.expiring_licenses > 0 ? 'Attention Needed' : 'All Good'" 
                                :severity="getAlertSeverity(report?.alerts?.expiring_licenses)" 
                                class="mt-2"
                            />
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg border-l-4 border-orange-500">
                            <i class="pi pi-exclamation-circle text-orange-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Low Stock Licenses</p>
                            <p class="mt-1 text-xl font-bold text-orange-600">
                                {{ formatNumber(report?.alerts?.low_stock_licenses) }}
                            </p>
                            <Badge 
                                :value="report?.alerts?.low_stock_licenses > 0 ? 'Restock Needed' : 'Well Stocked'" 
                                :severity="getAlertSeverity(report?.alerts?.low_stock_licenses)" 
                                class="mt-2"
                            />
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                            <i class="pi pi-wrench text-red-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Maintenance Assets</p>
                            <p class="mt-1 text-xl font-bold text-red-600">
                                {{ formatNumber(report?.alerts?.maintenance_assets) }}
                            </p>
                            <Badge 
                                :value="report?.alerts?.maintenance_assets > 0 ? 'Maintenance Due' : 'All Operational'" 
                                :severity="getAlertSeverity(report?.alerts?.maintenance_assets)" 
                                class="mt-2"
                            />
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg border-l-4 border-purple-500">
                            <i class="pi pi-box text-purple-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">Low Stock Stationary</p>
                            <p class="mt-1 text-xl font-bold text-purple-600">
                                {{ formatNumber(report?.alerts?.low_stock_stationary) }}
                            </p>
                            <Badge 
                                :value="report?.alerts?.low_stock_stationary > 0 ? 'Reorder Needed' : 'Adequate Stock'" 
                                :severity="getAlertSeverity(report?.alerts?.low_stock_stationary)" 
                                class="mt-2"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Detailed Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Asset Metrics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-desktop text-blue-500"></i>
                            <span>Asset Metrics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Assets</span>
                                <span class="font-bold text-blue-600">
                                    {{ formatNumber(report?.asset_metrics?.total_assets) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-700">Assigned Assets</span>
                                <span class="font-bold text-green-600">
                                    {{ formatNumber(report?.asset_metrics?.assigned_assets) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                                <span class="font-medium text-gray-700">Available Assets</span>
                                <span class="font-bold text-yellow-600">
                                    {{ formatNumber(report?.asset_metrics?.available_assets) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Value</span>
                                <span class="font-bold text-purple-600">
                                    {{ formatCurrency(report?.asset_metrics?.total_value) }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- License Metrics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-key text-green-500"></i>
                            <span>License Metrics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Licenses</span>
                                <span class="font-bold text-green-600">
                                    {{ formatNumber(report?.license_metrics?.total_licenses) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="font-medium text-gray-700">Active Licenses</span>
                                <span class="font-bold text-blue-600">
                                    {{ formatNumber(report?.license_metrics?.active_licenses) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <span class="font-medium text-gray-700">Expired Licenses</span>
                                <span class="font-bold text-red-600">
                                    {{ formatNumber(report?.license_metrics?.expired_licenses) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                <span class="font-medium text-gray-700">Utilization Rate</span>
                                <span class="font-bold text-purple-600">
                                    {{ (report?.license_metrics?.utilization_rate || 0).toFixed(1) }}%
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- User & Stationary Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User Metrics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-users text-purple-500"></i>
                            <span>User Metrics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Users</span>
                                <span class="font-bold text-purple-600">
                                    {{ formatNumber(report?.user_metrics?.total_users) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-700">Active Users</span>
                                <span class="font-bold text-green-600">
                                    {{ formatNumber(report?.user_metrics?.active_users) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="font-medium text-gray-700">Users with Assets</span>
                                <span class="font-bold text-blue-600">
                                    {{ formatNumber(report?.user_metrics?.users_with_assets) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                <span class="font-medium text-gray-700">New Users</span>
                                <span class="font-bold text-orange-600">
                                    {{ formatNumber(report?.user_metrics?.new_users_this_period) }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Stationary Metrics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-shopping-cart text-orange-500"></i>
                            <span>Stationary Metrics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Items</span>
                                <span class="font-bold text-orange-600">
                                    {{ formatNumber(report?.stationary_metrics?.total_items) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-700">Active Items</span>
                                <span class="font-bold text-green-600">
                                    {{ formatNumber(report?.stationary_metrics?.active_items) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <span class="font-medium text-gray-700">Low Stock Items</span>
                                <span class="font-bold text-red-600">
                                    {{ formatNumber(report?.stationary_metrics?.low_stock_items) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Stock Value</span>
                                <span class="font-bold text-purple-600">
                                    {{ formatCurrency(report?.stationary_metrics?.total_stock_value) }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-bolt text-yellow-500"></i>
                        <span>Quick Actions</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Button
                            label="View Assets"
                            icon="pi pi-desktop"
                            severity="primary"
                            outlined
                            class="w-full"
                            @click="router.visit(route('admin.assets.index'))"
                        />
                        <Button
                            label="Manage Licenses"
                            icon="pi pi-key"
                            severity="success"
                            outlined
                            class="w-full"
                            @click="router.visit(route('admin.licenses.index'))"
                        />
                        <Button
                            label="User Management"
                            icon="pi pi-users"
                            severity="info"
                            outlined
                            class="w-full"
                            @click="router.visit(route('admin.users.index'))"
                        />
                        <Button
                            label="Stationary Items"
                            icon="pi pi-shopping-cart"
                            severity="warning"
                            outlined
                            class="w-full"
                            @click="router.visit(route('admin.stationary-items.index'))"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>