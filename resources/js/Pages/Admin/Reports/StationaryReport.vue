    <script setup>
    import AppLayout from "@/sakai/layout/AppLayout.vue";
    import Card from "primevue/card";
    import Button from "primevue/button";
    import Breadcrumb from "primevue/breadcrumb";
    import VueApexCharts from "vue3-apexcharts";
    import { Head, router } from "@inertiajs/vue3";
    import { ref, computed, onMounted, nextTick } from "vue";

    const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
    const items = [
        { label: 'Reports', url: route('admin.reports.index') },
        { label: 'Stationary Report' }
    ];

    const props = defineProps({
        stationaryItems: Array,
        hotItems: [Array, Object],
        usageStats: Array,
        summary: Object,
        chartData: Object,
        filters: Object,
    });

    // Convert hotItems to array if it's an object/collection
    const hotItemsArray = computed(() => {
        if (Array.isArray(props.hotItems)) {
            return props.hotItems;
        } else if (props.hotItems && typeof props.hotItems === 'object') {
            return Object.values(props.hotItems);
        }
        return [];
    });

    // Hot items scrolling
    const hotItemsContainer = ref(null);
    const hotItemsScrollPosition = ref(0);
    const isHotItemsScrollEnd = ref(false);

    // Safe number formatting function
    const formatPrice = (price) => {
        if (!price && price !== 0) return '0.00';
        const num = typeof price === 'string' ? parseFloat(price) : price;
        return isNaN(num) ? '0.00' : num.toFixed(2);
    };

    // Chart configurations with safe defaults
    const categoryChartSeries = computed(() =>
        props.chartData?.category_chart?.series || [1]
    );

    const categoryChartOptions = computed(() => ({
        chart: {
            type: 'pie',
            height: 350
        },
        labels: props.chartData?.category_chart?.labels || ['No Data'],
        colors: ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { width: 300 },
                legend: { position: 'bottom' }
            }
        }]
    }));

    const stockChartSeries = computed(() =>
        props.chartData?.stock_chart?.series || [1]
    );

    const stockChartOptions = computed(() => ({
        chart: {
            type: 'donut',
            height: 350
        },
        labels: props.chartData?.stock_chart?.labels || ['No Data'],
        colors: ['#10B981', '#F59E0B', '#EF4444'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { width: 300 },
                legend: { position: 'bottom' }
            }
        }],
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Items',
                            color: '#374151'
                        }
                    }
                }
            }
        }
    }));

    // Chart rendering state
    const chartsMounted = ref(false);

    onMounted(() => {
        setTimeout(() => {
            chartsMounted.value = true;
        }, 100);
    });

    const exportReport = () => {
        // Build the export URL with parameters
        const params = new URLSearchParams({
            ...props.filters,
            export: true
        });

        // Use window.location to trigger download
        window.location.href = route('admin.reports.stationary') + '?' + params.toString();
    };

    // Generate transaction log data for each item from completed requests
    const getTransactionLog = (item) => {
        if (!item.request_item_details || !Array.isArray(item.request_item_details)) {
            return [];
        }

        // Filter only completed request details
        const completedRequests = item.request_item_details.filter(detail =>
            detail.request_item && detail.request_item.status === 'completed'
        );

        if (completedRequests.length === 0) {
            return [];
        }

        let balance = item.current_stock;

        const transactions = completedRequests.map(detail => {
            const request = detail.request_item;

            // Get user and request information
            let userName = 'Unknown';
            let userDepartment = 'Unknown Department';
            let userEmail = '';
            let purpose = '';
            let approverName = 'Unknown';
            let approvedAt = '';

            if (request && request.user) {
                userName = request.user.name || 'Unknown';
                userDepartment = request.user.department || 'Unknown Department';
                userEmail = request.user.email || '';
            }

            if (request) {
                purpose = request.purpose || 'No purpose specified';
                approverName = request.approved_by?.name || 'Unknown';
                approvedAt = request.approved_at ?
                    new Date(request.approved_at).toLocaleDateString('en-GB') : 'N/A';
            }

            // For outgoing transactions (stock reduction)
            const outQuantity = detail.final_quantity || detail.quantity || 0;

            const transaction = {
                date: request?.created_at ? new Date(request.created_at).toLocaleDateString('en-GB') : 'N/A',
                description: `Request: ${purpose}`,
                in: 0, // Requests typically only reduce stock
                out: outQuantity,
                balance: balance - outQuantity, // Update balance after this transaction
                cost_price: formatPrice(item.cost_price),
                selling_price: formatPrice(item.selling_price),
                amount: outQuantity * (parseFloat(item.cost_price) || 0),
                user: userName,
                department: userDepartment,
                email: userEmail,
                purpose: purpose,
                approver: approverName,
                approved_date: approvedAt,
                requested_quantity: detail.quantity || 0,
                approved_quantity: detail.approved_quantity || detail.quantity || 0
            };

            // Update running balance for next transaction
            balance -= outQuantity;

            return transaction;
        });

        return transactions.reverse();
    };

    // Calculate totals for transaction log
    const getTransactionTotals = (transactions) => {
        return {
            totalIn: transactions.reduce((sum, t) => sum + t.in, 0),
            totalOut: transactions.reduce((sum, t) => sum + t.out, 0),
            totalAmount: transactions.reduce((sum, t) => sum + t.amount, 0)
        };
    };

    const scrollHotItems = (direction) => {
        if (!hotItemsContainer.value) return;

        const container = hotItemsContainer.value;
        const scrollAmount = 300; // Adjust based on card width + gap

        if (direction === 'left') {
            container.scrollLeft -= scrollAmount;
        } else {
            container.scrollLeft += scrollAmount;
        }
    };

    const updateHotItemsScrollPosition = () => {
        if (!hotItemsContainer.value) return;

        const container = hotItemsContainer.value;
        hotItemsScrollPosition.value = container.scrollLeft;
        isHotItemsScrollEnd.value = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
    };

    // Initialize scroll position
    onMounted(() => {
        nextTick(() => {
            if (hotItemsContainer.value) {
                updateHotItemsScrollPosition();
            }
        });
    });
