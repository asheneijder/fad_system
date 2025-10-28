<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import VueApexCharts from "vue3-apexcharts";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'User Activity Report' }
];

const props = defineProps({
    users: Array,
    summary: Object,
    chartData: Object,
    filters: Object,
});

// Chart configurations with safe defaults
const departmentChartSeries = computed(() =>
    props.chartData?.department_chart?.series || []
);

const departmentChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            borderRadius: 4,
            distributed: true
        }
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: props.chartData?.department_chart?.labels || []
    },
    colors: ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4']
}));

const statusChartSeries = computed(() =>
    props.chartData?.status_chart?.series || []
);

const statusChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 350
    },
    labels: props.chartData?.status_chart?.labels || [],
    colors: ['#10B981', '#EF4444'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { width: 300 },
            legend: { position: 'bottom' }
        }
    }]
}));

const exportReport = () => {
    router.get(route('reports.user-activity'), { ...props.filters, export: true });
};
</script>

<template>

    <Head title="User Activity Report" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <Breadcrumb :home="home" :model="items" class="mb-4" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">User Activity Report</h1>
                    <p class="mt-1 text-gray-500">User statistics, assignments, and department analytics</p>
                </div>
                <Button label="Export to Excel" icon="pi pi-download" @click="exportReport" severity="success" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 font-semibold">Total Users</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary?.total || 0 }}</h3>
                            </div>
                            <i class="pi pi-users text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 font-semibold">Active Users</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary?.active || 0 }}</h3>
                            </div>
                            <i class="pi pi-user-plus text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-purple-50 to-purple-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-600 font-semibold">With Assets</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary?.with_assets || 0 }}</h3>
                            </div>
                            <i class="pi pi-desktop text-3xl text-purple-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-600 font-semibold">Avg Assets/User</p>
                                <h3 class="text-2xl font-bold text-gray-800">{{ summary?.average_assets || 0 }}</h3>
                            </div>
                            <i class="pi pi-chart-line text-3xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Department Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-blue-500"></i>
                            <span>Users by Department</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts v-if="departmentChartSeries.length > 0" type="bar"
                                :options="departmentChartOptions" :series="[{ data: departmentChartSeries }]"
                                height="100%" />
                            <div v-else class="h-full flex items-center justify-center text-gray-500">
                                No department data available
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Status Chart -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-donut text-green-500"></i>
                            <span>User Status Distribution</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts v-if="statusChartSeries.length > 0" type="donut"
                                :options="statusChartOptions" :series="statusChartSeries" height="100%" />
                            <div v-else class="h-full flex items-center justify-center text-gray-500">
                                No status data available
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Users Table -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-gray-500"></i>
                        <span>User Details</span>
                    </div>
                </template>
                <template #content>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">User Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Role</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Assets Assigned
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Last Activity
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ user.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ user.email }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ user.department || 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            user.status === 'active' ? 'bg-green-100 text-green-800' :
                                                'bg-red-100 text-red-800'
                                        ]">
                                            {{ user.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <span v-for="role in user.roles" :key="role.id"
                                            class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full mr-1">
                                            {{ role.name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ user.assets_count || 0 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ user.last_login_at || 'Never' }}
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