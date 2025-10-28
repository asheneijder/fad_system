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
    { label: 'Dashboard Overview' }
];

const props = defineProps({
    summary: Object,
    chartData: Object,
    filters: Object,
});

// Chart configurations
const assetTrendSeries = ref([{
    name: 'Assets Acquired',
    data: props.chartData.asset_trend.series[0]
}]);
const assetTrendOptions = ref({
    chart: {
        type: 'line',
        height: 350
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    xaxis: {
        categories: props.chartData.asset_trend.labels
    },
    colors: ['#6366F1'],
    markers: {
        size: 5
    }
});

const userTrendSeries = ref([{
    name: 'Users Registered',
    data: props.chartData.user_trend.series[0]
}]);
const userTrendOptions = ref({
    chart: {
        type: 'area',
        height: 350
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    xaxis: {
        categories: props.chartData.user_trend.labels
    },
    colors: ['#10B981'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
        }
    }
});

const stationaryUsageSeries = ref(props.chartData.stationary_usage.series[0]);
const stationaryUsageOptions = ref({
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            borderRadius: 4,
            columnWidth: '60%'
        }
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: props.chartData.stationary_usage.labels
    },
    colors: ['#F59E0B']
});

const exportReport = () => {
    router.get(route('reports.dashboard'), { ...props.filters, export: true });
};
</script>

<template>

    <Head title="Dashboard Overview Report" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <Breadcrumb :home="home" :model="items" class="mb-4" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Dashboard Overview Report</h1>
                    <p class="mt-1 text-gray-500">Complete system overview with all key metrics and trends</p>
                </div>
                <Button label="Export to Excel" icon="pi pi-download" @click="exportReport" severity="success" />
            </div>

            <!-- Assets Summary -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-desktop text-blue-500"></i>
                        <span>Assets Overview</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-blue-600 font-semibold">Total Assets</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.assets.total }}</h3>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-green-600 font-semibold">Assigned</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.assets.assigned }}</h3>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <p class="text-orange-600 font-semibold">Assignment Rate</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.assets.assigned_rate }}%</h3>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-purple-600 font-semibold">Total Value</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.assets.total_value }}</h3>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Licenses Summary -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-key text-green-500"></i>
                        <span>Licenses Overview</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-blue-600 font-semibold">Total Licenses</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.licenses.total }}</h3>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <p class="text-orange-600 font-semibold">Expiring Soon</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.licenses.expiring }}</h3>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <p class="text-red-600 font-semibold">Expired</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.licenses.expired }}</h3>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-green-600 font-semibold">Utilization</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ summary.licenses.utilization }}%</h3>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Users & Stationary Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Users Summary -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-users text-orange-500"></i>
                            <span>Users Overview</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                <span class="text-orange-700 font-medium">Total Users</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.users.total }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="text-green-700 font-medium">Active Users</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.users.active }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="text-blue-700 font-medium">Users with Assets</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.users.with_assets }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Stationary Summary -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-shopping-cart text-purple-500"></i>
                            <span>Stationary Overview</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                <span class="text-purple-700 font-medium">Total Items</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.stationary.total }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                <span class="text-orange-700 font-medium">Low Stock</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.stationary.low_stock }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <span class="text-red-700 font-medium">Out of Stock</span>
                                <span class="text-xl font-bold text-gray-800">{{ summary.stationary.out_of_stock
                                    }}</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Asset Trend Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-line text-blue-500"></i>
                            <span>Asset Acquisition Trend</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="line" :options="assetTrendOptions" :series="assetTrendSeries"
                                height="100%" />
                        </div>
                    </template>
                </Card>

                <!-- User Trend Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-area text-green-500"></i>
                            <span>User Registration Trend</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="area" :options="userTrendOptions" :series="userTrendSeries"
                                height="100%" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Stationary Usage Chart -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-bar text-orange-500"></i>
                        <span>Stationary Usage Trend</span>
                    </div>
                </template>
                <template #content>
                    <div class="h-80">
                        <VueApexCharts type="bar" :options="stationaryUsageOptions"
                            :series="[{ data: stationaryUsageSeries }]" height="100%" />
                    </div>
                </template>
            </Card>

            <!-- Claims Summary -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-file-edit text-indigo-500"></i>
                        <span>Claims Overview</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-yellow-50 rounded-lg">
                            <i class="pi pi-clock text-3xl text-yellow-500 mb-3"></i>
                            <p class="text-yellow-600 font-semibold">Pending Claims</p>
                            <h3 class="text-3xl font-bold text-gray-800">{{ summary.claims.pending }}</h3>
                        </div>
                        <div class="text-center p-6 bg-green-50 rounded-lg">
                            <i class="pi pi-check-circle text-3xl text-green-500 mb-3"></i>
                            <p class="text-green-600 font-semibold">Approved Claims</p>
                            <h3 class="text-3xl font-bold text-gray-800">{{ summary.claims.approved }}</h3>
                        </div>
                        <div class="text-center p-6 bg-blue-50 rounded-lg">
                            <i class="pi pi-file text-3xl text-blue-500 mb-3"></i>
                            <p class="text-blue-600 font-semibold">Total Claims</p>
                            <h3 class="text-3xl font-bold text-gray-800">{{ summary.claims.total }}</h3>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>