</script>

    <template>

        <Head title="Stationary Items Report" />
        <AppLayout>
            <div class="p-6 space-y-6">
                <Breadcrumb :home="home" :model="items" class="mb-4" />

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Stationary Items Report</h1>
                        <p class="mt-1 text-gray-500">Inventory levels, movements, and completed request history</p>
                    </div>
                    <Button label="Export to Excel" icon="pi pi-download" @click="exportReport" severity="success" />
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-600 font-semibold">Total Items</p>
                                    <h3 class="text-2xl font-bold text-gray-800">{{ summary?.total || 0 }}</h3>
                                </div>
                                <i class="pi pi-shopping-cart text-3xl text-blue-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-600 font-semibold">Completed Requests</p>
                                    <h3 class="text-2xl font-bold text-gray-800">{{ summary?.completed_requests || 0 }}
                                    </h3>
                                </div>
                                <i class="pi pi-check-circle text-3xl text-green-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-orange-600 font-semibold">Low Stock</p>
                                    <h3 class="text-2xl font-bold text-gray-800">{{ summary?.low_stock || 0 }}</h3>
                                </div>
                                <i class="pi pi-exclamation-circle text-3xl text-orange-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card class="bg-gradient-to-r from-red-50 to-red-100 border-0">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-red-600 font-semibold">Out of Stock</p>
                                    <h3 class="text-2xl font-bold text-gray-800">{{ summary?.out_of_stock || 0 }}</h3>
                                </div>
                                <i class="pi pi-times-circle text-3xl text-red-500"></i>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Hot Items Alert -->
                <Card v-if="hotItemsArray.length > 0" class="border-orange-200 bg-orange-50">
                    <template #title>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-orange-800">
                                <i class="pi pi-exclamation-triangle"></i>
                                <span>Low Stock Alert ({{ hotItemsArray.length }} items)</span>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="relative">
                            <!-- Scroll buttons for larger screens -->
                            <button @click="scrollHotItems('left')"
                                class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-orange-500 hover:bg-orange-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100"
                                :class="{ 'opacity-30 cursor-not-allowed': hotItemsScrollPosition === 0 }">
                                <i class="pi pi-chevron-left text-sm"></i>
                            </button>

                            <button @click="scrollHotItems('right')"
                                class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-orange-500 hover:bg-orange-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100"
                                :class="{ 'opacity-30 cursor-not-allowed': isHotItemsScrollEnd }">
                                <i class="pi pi-chevron-right text-sm"></i>
                            </button>

                            <!-- Scrollable container -->
                            <div ref="hotItemsContainer" class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide group"
                                style="scroll-behavior: smooth;" @scroll="updateHotItemsScrollPosition">
                                <div v-for="item in hotItemsArray" :key="item.id"
                                    class="flex-shrink-0 w-64 p-4 bg-white rounded-lg border border-orange-200 hover:border-orange-300 hover:shadow-md transition-all duration-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-gray-800 truncate" :title="item.name">
                                                {{ item.name }}
                                            </p>
                                            <div class="mt-2 space-y-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Current Stock:</span>
                                                    <span class="font-semibold text-sm px-2 py-1 rounded" :class="{
                                                        'bg-red-100 text-red-800': item.current_stock === 0,
                                                        'bg-orange-100 text-orange-800': item.current_stock > 0 && item.current_stock <= item.min_stock,
                                                        'bg-green-100 text-green-800': item.current_stock > item.min_stock
                                                    }">
                                                        {{ item.current_stock }} {{ item.unit }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Min Stock:</span>
                                                    <span class="font-semibold text-gray-800 text-sm">
                                                        {{ item.min_stock }} {{ item.unit }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm text-gray-600">Status:</span>
                                                    <span class="text-xs font-semibold px-2 py-1 rounded capitalize"
                                                        :class="{
                                                            'bg-red-100 text-red-800': item.current_stock === 0,
                                                            'bg-orange-100 text-orange-800': item.current_stock > 0 && item.current_stock <= item.min_stock,
                                                            'bg-green-100 text-green-800': item.current_stock > item.min_stock
                                                        }">
                                                        {{
                                                            item.current_stock === 0 ? 'Out of Stock' :
                                                                item.current_stock <= item.min_stock ? 'Low Stock' : 'In Stock'
                                                        }} </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-shrink-0">
                                            <i class="pi text-2xl" :class="{
                                                'pi-times-circle text-red-500': item.current_stock === 0,
                                                'pi-exclamation-triangle text-orange-500': item.current_stock > 0 && item.current_stock <= item.min_stock,
                                                'pi-check-circle text-green-500': item.current_stock > item.min_stock
                                            }"></i>
                                        </div>
                                    </div>

                                    <!-- Stock level indicator -->
                                    <div class="mt-3">
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>Stock Level</span>
                                            <span>{{ Math.round((item.current_stock / Math.max(item.min_stock * 2,
                                                item.current_stock)) * 100) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-500" :class="{
                                                'bg-red-500': item.current_stock === 0,
                                                'bg-orange-500': item.current_stock > 0 && item.current_stock <= item.min_stock,
                                                'bg-green-500': item.current_stock > item.min_stock
                                            }"
                                                :style="{ width: `${Math.min(100, (item.current_stock / Math.max(item.min_stock * 2, item.current_stock)) * 100)}%` }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Scroll indicators -->
                            <div class="flex justify-center mt-3 space-x-1">
                                <div v-for="(item, index) in hotItemsArray" :key="index"
                                    class="w-2 h-2 rounded-full transition-all duration-200" :class="{
                                        'bg-orange-500': Math.floor(hotItemsScrollPosition / 272) === index,
                                        'bg-orange-200': Math.floor(hotItemsScrollPosition / 272) !== index
                                    }"></div>
                            </div>
                        </div>
                    </template>
                </Card>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Category Chart -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-pie text-blue-500"></i>
                                <span>Items by Category</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-80">
                                <VueApexCharts
                                    v-if="chartsMounted && categoryChartSeries.length > 0 && categoryChartSeries[0] !== 1"
                                    type="pie" :options="categoryChartOptions" :series="categoryChartSeries"
                                    height="100%" />
                                <div v-else class="h-full flex items-center justify-center text-gray-500">
                                    No category data available
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Stock Status Chart -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-donut text-green-500"></i>
                                <span>Stock Status Distribution</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-80">
                                <VueApexCharts
                                    v-if="chartsMounted && stockChartSeries.length > 0 && stockChartSeries[0] !== 1"
                                    type="donut" :options="stockChartOptions" :series="stockChartSeries"
                                    height="100%" />
                                <div v-else class="h-full flex items-center justify-center text-gray-500">
                                    No stock data available
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Transaction Log for Each Item -->
                <div v-for="item in stationaryItems" :key="item.id" class="space-y-6">
                    <!-- Transaction Log -->
                    <Card>
                        <template #title>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-file-text text-gray-500"></i>
                                    <span>{{ item.name }} - Transaction Log (Completed Requests)</span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Current Stock: <span :class="[
                                        'font-semibold',
                                        item.current_stock === 0 ? 'text-red-600' :
                                            item.current_stock <= item.min_stock ? 'text-orange-600' :
                                                'text-green-600'
                                    ]">{{ item.current_stock }}</span>
                                    | Min Stock: {{ item.min_stock }}
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 w-24">DATE</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">DESCRIPTION</th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-16">IN</th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-16">OUT</th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-20">BALANCE
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-20">COST P.
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-20">SELL. P.
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 w-20">AMOUNT
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Opening Balance -->
                                        <tr class="border-b border-gray-100">
                                            <td class="px-3 py-2 text-gray-600"></td>
                                            <td class="px-3 py-2 text-gray-600 font-medium">BALANCE B/F</td>
                                            <td class="px-3 py-2 text-center"></td>
                                            <td class="px-3 py-2 text-center"></td>
                                            <td class="px-3 py-2 text-center font-semibold text-blue-600">{{
                                                item.current_stock }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">RM{{
                                                formatPrice(item.cost_price)
                                                }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">RM{{
                                                formatPrice(item.selling_price) }}</td>
                                            <td class="px-3 py-2 text-center"></td>
                                        </tr>

                                        <!-- Transactions -->
                                        <tr v-for="(transaction, index) in getTransactionLog(item)" :key="index"
                                            class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-600">{{ transaction.date }}</td>
                                            <td class="px-3 py-2 text-gray-600">
                                                <div class="font-medium">{{ transaction.description }}</div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <div class="font-semibold">{{ transaction.user }}</div>
                                                    <div>{{ transaction.department }}</div>
                                                    <div class="text-blue-600">{{ transaction.email }}</div>
                                                    <div class="text-green-600 mt-1">Approved by: {{
                                                        transaction.approver }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center text-green-600 font-semibold">
                                                <span v-if="transaction.in > 0">{{ transaction.in }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-center text-red-600 font-semibold">
                                                <span v-if="transaction.out > 0">{{ transaction.out }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-center font-semibold">{{ transaction.balance }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-600">RM{{ transaction.cost_price
                                            }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-600">RM{{
                                                transaction.selling_price
                                            }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-blue-600 font-semibold">
                                                <span v-if="transaction.amount > 0">RM{{ transaction.amount.toFixed(2)
                                                }}</span>
                                            </td>
                                        </tr>

                                        <!-- Totals -->
                                        <tr v-if="getTransactionLog(item).length > 0" class="bg-gray-50 font-semibold">
                                            <td class="px-3 py-2" colspan="2">TOTAL :</td>
                                            <td class="px-3 py-2 text-center text-green-600">
                                                {{ getTransactionTotals(getTransactionLog(item)).totalIn }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-red-600">
                                                {{ getTransactionTotals(getTransactionLog(item)).totalOut }}
                                            </td>
                                            <td class="px-3 py-2 text-center">{{ item.current_stock }}</td>
                                            <td class="px-3 py-2 text-center">{{ item.unit || '' }}</td>
                                            <td class="px-3 py-2 text-center"></td>
                                            <td class="px-3 py-2 text-center text-blue-600">
                                                RM{{
                                                    getTransactionTotals(getTransactionLog(item)).totalAmount.toFixed(2) }}
                                            </td>
                                        </tr>

                                        <!-- No transactions message -->
                                        <tr v-if="getTransactionLog(item).length === 0">
                                            <td colspan="8" class="px-3 py-4 text-center text-gray-500">
                                                No completed request history available
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </AppLayout>
    </template>