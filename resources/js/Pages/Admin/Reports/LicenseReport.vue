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
    { label: 'License Report' }
];

const props = defineProps({
    licenses: Array,
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
    colors: ['#6366F1', '#10B981', '#F59E0B', '#EF4444'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { width: 300 },
            legend: { position: 'bottom' }
        }
    }]
});

const manufacturerChartSeries = ref(props.chartData.manufacturer_chart.series);
const manufacturerChartOptions = ref({
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4
        }
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: props.chartData.manufacturer_chart.labels
    },
    colors: ['#6366F1']
});

const exportReport = () => {
    router.get(route('reports.license'), { ...props.filters, export: true });
};
</script>

<template>

    <Head title="License Management Report" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <Breadcrumb :home="home" :model="items" class="mb-4" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">License Management Report</h1>
                    <p class="mt-1 text-gray-500">Software license utilization and expiration overview</p>
                </div>
                <Button label="Export to Excel" icon="pi pi-download" @click="exportReport" severity="success" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 font-semibold">Total Licenses</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.total }}</h3>
                            </div>
                            <i class="pi pi-key text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 font-semibold">Utilization Rate</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.utilization_rate }}%</h3>
                            </div>
                            <i class="pi pi-percentage text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-600 font-semibold">Expiring Soon</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.expiring_soon }}</h3>
                            </div>
                            <i class="pi pi-clock text-3xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-red-50 to-red-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-600 font-semibold">Expired</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary.expired }}</h3>
                            </div>
                            <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
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
                            <span>Licenses by Status</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="pie" :options="statusChartOptions" :series="statusChartSeries"
                                height="100%" />
                        </div>
                    </template>
                </Card>

                <!-- Manufacturer Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-green-500"></i>
                            <span>Licenses by Manufacturer</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="bar" :options="manufacturerChartOptions"
                                :series="[{ data: manufacturerChartSeries }]" height="100%" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Licenses Table -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-gray-500"></i>
                        <span>License Details</span>
                    </div>
                </template>
                <template #content>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">License Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Manufacturer
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Total Qty</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Available</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Expiration Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="license in licenses" :key="license.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ license.license_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ license.manufacturer }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            license.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ license.status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ license.total_qty }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ license.available_qty }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <span :class="{
                                            'text-red-600 font-semibold': new Date(license.expiration_date) < new Date(),
                                            'text-orange-600 font-semibold': new Date(license.expiration_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                                        }">
                                            {{ license.expiration_date }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>