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
import { ref } from "vue";

const props = defineProps({
    report: Object,
    filters: Object
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Stationary Items Report' }
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
    router.get(route('admin.reports.stationary-report'), {
        date_range: selectedDateRange.value,
        report_type: 'stationary'
    });
};

const exportReport = () => {
    router.get(route('admin.reports.stationary-report'), {
        date_range: selectedDateRange.value,
        report_type: 'stationary',
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

const getStockStatus = (item) => {
    if (item.quantity === 0) return { label: 'Out of Stock', severity: 'danger' };
    if (item.quantity <= item.min_quantity) return { label: 'Low Stock', severity: 'warning' };
    return { label: 'In Stock', severity: 'success' };
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
                                <p class="text-sm font-medium text-gray-500">Total Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.summary?.total_items) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatNumber(report?.summary?.active_items) }} active
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="pi pi-box text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Stock Value</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatCurrency(report?.summary?.total_stock_value) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Inventory value
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="pi pi-dollar text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.summary?.low_stock_items) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Require attention
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="pi pi-exclamation-triangle text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Avg. Quantity</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.stock_analytics?.avg_quantity) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Per item average
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="pi pi-chart-bar text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Stock Analytics -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-line text-green-500"></i>
                        <span>Stock Analytics</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-blue-50 rounded-lg">
                            <i class="pi pi-box text-blue-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">Total Quantity</p>
                            <p class="mt-1 text-3xl font-bold text-blue-600">
                                {{ formatNumber(report?.stock_analytics?.total_quantity) }}
                            </p>
                        </div>
                        <div class="text-center p-6 bg-green-50 rounded-lg">
                            <i class="pi pi-shopping-cart text-green-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">Total Items</p>
                            <p class="mt-1 text-3xl font-bold text-green-600">
                                {{ formatNumber(report?.stock_analytics?.total_items) }}
                            </p>
                        </div>
                        <div class="text-center p-6 bg-purple-50 rounded-lg">
                            <i class="pi pi-calculator text-purple-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">Average per Item</p>
                            <p class="mt-1 text-3xl font-bold text-purple-600">
                                {{ formatNumber(report?.stock_analytics?.avg_quantity) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Low Stock Alerts -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-red-500"></i>
                            <span>Low Stock Alerts</span>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="report?.low_stock_items" showGridlines stripedRows class="p-datatable-sm">
                            <Column field="name" header="Item Name" style="min-width: 200px">
                                <template #body="slotProps">
                                    <div class="font-medium text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500">{{ slotProps.data.category?.name || 'Uncategorized' }}</div>
                                </template>
                            </Column>
                            <Column field="quantity" header="Current Stock" style="min-width: 120px">
                                <template #body="slotProps">
                                    <Badge 
                                        :value="slotProps.data.quantity" 
                                        :severity="getStockStatus(slotProps.data).severity" 
                                    />
                                </template>
                            </Column>
                            <Column field="min_quantity" header="Min Qty" style="min-width: 100px">
                                <template #body="slotProps">
                                    {{ formatNumber(slotProps.data.min_quantity) }}
                                </template>
                            </Column>
                            <Column field="unit_price" header="Unit Price" style="min-width: 100px">
                                <template #body="slotProps">
                                    {{ formatCurrency(slotProps.data.unit_price) }}
                                </template>
                            </Column>
                            <Column header="Status" style="min-width: 120px">
                                <template #body="slotProps">
                                    <Badge 
                                        :value="getStockStatus(slotProps.data).label" 
                                        :severity="getStockStatus(slotProps.data).severity" 
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Top Moving Items -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-sort-amount-up text-blue-500"></i>
                            <span>Top Moving Items</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in report?.top_moving_items"
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-sm">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-700">{{ item.name }}</div>
                                        <div class="text-xs text-gray-500">Stock: {{ formatNumber(item.quantity) }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">{{ formatCurrency(item.unit_price) }}</div>
                                    <div class="text-xs text-gray-500">per unit</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Movement Analytics -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-sync text-purple-500"></i>
                        <span>Movement Analytics</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-purple-50 rounded-lg">
                            <i class="pi pi-chart-line text-purple-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">Total Movements</p>
                            <p class="mt-1 text-3xl font-bold text-purple-600">
                                {{ formatNumber(report?.movement_analytics?.total_movements) }}
                            </p>
                        </div>
                        <div class="text-center p-6 bg-green-50 rounded-lg">
                            <i class="pi pi-arrow-down-left text-green-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">In Stock Movements</p>
                            <p class="mt-1 text-3xl font-bold text-green-600">
                                {{ formatNumber(report?.movement_analytics?.in_stock_movements) }}
                            </p>
                        </div>
                        <div class="text-center p-6 bg-red-50 rounded-lg">
                            <i class="pi pi-arrow-up-right text-red-600 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">Out Stock Movements</p>
                            <p class="mt-1 text-3xl font-bold text-red-600">
                                {{ formatNumber(report?.movement_analytics?.out_stock_movements) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Inventory Value Summary -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-dollar text-green-500"></i>
                        <span>Inventory Value Summary</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-500">Total Inventory Value</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">
                                {{ formatCurrency(report?.summary?.total_stock_value) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-500">Average Item Value</p>
                            <p class="mt-1 text-xl font-bold text-blue-600">
                                {{ formatCurrency(report?.summary?.total_stock_value / report?.summary?.total_items) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-500">Active Items Value</p>
                            <p class="mt-1 text-xl font-bold text-green-600">
                                {{ formatCurrency(report?.summary?.total_stock_value * (report?.summary?.active_items / report?.summary?.total_items)) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-500">Low Stock Value</p>
                            <p class="mt-1 text-xl font-bold text-orange-600">
                                {{ formatCurrency(report?.low_stock_items?.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0) || 0) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>