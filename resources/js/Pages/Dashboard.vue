<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from "primevue/badge";
import Chart from "primevue/chart";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ProgressBar from "primevue/progressbar";
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";

const props = defineProps({
    basicStats: Object,
    recentActivities: Object,
    stockAlerts: Array,
    requestStats: Object,
    assetStats: Object,
    movementTrends: Object,
});

const chartData = ref({});
const chartOptions = ref({});
const activeChart = ref('requests');

// Statistics Cards Data
const statCards = computed(() => [
    {
        title: 'Total Users',
        value: props.basicStats.users,
        icon: 'pi-users',
        color: 'blue',
        route: 'admin.users.index'
    },
    {
        title: 'Total Assets',
        value: props.basicStats.assets,
        icon: 'pi-box',
        color: 'green',
        route: 'admin.assets.index'
    },
    {
        title: 'Categories',
        value: props.basicStats.categories,
        icon: 'pi-tags',
        color: 'purple',
        route: 'admin.categories.index'
    },
    {
        title: 'Product Models',
        value: props.basicStats.models,
        icon: 'pi-cube',
        color: 'orange',
        route: 'admin.models.index'
    },
    {
        title: 'Licenses',
        value: props.basicStats.licenses,
        icon: 'pi-key',
        color: 'cyan',
        route: 'admin.licenses.index'
    },
    {
        title: 'Stationary Items',
        value: props.basicStats.stationary_items,
        icon: 'pi-pencil',
        color: 'pink',
        route: 'admin.stationary.index'
    },
    {
        title: 'Pending Requests',
        value: props.basicStats.pending_requests,
        icon: 'pi-shopping-cart',
        color: 'red',
        route: 'admin.requests.index'
    }
]);

// Stock Alert Severity
const getStockSeverity = (item) => {
    if (item.status === 'out_of_stock') return 'danger';
    if (item.status === 'low_stock') return 'warning';
    return 'success';
};

const getStockStatusText = (item) => {
    if (item.status === 'out_of_stock') return 'Out of Stock';
    if (item.status === 'low_stock') return 'Low Stock';
    return 'Adequate';
};

// Format numbers
const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

// Chart setup
const initChart = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    
    chartOptions.value = {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    color: documentStyle.getPropertyValue('--text-color')
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: documentStyle.getPropertyValue('--text-color-secondary')
                },
                grid: {
                    color: documentStyle.getPropertyValue('--surface-border')
                }
            },
            y: {
                ticks: {
                    color: documentStyle.getPropertyValue('--text-color-secondary')
                },
                grid: {
                    color: documentStyle.getPropertyValue('--surface-border')
                }
            }
        }
    };

    loadChartData();
};

const loadChartData = async (type = 'requests') => {
    try {
        const response = await fetch(route('dashboard.chart-data', { type }));
        const data = await response.json();
        chartData.value = data;
        activeChart.value = type;
    } catch (error) {
        console.error('Error loading chart data:', error);
    }
};

// Navigation
const navigateTo = (route) => {
    router.visit(route(route));
};

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

