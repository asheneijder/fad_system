<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Breadcrumb from 'primevue/breadcrumb';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Badge from 'primevue/badge";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    report: Object,
    filters: Object
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'User Activity Report' }
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
    router.get(route('admin.reports.user-activity-report'), {
        date_range: selectedDateRange.value,
        report_type: 'user_activity'
    });
};

const exportReport = () => {
    router.get(route('admin.reports.user-activity-report'), {
        date_range: selectedDateRange.value,
        report_type: 'user_activity',
        export: 'true'
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
                                <p class="text-sm font-medium text-gray-500">Total Users</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.user_statistics?.total_users) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatNumber(report?.user_statistics?.new_users_this_period) }} new this period
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="pi pi-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Users</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.user_statistics?.active_users) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ ((report?.user_statistics?.active_users / report?.user_statistics?.total_users) * 100 || 0).toFixed(1) }}% active rate
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="pi pi-user text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Users with Assets</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.user_statistics?.users_with_assets) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ ((report?.user_statistics?.users_with_assets / report?.user_statistics?.total_users) * 100 || 0).toFixed(1) }}% assignment rate
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="pi pi-desktop text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">New Users</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ formatNumber(report?.user_statistics?.new_users_this_period) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Added during this period
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="pi pi-user-plus text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Users by Asset Count -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-trophy text-yellow-500"></i>
                            <span>Top Users by Asset Assignment</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div
                                v-for="(user, index) in report?.asset_assignments_by_user"
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-sm">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-700">{{ user.user_name }}</div>
                                        <div class="text-xs text-gray-500">{{ user.department || 'No Department' }}</div>
                                    </div>
                                </div>
                                <Badge :value="user.asset_count" severity="info" />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Department Breakdown -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-building text-purple-500"></i>
                            <span>Department Breakdown</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div
                                v-for="dept in report?.department_breakdown"
                                :key="dept.department"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-users text-purple-600 text-sm"></i>
                                    </div>
                                    <span class="font-medium text-gray-700">{{ dept.department }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :value="dept.user_count" severity="info" />
                                    <span class="text-sm text-gray-500">
                                        {{ ((dept.user_count / report?.user_statistics?.total_users) * 100 || 0).toFixed(1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Recent Activities -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-history text-gray-500"></i>
                        <span>Recent User Activities</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="report?.recent_activities" showGridlines stripedRows class="p-datatable-sm">
                        <Column field="user_name" header="User" style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-900">{{ slotProps.data.user_name }}</div>
                            </template>
                        </Column>
                        <Column field="action" header="Action" style="min-width: 150px">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.action" severity="info" />
                            </template>
                        </Column>
                        <Column field="asset_name" header="Asset" style="min-width: 200px">
                            <template #body="slotProps">
                                {{ slotProps.data.asset_name || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="timestamp" header="Timestamp" style="min-width: 200px">
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.timestamp) }}
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- User Distribution Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="text-center">
                    <template #content>
                        <div class="p-4">
                            <div class="p-3 bg-blue-100 rounded-full inline-flex mb-3">
                                <i class="pi pi-chart-bar text-blue-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-500">User Activity Rate</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ ((report?.user_statistics?.users_with_assets / report?.user_statistics?.total_users) * 100 || 0).toFixed(1) }}%
                            </p>
                        </div>
                    </template>
                </Card>

                <Card class="text-center">
                    <template #content>
                        <div class="p-4">
                            <div class="p-3 bg-green-100 rounded-full inline-flex mb-3">
                                <i class="pi pi-check-circle text-green-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Active User Rate</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ ((report?.user_statistics?.active_users / report?.user_statistics?.total_users) * 100 || 0).toFixed(1) }}%
                            </p>
                        </div>
                    </template>
                </Card>

                <Card class="text-center">
                    <template #content>
                        <div class="p-4">
                            <div class="p-3 bg-purple-100 rounded-full inline-flex mb-3">
                                <i class="pi pi-user-plus text-purple-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Growth Rate</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                +{{ formatNumber(report?.user_statistics?.new_users_this_period) }}
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>