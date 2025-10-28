<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Select from "primevue/select";
import Breadcrumb from "primevue/breadcrumb";
import VueApexCharts from "vue3-apexcharts";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [{ label: 'Reports' }];

const props = defineProps({
    reportTypes: Array,
    dateRanges: Array,
    quickStats: Object,
});

const selectedReport = ref(null);
const selectedDateRange = ref('last_30_days');

const generateReport = (exportReport = false) => {
    if (!selectedReport.value) {
        alert('Please select a report type first.');
        return;
    }

    const params = {
        date_range: selectedDateRange.value,
        ...(exportReport && { export: true })
    };

    const routes = {
        asset: 'admin.reports.asset',
        license: 'admin.reports.license',
        user_activity: 'admin.reports.user-activity',
        stationary: 'admin.reports.stationary',
        dashboard: 'admin.reports.dashboard',
    };

    if (routes[selectedReport.value]) {
        router.get(route(routes[selectedReport.value]), params);
    }
};

const quickGenerate = (reportType) => {
    selectedReport.value = reportType;
    setTimeout(() => {
        generateReport(false);
    }, 100);
};

// ApexCharts configuration
const chartSeries = ref([
    props.quickStats.total_assets,
    props.quickStats.total_licenses,
    props.quickStats.total_users,
    props.quickStats.low_stock_items,
]);

const chartOptions = ref({
    chart: {
        type: 'donut',
        height: 350
    },
    labels: ['Assets', 'Licenses', 'Users', 'Stationary'],
    colors: ['#6366F1', '#10B981', '#F59E0B', '#EF4444'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }],
    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '16px',
                        fontFamily: 'Inter, sans-serif',
                        color: '#6B7280',
                    },
                    value: {
                        show: true,
                        fontSize: '20px',
                        fontFamily: 'Inter, sans-serif',
                        color: '#374151',
                        formatter: function (val) {
                            return val;
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        color: '#6B7280',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => {
                                return a + b;
                            }, 0);
                        }
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        colors: ['#fff']
    },
    states: {
        hover: {
            filter: {
                type: 'darken',
                value: 0.1
            }
        }
    }
});
</script>

<template>

    <Head title="Reports Dashboard" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <Breadcrumb :home="home" :model="items" class="mb-4" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                    <p class="mt-1 text-gray-500">Generate comprehensive reports for your asset management system</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 font-semibold">Total Assets</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ quickStats.total_assets }}</h3>
                            </div>
                            <i class="pi pi-desktop text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 font-semibold">Total Licenses</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ quickStats.total_licenses }}</h3>
                            </div>
                            <i class="pi pi-key text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-600 font-semibold">Active Users</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ quickStats.total_users }}</h3>
                            </div>
                            <i class="pi pi-users text-3xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-red-50 to-red-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-600 font-semibold">Low Stock Items</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ quickStats.low_stock_items }}</h3>
                            </div>
                            <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Report Selection -->
                <div class="lg:col-span-2">
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-line text-blue-500"></i>
                                <span>Generate Report</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-6">
                                <div class="space-y-4">
                                    <label class="block text-sm font-medium text-gray-700">Select Report Type</label>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="report in reportTypes" :key="report.value"
                                            @click="selectedReport = report.value" :class="[
                                                'p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                                                selectedReport === report.value
                                                    ? 'border-blue-500 bg-blue-50 shadow-md'
                                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                            ]">
                                            <div class="flex items-start gap-3">
                                                <div class="p-2 bg-blue-100 rounded-lg">
                                                    <i :class="[report.icon, 'text-blue-600 text-lg']"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="font-semibold text-gray-800">{{ report.label }}</h3>
                                                    <p class="text-sm text-gray-600 mt-1">{{ report.description }}</p>
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="pi pi-check text-white rounded-full p-1 text-xs"
                                                        :class="selectedReport === report.value ? 'bg-blue-500' : 'bg-gray-300'"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                        <Select v-model="selectedDateRange" :options="dateRanges" optionLabel="label"
                                            optionValue="value" class="w-full" />
                                    </div>
                                    <div class="flex items-end space-x-2" v-if="selectedReport">
                                        <Button label="View Report" icon="pi pi-eye" class="w-full"
                                            @click="generateReport(false)" severity="primary" />
                                        <Button label="Export" icon="pi pi-download" class="w-full"
                                            @click="generateReport(true)" severity="success" outlined />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Quick Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-purple-500"></i>
                            <span>System Overview</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-64">
                            <VueApexCharts type="donut" :options="chartOptions" :series="chartSeries" height="100%" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Access -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-bolt text-yellow-500"></i>
                        <span>Quick Access</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div v-for="report in reportTypes" :key="report.value" @click="quickGenerate(report.value)"
                            class="p-4 border border-gray-200 rounded-lg text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                            <div
                                class="p-3 bg-blue-100 rounded-full inline-flex mb-2 group-hover:bg-blue-200 transition-colors">
                                <i :class="[report.icon, 'text-blue-600 text-xl']"></i>
                            </div>
                            <h4 class="font-medium text-gray-800 text-sm">{{ report.label.split(' ')[0] }}</h4>
                            <p class="text-xs text-gray-500 mt-1">Report</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>