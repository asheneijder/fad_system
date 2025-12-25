<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from "primevue/breadcrumb";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Badge from "primevue/badge";
import VueApexCharts from "vue3-apexcharts";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { debounce } from "lodash";

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [
    { label: 'Reports', url: route('admin.reports.index') },
    { label: 'Fixed Asset Listing' }
];

const props = defineProps({
    assets: Array,
    summary: Object,
    chartData: Object,
    categoryBreakdown: Array,
    allCategories: Array,
    statusOptions: Array,
    filters: Object,
});

// Reactive filters
const search = ref(props.filters.search || '');
const category = ref(props.filters.category || null);
const status = ref(props.filters.status || null);

// Debounced search
const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

watch(search, debouncedSearch);

// Apply filters
const applyFilters = () => {
    router.get(route('admin.reports.asset'), {
        search: search.value,
        category: category.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true
    });
};

// Clear filters
const clearFilters = () => {
    search.value = '';
    category.value = null;
    status.value = null;
    router.get(route('admin.reports.asset'), {}, {
        preserveState: true,
        replace: true
    });
};

// Export function
const exportReport = () => {
    const params = new URLSearchParams({
        search: search.value || '',
        category: category.value || '',
        status: status.value || '',
        export: 'true'
    });
    
    window.location.href = route('admin.reports.asset') + '?' + params.toString();
};

