<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from "primevue/badge";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ProgressBar from "primevue/progressbar";
import Select from "primevue/select";
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    stats: Object,
    monthlyTrends: Array,
    topRequestedItems: Array,
    topRequestors: Array,
    statusDistribution: Array,
    lowStockItems: Array,
    recentPendingRequests: Array,
    departmentRequests: Array,
});

const chartPeriod = ref('monthly');

// Chart Period Options
const periodOptions = ref([
    { label: 'Monthly', value: 'monthly' },
    { label: 'Weekly', value: 'weekly' },
    { label: 'Yearly', value: 'yearly' },
]);

// Request Trends Chart Options
const requestTrendsChart = ref({
    series: [
        {
            name: 'Total Requests',
            data: props.monthlyTrends.map(trend => trend.total),
        },
        {
            name: 'Approved',
            data: props.monthlyTrends.map(trend => trend.approved),
        },
        {
            name: 'Pending',
            data: props.monthlyTrends.map(trend => trend.pending),
        }
    ],
    chart: {
        type: 'line',
        height: 350,
        zoom: {
            enabled: false
        },
        toolbar: {
            show: true
        }
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B'],
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    title: {
        text: 'Request Trends',
        align: 'left'
    },
    grid: {
        row: {
            colors: ['#f3f4f6', 'transparent'],
            opacity: 0.5
        },
    },
    xaxis: {
        categories: props.monthlyTrends.map(trend => trend.month),
    },
    yaxis: {
        title: {
            text: 'Number of Requests'
        }
    },
    legend: {
        position: 'top',
    }
});

// Status Distribution Chart Options
const statusDistributionChart = ref({
    series: props.statusDistribution.map(item => item.count),
    chart: {
        type: 'donut',
        height: 350
    },
    colors: ['#F59E0B', '#10B981', '#EF4444', '#3B82F6', '#6B7280'],
    labels: props.statusDistribution.map(item => 
        item.status.charAt(0).toUpperCase() + item.status.slice(1)
    ),
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                        }
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex];
        }
    },
    legend: {
        position: 'bottom'
    },
    title: {
        text: 'Request Status Distribution',
        align: 'center'
    }
});

// Department Requests Chart Options
const departmentChart = ref({
    series: [{
        data: props.departmentRequests.map(item => item.request_count)
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            borderRadius: 4,
            horizontal: true,
        }
    },
    colors: ['#3B82F6'],
    dataLabels: {
        enabled: true
    },
    xaxis: {
        categories: props.departmentRequests.map(item => item.department),
        title: {
            text: 'Number of Requests'
        }
    },
    yaxis: {
        title: {
            text: 'Department'
        }
    },
    title: {
        text: 'Requests by Department',
        align: 'center'
    }
});

// Quick Actions
const quickActions = [
    {
        label: 'Manage Requests',
        description: 'Review and approve pending requests',
        icon: 'pi pi-inbox',
        route: 'admin.manage-request-items.index',
        color: 'blue'
    },
    {
        label: 'View Inventory',
        description: 'Check stock levels and manage items',
        icon: 'pi pi-box',
        route: 'admin.stationary-items.index',
        color: 'green'
    },
    {
        label: 'User Management',
        description: 'Manage system users and permissions',
        icon: 'pi pi-users',
        route: 'admin.users.index',
        color: 'purple'
    },
    {
        label: 'Create Request',
        description: 'Submit a new stationary request',
        icon: 'pi pi-plus',
        route: 'user.requests.create',
        color: 'orange'
    }
];

// Helper Methods
const getStatusSeverity = (status) => {
    const statusMap = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        completed: 'info',
        cancelled: 'secondary'
    };
    return statusMap[status] || 'secondary';
};

