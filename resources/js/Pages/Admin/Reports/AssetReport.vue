<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import VueApexCharts from "vue3-apexcharts";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Asset Report' }
];

const props = defineProps({
    assets: Array,
    summary: Object,
    chartData: Object,
    filters: Object,
});

// Chart configurations
const statusChartSeries = ref(props.chartData.status_chart.series);
const statusChartOptions = ref({
    chart: {
        type: 'pie',
        height: 350
    },
    labels: props.chartData.status_chart.labels,
    colors: ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { width: 300 },
            legend: { position: 'bottom' }
        }
    }],
    legend: {
        position: 'bottom'
    }
});

const assignmentChartSeries = ref(props.chartData.category_chart.series);
const assignmentChartOptions = ref({
    chart: {
        type: 'donut',
        height: 350
    },
    labels: props.chartData.category_chart.labels,
    colors: ['#10B981', '#EF4444'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { width: 300 },
            legend: { position: 'bottom' }
        }
    }],
    plotOptions: {
        pie: {
            donut: {
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Assets',
                        color: '#374151'
                    }
                }
            }
        }
    }
});

const exportReport = () => {
    router.get(route('admin.reports.asset'), { ...props.filters, export: true });
};
</script>

<template>

    <Head title="Asset Management Report" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <Breadcrumb :home="home" :model="items" class="mb-4" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Asset Management Report</h1>
                    <p class="mt-1 text-gray-500">Comprehensive overview of all assets and their status</p>
                </div>
                <Button label="Export to Excel" icon="pi pi-download" @click="exportReport" severity="success" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 font-semibold">Total Assets</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.total }}</h3>
                            </div>
                            <i class="pi pi-box text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 font-semibold">Total Value</p>
                                <h3 class="text-2xl font-bold text-gray-800">${{ summary.total_value }}</h3>
                            </div>
                            <i class="pi pi-dollar text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-purple-50 to-purple-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-600 font-semibold">Assigned Assets</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.assigned }}</h3>
                            </div>
                            <i class="pi pi-user text-3xl text-purple-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-600 font-semibold">Average Value</p>
                                <h3 class="text-2xl font-bold text-gray-800">${{ summary.average_value }}</h3>
                            </div>
                            <i class="pi pi-chart-line text-3xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Status Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-pie text-blue-500"></i>
                            <span>Assets by Status</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="pie" :options="statusChartOptions" :series="statusChartSeries"
                                height="100%" />
                        </div>
                    </template>
                </Card>

                <!-- Assignment Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-donut text-green-500"></i>
                            <span>Assignment Distribution</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="donut" :options="assignmentChartOptions"
                                :series="assignmentChartSeries" height="100%" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Assets Table -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-gray-500"></i>
                        <span>Asset Details</span>
                    </div>
                </template>
                <template #content>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Asset Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Assigned To</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Current Value
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Purchase Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ asset.asset_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ asset.category?.name || 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            asset.status === 'active' ? 'bg-green-100 text-green-800' :
                                                asset.status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                        ]">
                                            {{ asset.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ asset.user?.name || 'Unassigned' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">${{ asset.current_value }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ asset.purchase_date }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>