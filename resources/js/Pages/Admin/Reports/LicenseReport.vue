<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Breadcrumb from "primevue/breadcrumb";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Badge from "primevue/badge";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    report: Object,
    filters: Object
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Software License Report' }
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
    router.get(route('admin.reports.license-report'), {
        date_range: selectedDateRange.value,
        report_type: 'license'
    });
};

const exportReport = () => {
    router.get(route('admin.reports.license-report'), {
        date_range: selectedDateRange.value,
        report_type: 'license',
        export: 'true'
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

const formatPercentage = (num) => {
    return `${(num || 0).toFixed(1)}%`;
};

const getStockStatus = (license) => {
    if (license.available_qty === 0) return { label: 'Out of Stock', severity: 'danger' };
    if (license.available_qty <= license.min_qty) return { label: 'Low Stock', severity: 'warning' };
    return { label: 'In Stock', severity: 'success' };
};

const isExpiringSoon = (expirationDate) => {
    if (!expirationDate) return false;
    const expDate = new Date(expirationDate);
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
    return expDate <= thirtyDaysFromNow && expDate > new Date();
};

const isExpired = (expirationDate) => {
    if (!expirationDate) return false;
    return new Date(expirationDate) < new Date();
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="border-l-4 border-blue-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Licenses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.summary?.total_licenses) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatNumber(report?.summary?.active_licenses) }} active
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="pi pi-key text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Quantity</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.summary?.total_quantity) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatNumber(report?.summary?.available_quantity) }} available
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="pi pi-box text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Utilization Rate</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatPercentage(report?.summary?.utilization_rate) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    License usage efficiency
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="pi pi-chart-line text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Expired Licenses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.summary?.expired_licenses) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Require attention
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="pi pi-clock text-orange-600 text-xl"></i>
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
                            <span>License Status Distribution</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <Badge :value="report?.status_distribution?.active" severity="success" />
                                    <span class="font-medium text-gray-700">Active Licenses</span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    {{ ((report?.status_distribution?.active / report?.summary?.total_licenses) * 100 || 0).toFixed(1) }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <Badge :value="report?.status_distribution?.expired" severity="danger" />
                                    <span class="font-medium text-gray-700">Expired Licenses</span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    {{ ((report?.status_distribution?.expired / report?.summary?.total_licenses) * 100 || 0).toFixed(1) }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <Badge :value="report?.status_distribution?.expiring_soon" severity="warning" />
                                    <span class="font-medium text-gray-700">Expiring Soon</span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    Next 30 days
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Expiration Analytics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-calendar-times text-orange-500"></i>
                            <span>Expiration Analytics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                                <span class="font-medium text-gray-700">Expiring This Month</span>
                                <span class="font-bold text-yellow-600">
                                    {{ formatNumber(report?.expiration_analytics?.expiring_this_month) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <span class="font-medium text-gray-700">Already Expired</span>
                                <span class="font-bold text-red-600">
                                    {{ formatNumber(report?.expiration_analytics?.expired_licenses) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-700">Next Expiration</span>
                                <span class="font-bold text-green-600">
                                    {{ report?.expiration_analytics?.next_expiration ? new Date(report.expiration_analytics.next_expiration).toLocaleDateString() : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Manufacturer Breakdown -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-building text-purple-500"></i>
                        <span>Manufacturer Breakdown</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="report?.manufacturer_breakdown" showGridlines stripedRows class="p-datatable-sm">
                        <Column field="manufacturer" header="Manufacturer" style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-900">{{ slotProps.data.manufacturer }}</div>
                            </template>
                        </Column>
                        <Column field="count" header="License Count" style="min-width: 150px">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.count" severity="info" />
                            </template>
                        </Column>
                        <Column field="total_quantity" header="Total Quantity" style="min-width: 150px">
                            <template #body="slotProps">
                                {{ formatNumber(slotProps.data.total_quantity) }}
                            </template>
                        </Column>
                        <Column header="Market Share" style="min-width: 150px">
                            <template #body="slotProps">
                                {{ ((slotProps.data.count / report?.summary?.total_licenses) * 100 || 0).toFixed(1) }}%
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Low Stock Alerts -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-500"></i>
                        <span>Low Stock Alerts</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="report?.low_stock_alerts" showGridlines stripedRows class="p-datatable-sm">
                        <Column field="license_name" header="License Name" style="min-width: 250px">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-900">{{ slotProps.data.license_name }}</div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.manufacturer }}</div>
                            </template>
                        </Column>
                        <Column field="available_qty" header="Available" style="min-width: 120px">
                            <template #body="slotProps">
                                <Badge 
                                    :value="slotProps.data.available_qty" 
                                    :severity="getStockStatus(slotProps.data).severity" 
                                />
                            </template>
                        </Column>
                        <Column field="min_qty" header="Min Qty" style="min-width: 100px">
                            <template #body="slotProps">
                                {{ formatNumber(slotProps.data.min_qty) }}
                            </template>
                        </Column>
                        <Column field="total_qty" header="Total Qty" style="min-width: 100px">
                            <template #body="slotProps">
                                {{ formatNumber(slotProps.data.total_qty) }}
                            </template>
                        </Column>
                        <Column field="expiration_date" header="Expiration" style="min-width: 150px">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.expiration_date">
                                    <Badge 
                                        :value="new Date(slotProps.data.expiration_date).toLocaleDateString()"
                                        :severity="isExpired(slotProps.data.expiration_date) ? 'danger' : isExpiringSoon(slotProps.data.expiration_date) ? 'warning' : 'success'"
                                    />
                                </div>
                                <Badge v-else value="No Expiry" severity="info" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Recently Added Licenses -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-plus-circle text-green-500"></i>
                        <span>Recently Added Licenses</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="report?.recently_added" showGridlines stripedRows class="p-datatable-sm">
                        <Column field="license_name" header="License Name" style="min-width: 250px">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-900">{{ slotProps.data.license_name }}</div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.product_key }}</div>
                            </template>
                        </Column>
                        <Column field="manufacturer" header="Manufacturer" style="min-width: 150px" />
                        <Column field="total_qty" header="Total Qty" style="min-width: 100px">
                            <template #body="slotProps">
                                {{ formatNumber(slotProps.data.total_qty) }}
                            </template>
                        </Column>
                        <Column field="available_qty" header="Available" style="min-width: 100px">
                            <template #body="slotProps">
                                {{ formatNumber(slotProps.data.available_qty) }}
                            </template>
                        </Column>
                        <Column field="status" header="Status" style="min-width: 100px">
                            <template #body="slotProps">
                                <Badge 
                                    :value="slotProps.data.status ? 'Active' : 'Inactive'" 
                                    :severity="slotProps.data.status ? 'success' : 'danger'" 
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>