const getPrioritySeverity = (priority) => {
    const priorityMap = {
        low: 'success',
        medium: 'warning',
        high: 'danger',
        urgent: 'danger'
    };
    return priorityMap[priority] || 'secondary';
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStockStatus = (current, min) => {
    if (current === 0) return { severity: 'danger', label: 'Out of Stock' };
    if (current <= min) return { severity: 'warning', label: 'Low Stock' };
    return { severity: 'success', label: 'In Stock' };
};

const getApprovalRate = (request) => {
    if (!request.items) return 0;
    const totalRequested = request.items.reduce((sum, item) => sum + item.quantity, 0);
    const totalApproved = request.items.reduce((sum, item) => sum + (item.approved_quantity || 0), 0);
    
    if (totalRequested === 0) return 100;
    return Math.round((totalApproved / totalRequested) * 100);
};

// Update chart data when period changes
const updateChartData = async () => {
    try {
        const response = await fetch(route('admin.dashboard.chart-data', { period: chartPeriod.value }));
        const data = await response.json();
        
        // Update the request trends chart
        requestTrendsChart.value = {
            ...requestTrendsChart.value,
            series: [
                {
                    name: 'Total Requests',
                    data: data.map(item => item.total),
                },
                {
                    name: 'Approved',
                    data: data.map(item => item.approved),
                }
            ],
            xaxis: {
                ...requestTrendsChart.value.xaxis,
                categories: data.map(item => item.label),
            }
        };
    } catch (error) {
        console.error('Failed to fetch chart data:', error);
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600 mt-1">Welcome to your stationary management system</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">Chart Period:</span>
                    <Select v-model="chartPeriod" :options="periodOptions" optionLabel="label" optionValue="value"
                        @change="updateChartData" class="w-32" />
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-l-4 border-blue-500 shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
                    @click="router.get(route('admin.manage-request-items.index'))">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">All time requests</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
                    @click="router.get(route('admin.manage-request-items.index', { status: 'pending' }))">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Review</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.pending_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">Needs attention</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
                    @click="router.get(route('admin.manage-request-items.index', { status: 'approved' }))">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.approved_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">Ready for completion</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
                    @click="router.get(route('admin.stationary-items.index'))">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.low_stock_items) }}</p>
                                <p class="text-xs text-gray-400 mt-1">Need restocking</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-xl text-red-600 pi pi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Request Trends Chart -->
                <Card class="xl:col-span-2 shadow-lg">
                    <template #content>
                        <VueApexCharts 
                            :series="requestTrendsChart.series" 
                            :options="requestTrendsChart" 
                            height="350"
                        />
                    </template>
                </Card>

                <!-- Status Distribution -->
                <Card class="shadow-lg">
                    <template #content>
                        <VueApexCharts 
                            :series="statusDistributionChart.series" 
                            :options="statusDistributionChart" 
                            height="350"
                        />
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card class="shadow-lg">
                <template #content>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="action in quickActions" :key="action.label" 
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                            @click="router.get(route(action.route))">
                            <div class="flex items-center gap-3">
                                <div :class="`p-3 bg-${action.color}-100 rounded-full`">
                                    <i :class="`text-${action.color}-600 pi ${action.icon}`"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ action.label }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ action.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Bottom Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Top Requested Items -->
                <Card class="shadow-lg">
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Top Requested Items</h3>
                            <Badge :value="'This Month'" severity="info" />
                        </div>
                        <div class="space-y-3">
                            <div v-for="(item, index) in topRequestedItems" :key="item.name" 
                                class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-bold text-blue-600">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ item.category }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900">{{ formatNumber(item.total_requested) }}</p>
                                    <p class="text-xs text-gray-500">{{ item.request_count }} requests</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Recent Pending Requests -->
                <Card class="shadow-lg">
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Recent Pending Requests</h3>
                            <Button label="View All" icon="pi pi-arrow-right" text size="small"
                                @click="router.get(route('admin.manage-request-items.index', { status: 'pending' }))" />
                        </div>
                        <div class="space-y-3">
                            <div v-for="request in recentPendingRequests" :key="request.id"
                                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                                @click="router.get(route('admin.manage-request-items.show', request.id))">
                                <div class="flex justify-between items-start mb-2">
                                    <p class="font-medium text-gray-900 text-sm truncate">{{ request.purpose }}</p>
                                    <Badge :value="request.priority" :severity="getPrioritySeverity(request.priority)" 
                                        class="capitalize text-xs" />
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>{{ request.user.name }}</span>
                                    <span>{{ formatDate(request.created_at) }}</span>
                                </div>
                                <div class="mt-2">
                                    <ProgressBar :value="getApprovalRate(request)" :showValue="false" class="h-2" />
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Approval Progress</span>
                                        <span>{{ getApprovalRate(request) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Low Stock Alert -->
                <Card class="shadow-lg">
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Low Stock Alert</h3>
                            <Badge :value="lowStockItems.length" severity="danger" />
                        </div>
                        <div class="space-y-3">
                            <div v-for="item in lowStockItems" :key="item.id"
                                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                                @click="router.get(route('admin.stationary-items.index'))">
                                <div class="flex justify-between items-center mb-2">
                                    <p class="font-medium text-gray-900 text-sm">{{ item.name }}</p>
                                    <Badge :value="getStockStatus(item.current_stock, item.min_stock).label"
                                        :severity="getStockStatus(item.current_stock, item.min_stock).severity"
                                        class="text-xs" />
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>Current: {{ item.current_stock }} {{ item.unit }}</span>
                                    <span>Min: {{ item.min_stock }} {{ item.unit }}</span>
                                </div>
                                <div class="mt-2">
                                    <ProgressBar :value="(item.current_stock / item.min_stock) * 100" 
                                                :showValue="false" 
                                                :class="{
                                                    'p-progressbar-danger': item.current_stock <= item.min_stock,
                                                    'p-progressbar-warning': item.current_stock > item.min_stock && item.current_stock <= item.min_stock * 2,
                                                    'p-progressbar-success': item.current_stock > item.min_stock * 2
                                                }" 
                                                class="h-2" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Additional Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Requestors -->
                <Card class="shadow-lg">
                    <template #content>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Requestors (This Month)</h3>
                        <DataTable :value="topRequestors" showGridlines class="p-datatable-sm">
                            <Column field="name" header="User">
                                <template #body="slotProps">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ slotProps.data.name }}</p>
                                        <p class="text-xs text-gray-500">{{ slotProps.data.department || '—' }}</p>
                                    </div>
                                </template>
                            </Column>
                            <Column field="request_count" header="Requests" style="width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.request_count" severity="info" />
                                </template>
                            </Column>
                            <Column field="approved_count" header="Approved" style="width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.approved_count" severity="success" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Department-wise Requests -->
                <Card class="shadow-lg">
                    <template #content>
                        <VueApexCharts 
                            :series="departmentChart.series" 
                            :options="departmentChart" 
                            height="350"
                        />
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1.5rem;
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
}

:deep(.p-progressbar) {
    height: 0.5rem;
}
</style>