onMounted(() => {
    initChart();
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Welcome Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                    <p class="mt-1 text-gray-500">Welcome to your asset management system</p>
                </div>
                <div class="text-sm text-gray-500">
                    Last updated: {{ new Date().toLocaleDateString() }}
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="(card, index) in statCards" :key="index" 
                      class="cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-105"
                      @click="navigateTo(card.route)">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ card.title }}</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900">{{ formatNumber(card.value) }}</p>
                            </div>
                            <div :class="`p-3 bg-${card.color}-100 rounded-full`">
                                <i :class="`pi ${card.icon} text-${card.color}-500 text-xl`"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <i class="pi pi-arrow-right mr-1"></i>
                            <span>View details</span>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts and Main Content -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Chart Section -->
                <Card class="lg:col-span-2">
                    <template #title>
                        <div class="flex items-center justify-between">
                            <span>Analytics Overview</span>
                            <div class="flex space-x-2">
                                <Button label="Requests" :severity="activeChart === 'requests' ? 'primary' : 'secondary'" 
                                        @click="loadChartData('requests')" size="small" />
                                <Button label="Assets" :severity="activeChart === 'assets' ? 'primary' : 'secondary'" 
                                        @click="loadChartData('assets')" size="small" />
                                <Button label="Stationary" :severity="activeChart === 'stationary' ? 'primary' : 'secondary'" 
                                        @click="loadChartData('stationary')" size="small" />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <Chart type="line" :data="chartData" :options="chartOptions" class="h-80" />
                    </template>
                </Card>

                <!-- Stock Alerts -->
                <Card>
                    <template #title>
                        <div class="flex items-center justify-between">
                            <span>Stock Alerts</span>
                            <Badge :value="stockAlerts.length" severity="danger" />
                        </div>
                    </template>
                    <template #content>
                        <div v-if="stockAlerts.length > 0" class="space-y-4">
                            <div v-for="item in stockAlerts" :key="item.id" 
                                 class="p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-gray-900">{{ item.name }}</span>
                                    <Badge :value="getStockStatusText(item)" :severity="getStockSeverity(item)" />
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span>Current: {{ item.current_stock }}</span>
                                    <span>Min: {{ item.min_stock }}</span>
                                </div>
                                <ProgressBar :value="(item.current_stock / (item.min_stock * 2)) * 100" 
                                            :severity="getStockSeverity(item)" 
                                            class="mt-2" />
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-8">
                            <i class="pi pi-check-circle text-4xl mb-3 text-green-500"></i>
                            <p>All items are adequately stocked</p>
                        </div>
                    </template>
                </Card>

                <!-- Recent Activities -->
                <Card>
                    <template #title>
                        <span>Recent Activities</span>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <!-- Recent Users -->
                            <div v-if="recentActivities.users.length > 0">
                                <h4 class="font-medium text-gray-700 mb-2">New Users</h4>
                                <div v-for="user in recentActivities.users" :key="user.id" 
                                     class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ user.job_title || 'No title' }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ formatDate(user.created_at) }}</span>
                                </div>
                            </div>

                            <!-- Recent Assets -->
                            <div v-if="recentActivities.assets.length > 0" class="mt-4">
                                <h4 class="font-medium text-gray-700 mb-2">New Assets</h4>
                                <div v-for="asset in recentActivities.assets" :key="asset.id" 
                                     class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-box text-green-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ asset.name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ asset.asset_tag }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ formatDate(asset.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Request Statistics -->
                <Card>
                    <template #title>
                        <span>Request Statistics</span>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <p class="text-2xl font-bold text-blue-600">{{ requestStats.total }}</p>
                                    <p class="text-sm text-blue-500">Total Requests</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <p class="text-2xl font-bold text-green-600">{{ requestStats.by_status?.approved || 0 }}</p>
                                    <p class="text-sm text-green-500">Approved</p>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <div v-for="(count, status) in requestStats.by_status" :key="status" 
                                     class="flex items-center justify-between">
                                    <Badge :value="status" :severity="status === 'approved' ? 'success' : status === 'pending' ? 'warning' : 'secondary'" />
                                    <span class="font-medium">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Asset Overview -->
                <Card>
                    <template #title>
                        <span>Asset Overview</span>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <p class="text-2xl font-bold text-purple-600">{{ assetStats.total }}</p>
                                <p class="text-sm text-purple-500">Total Assets</p>
                            </div>
                            
                            <div class="space-y-2">
                                <div v-for="(count, category) in assetStats.by_category" :key="category" 
                                     class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">{{ category }}</span>
                                    <span class="font-medium">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <template #title>
                    <span>Quick Actions</span>
                </template>
                <template #content>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                        <Button label="Add Asset" icon="pi pi-plus" severity="primary" outlined
                                @click="navigateTo('admin.assets.create')" />
                        <Button label="Manage Users" icon="pi pi-users" severity="secondary" outlined
                                @click="navigateTo('admin.users.index')" />
                        <Button label="View Requests" icon="pi pi-shopping-cart" severity="success" outlined
                                @click="navigateTo('admin.requests.index')" />
                        <Button label="Stock Items" icon="pi pi-box" severity="warning" outlined
                                @click="navigateTo('admin.stationary.index')" />
                        <Button label="Categories" icon="pi pi-tags" severity="help" outlined
                                @click="navigateTo('admin.categories.index')" />
                        <Button label="Reports" icon="pi pi-chart-bar" severity="info" outlined
                                @click="navigateTo('admin.reports.index')" />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card) {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

:deep(.p-card-body) {
    padding: 1.5rem;
}

:deep(.p-card-title) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

:deep(.p-progressbar) {
    height: 6px;
    border-radius: 3px;
}

.hover\:scale-105:hover {
    transform: scale(1.05);
}
</style>