// Chart configurations
const statusChartOptions = ref({
    chart: {
        type: 'donut',
        height: 300
    },
    labels: props.chartData.status_chart.labels,
    colors: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'],
    legend: {
        position: 'bottom'
    },
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

const financialChartOptions = ref({
    chart: {
        type: 'bar',
        height: 300
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '60%',
            borderRadius: 4
        }
    },
    colors: ['#3B82F6', '#EF4444', '#10B981'],
    xaxis: {
        categories: props.chartData.financial_chart.labels
    },
    yaxis: {
        labels: {
            formatter: function (value) {
                return '$' + value.toLocaleString();
            }
        }
    }
});

// Status badge styling
const getStatusSeverity = (status) => {
    switch (status) {
        case 'active': return 'success';
        case 'assigned': return 'info';
        case 'available': return 'secondary';
        case 'maintenance': return 'warning';
        case 'retired': return 'danger';
        default: return 'secondary';
    }
};
</script>

<template>

    <Head title="Fixed Asset Listing" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Breadcrumb :home="home" :model="items" class="mb-4" />
                    <h1 class="text-3xl font-bold text-gray-900">Fixed Asset Listing</h1>
                    <p class="mt-1 text-gray-600">Comprehensive overview of all assets, depreciation status, and
                        financial metrics</p>
                </div>
                <Button label="Export to Excel" icon="pi pi-file-excel" @click="exportReport" severity="success" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="border-0 shadow-sm bg-gradient-to-br from-blue-50 to-blue-100">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-600 font-semibold text-sm">Total Assets</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ summary.total_assets }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ summary.assigned_assets }} assigned</p>
                            </div>
                            <i class="pi pi-box text-3xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border-0 shadow-sm bg-gradient-to-br from-green-50 to-green-100">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 font-semibold text-sm">Total Current Value</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">${{ summary.total_current_value }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">${{ summary.total_purchase_cost }} purchase cost
                                </p>
                            </div>
                            <i class="pi pi-dollar text-3xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border-0 shadow-sm bg-gradient-to-br from-purple-50 to-purple-100">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-600 font-semibold text-sm">Depreciation</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ summary.fully_depreciated }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ summary.depreciating_soon }} depreciating soon
                                </p>
                            </div>
                            <i class="pi pi-chart-line text-3xl text-purple-500"></i>
                        </div>
                    </template>
                </Card>

                <Card class="border-0 shadow-sm bg-gradient-to-br from-orange-50 to-orange-100">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-600 font-semibold text-sm">Utilization Rate</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ summary.utilization_rate }}%</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ summary.needs_sighting }} need sighting</p>
                            </div>
                            <i class="pi pi-chart-bar text-3xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <InputText v-model="search" placeholder="Search assets..." class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <Dropdown v-model="category" :options="allCategories" placeholder="All Categories"
                                class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <Dropdown v-model="status" :options="statusOptions" placeholder="All Status"
                                class="w-full" />
                        </div>
                        <div class="flex items-end space-x-2">
                            <Button label="Clear" @click="clearFilters" severity="secondary" class="w-full" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Status Distribution -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-pie text-blue-500"></i>
                            <span>Asset Status Distribution</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="donut" :options="statusChartOptions"
                                :series="chartData.status_chart.series" height="100%" />
                        </div>
                    </template>
                </Card>

                <!-- Financial Overview -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-green-500"></i>
                            <span>Financial Overview</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-80">
                            <VueApexCharts type="bar" :options="financialChartOptions"
                                :series="[{ data: chartData.financial_chart.series }]" height="100%" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Category Breakdown -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-table text-purple-500"></i>
                        <span>Category Breakdown</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="categoryBreakdown" class="p-datatable-sm" showGridlines>
                        <Column field="name" header="Category" sortable>
                            <template #body="{ data }">
                                <div class="font-medium text-gray-900">{{ data.name }}</div>
                            </template>
                        </Column>
                        <Column field="count" header="Count" sortable>
                            <template #body="{ data }">
                                <Badge :value="data.count" severity="info" />
                            </template>
                        </Column>
                        <Column field="assigned_count" header="Assigned" sortable>
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">{{ data.assigned_count }}</span>
                            </template>
                        </Column>
                        <Column field="total_cost" header="Purchase Cost" sortable>
                            <template #body="{ data }">
                                <div class="text-right font-medium">${{ data.total_cost }}</div>
                            </template>
                        </Column>
                        <Column field="depreciation" header="Depreciation" sortable>
                            <template #body="{ data }">
                                <div class="text-right text-red-600">${{ data.depreciation }}</div>
                            </template>
                        </Column>
                        <Column field="total_value" header="Current Value" sortable>
                            <template #body="{ data }">
                                <div class="text-right font-bold text-green-600">${{ data.total_value }}</div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Assets Table -->
            <Card>
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-list text-gray-500"></i>
                            <span>Asset Details</span>
                        </div>
                        <Badge :value="assets.length" severity="info" />
                    </div>
                </template>
                <template #content>
                    <DataTable :value="assets" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[10, 25, 50]"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} assets"
                        class="p-datatable-sm" showGridlines>
                        <Column field="asset_tag_no" header="Asset Tag" sortable>
                            <template #body="{ data }">
                                <div class="font-mono text-sm font-bold text-blue-600">{{ data.asset_tag_no }}</div>
                            </template>
                        </Column>
                        <Column field="category.name" header="Category" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.category?.name" severity="secondary" />
                            </template>
                        </Column>
                        <Column field="asset_name" header="Description" sortable>
                            <template #body="{ data }">
                                <div class="font-medium text-gray-900">{{ data.asset_name }}</div>
                                <div class="text-xs text-gray-500">{{ data.serial_no }}</div>
                            </template>
                        </Column>
                        <Column field="purchase_date" header="Purchase Date" sortable>
                            <template #body="{ data }">
                                <div class="text-sm">{{ data.purchase_date }}</div>
                            </template>
                        </Column>
                        <Column field="status" header="Status" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column field="user.name" header="Assigned To" sortable>
                            <template #body="{ data }">
                                <div v-if="data.user" class="text-sm text-gray-900">{{ data.user.name }}</div>
                                <Tag v-else value="Unassigned" severity="secondary" />
                            </template>
                        </Column>
                        <Column field="current_value" header="Current Value" sortable>
                            <template #body="{ data }">
                                <div class="text-right font-bold" :class="{
                                    'text-green-600': data.current_value > 0,
                                    'text-gray-400': data.current_value == 0
                                }">
                                    ${{ data.current_value }}
                                </div>
                            </template>
                        </Column>
                        <Column field="location" header="Location" sortable>
                            <template #body="{ data }">
                                <div class="text-xs">
                                    <div>{{ data.location }}</div>
                                    <div class="text-gray-500">{{ data.location_2 }}</div>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>