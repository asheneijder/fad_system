<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from "primevue/badge";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ProgressBar from "primevue/progressbar";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    stats: Object,
    recentRequests: Array,
    myRequestTrends: Array,
    popularItems: Array,
});

// User Request Trends Chart
const requestTrendsChart = computed(() => ({
    series: [{
        name: 'My Requests',
        data: props.myRequestTrends.map(trend => trend.total),
    }],
    chart: {
        type: 'line',
        height: 300,
        toolbar: {
            show: true
        }
    },
    colors: ['#3B82F6'],
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    title: {
        text: 'My Request Trends',
        align: 'left'
    },
    grid: {
        row: {
            colors: ['#f3f4f6', 'transparent'],
            opacity: 0.5
        },
    },
    xaxis: {
        categories: props.myRequestTrends.map(trend => trend.month),
    },
    yaxis: {
        title: {
            text: 'Number of Requests'
        }
    }
}));

// Quick Actions for Users
const quickActions = [
    {
        label: 'Request Items',
        description: 'Request new stationary items',
        icon: 'pi pi-shopping-cart',
        route: 'user.request-items.index',
        color: 'purple'
    },
    {
        label: 'Profile',
        description: 'Update my profile information',
        icon: 'pi pi-user',
        route: 'profile.edit',
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

const getStatusText = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
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
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Dashboard</h1>
                    <p class="text-gray-600 mt-1">Welcome to your personal stationary portal</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-l-4 border-blue-500 shadow-lg">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">My Total Requests</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.my_total_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">All my requests</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-lg">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.my_pending_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">Awaiting approval</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-lg">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.my_approved_requests) }}</p>
                                <p class="text-xs text-gray-400 mt-1">Ready for pickup</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-lg">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Available Items</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.available_items) }}</p>
                                <p class="text-xs text-gray-400 mt-1">In stock</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-box"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Request Trends -->
                <Card class="lg:col-span-2 shadow-lg">
                    <template #content>
                        <VueApexCharts 
                            :series="requestTrendsChart.series" 
                            :options="requestTrendsChart" 
                            height="300"
                        />
                    </template>
                </Card>

                <!-- Quick Actions -->
                <Card class="shadow-lg">
                    <template #content>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <div v-for="action in quickActions" :key="action.label" 
                                class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer"
                                @click="router.get(route(action.route))">
                                <div class="flex items-center gap-3">
                                    <div :class="`p-2 bg-${action.color}-100 rounded-full`">
                                        <i :class="`text-${action.color}-600 pi ${action.icon}`"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 text-sm">{{ action.label }}</h4>
                                        <p class="text-xs text-gray-600">{{ action.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Recent Requests & Popular Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Requests -->
                <Card class="shadow-lg">
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">My Recent Requests</h3>
                            <Button label="View All" icon="pi pi-arrow-right" text size="small"
                                @click="router.get(route('user.request-items.index'))" />
                        </div>
                        <div class="space-y-3">
                            <div v-for="request in recentRequests" :key="request.id"
                                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                                @click="router.get(route('user.requests.show', request.id))">
                                <div class="flex justify-between items-start mb-2">
                                    <p class="font-medium text-gray-900 text-sm truncate flex-1 mr-2">{{ request.purpose }}</p>
                                    <Badge :value="getStatusText(request.status)" 
                                           :severity="getStatusSeverity(request.status)"
                                           class="text-xs capitalize flex-shrink-0" />
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>{{ formatDate(request.created_at) }}</span>
                                    <span>{{ request.items?.length || 0 }} items</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Popular Items -->
                <Card class="shadow-lg">
                    <template #content>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Popular Items This Month</h3>
                        <div class="space-y-3">
                            <div v-for="item in popularItems" :key="item.name"
                                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ item.category }}</p>
                                    </div>
                                    <!-- <Badge :value="item.current_stock" severity="info" class="text-xs" /> -->
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>Requested {{ item.total_requests }} times</span>
                                    <span class="flex items-center gap-1">
                                        <i class="pi pi-box"></i>
                                        In stock
                                    </span>
                                </div>
                            </div>
                        </div>
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
</style>