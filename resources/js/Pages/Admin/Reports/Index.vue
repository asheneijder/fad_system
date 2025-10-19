<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Select from "primevue/select";
import Breadcrumb from 'primevue/breadcrumb';
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const home = { icon: 'pi pi-home', url: route('admin.dashboard') }; // Fixed route
const items = [{ label: 'Reports' }];

const reportTypes = ref([
    { value: 'asset', label: 'Asset Management Report', icon: 'pi pi-desktop', description: 'Asset statistics, assignments, and maintenance analytics' },
    { value: 'license', label: 'Software License Report', icon: 'pi pi-key', description: 'License utilization, expirations, and manufacturer breakdown' },
    { value: 'user_activity', label: 'User Activity Report', icon: 'pi pi-users', description: 'User statistics, assignments, and department analytics' },
    { value: 'stationary', label: 'Stationary Items Report', icon: 'pi pi-shopping-cart', description: 'Stock levels, movements, and inventory analytics' },
    { value: 'dashboard', label: 'Comprehensive Dashboard Report', icon: 'pi pi-chart-bar', description: 'Complete system overview with all metrics' },
]);

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

const selectedReport = ref(null);
const selectedDateRange = ref('last_30_days');

const generateReport = (exportReport = false) => {
    if (!selectedReport.value) {
        alert('Please select a report type first.');
        return;
    }

    const params = new URLSearchParams({
        date_range: selectedDateRange.value,
        report_type: selectedReport.value,
    });

    if (exportReport) {
        params.append('export', 'true');
    }

    // Direct URL approach - always works
    const urls = {
        asset: '/admin/reports/asset-report',
        license: '/admin/reports/license-report',
        user_activity: '/admin/reports/user-activity-report',
        stationary: '/admin/reports/stationary-report',
        dashboard: '/admin/reports/dashboard-report',
    };

    const url = urls[selectedReport.value] + '?' + params.toString();
    console.log('Navigating to:', url);
    window.location.href = url;
};

const getReportIcon = (reportType) => {
    const report = reportTypes.value.find(r => r.value === reportType);
    return report ? report.icon : 'pi pi-file';
};
</script>

<template>
    <Head title="Reports" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                    <p class="mt-1 text-gray-500">Generate comprehensive reports for your asset management system</p>
                </div>
            </div>

            <!-- Report Selection -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-line text-blue-500"></i>
                        <span>Generate Report</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Report Type Selection -->
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">Select Report Type</label>
                            <div class="grid grid-cols-1 gap-4">
                                <div
                                    v-for="report in reportTypes"
                                    :key="report.value"
                                    @click="selectedReport = report.value"
                                    :class="[
                                        'p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                                        selectedReport === report.value
                                            ? 'border-blue-500 bg-blue-50 shadow-md'
                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-blue-100 rounded-lg">
                                            <i :class="[report.icon, 'text-blue-600 text-lg']"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800">{{ report.label }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ report.description }}</p>
                                        </div>
                                        <div class="flex items-center">
                                            <i 
                                                class="pi pi-check text-white rounded-full p-1 text-xs"
                                                :class="selectedReport === report.value ? 'bg-blue-500' : 'bg-gray-300'"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range & Actions -->
                        <div class="space-y-6">
                            <!-- Date Range Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <Select
                                    v-model="selectedDateRange"
                                    :options="dateRanges"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select Date Range"
                                    class="w-full"
                                />
                            </div>

                            <!-- Quick Stats -->
                            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-0">
                                <template #content>
                                    <div class="space-y-3">
                                        <h4 class="font-semibold text-gray-800">Report Features</h4>
                                        <ul class="space-y-2 text-sm text-gray-600">
                                            <li class="flex items-center gap-2">
                                                <i class="pi pi-chart-line text-green-500"></i>
                                                <span>Comprehensive analytics</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <i class="pi pi-download text-blue-500"></i>
                                                <span>Export capabilities</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <i class="pi pi-filter text-purple-500"></i>
                                                <span>Custom date ranges</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <i class="pi pi-eye text-orange-500"></i>
                                                <span>Visual charts and graphs</span>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </Card>

                            <!-- Action Buttons -->
                            <div class="space-y-3" v-if="selectedReport">
                                <Button
                                    label="Generate Report"
                                    icon="pi pi-chart-bar"
                                    class="w-full"
                                    @click="generateReport(false)"
                                    severity="primary"
                                />
                                <Button
                                    label="Export Report"
                                    icon="pi pi-download"
                                    class="w-full"
                                    @click="generateReport(true)"
                                    severity="secondary"
                                    outlined
                                />
                            </div>

                            <!-- Instructions -->
                            <div v-else class="text-center py-8 text-gray-500">
                                <i class="pi pi-info-circle text-4xl mb-3 opacity-50"></i>
                                <p>Select a report type to generate analytics</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Recent Reports Quick Access -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-history text-gray-500"></i>
                        <span>Quick Access</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div
                            v-for="report in reportTypes"
                            :key="report.value"
                            @click="selectedReport = report.value"
                            class="p-4 border border-gray-200 rounded-lg text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
                        >
                            <div class="p-3 bg-blue-100 rounded-full inline-flex mb-2">
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

<style scoped>
:deep(.p-card-body) {
    padding: 1.5rem;
}

:deep(.p-card-title) {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}
</style>