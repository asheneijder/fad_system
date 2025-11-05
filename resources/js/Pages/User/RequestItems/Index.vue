<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Card from "primevue/card";
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    stationaryItems: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 12, current_page: 1 })
    },
    cartItems: {
        type: Array,
        default: () => []
    },
    requests: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    categories: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ 
            search: '', 
            category: '',
            page: 1,
            stationary_page: 1 
        })
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Request Items' }];

const search = ref(props.filters?.search || "");
const category = ref(props.filters?.category || "");
const stationaryItems = ref(props.stationaryItems);
const cartItems = ref(props.cartItems || []);
const requests = ref(props.requests);
const categories = ref(props.categories);
const showAddToCartDialog = ref(false);
const showCartDialog = ref(false);
const showRequestDialog = ref(false);
const selectedItem = ref(null);
const loadingMore = ref(false);

// Forms
const cartForm = useForm({
    stationary_item_id: null,
    quantity: 1,
    notes: '',
});

const requestForm = useForm({
    purpose: '',
    priority: 'medium',
    needed_by: null,
    notes: '',
    cart_item_ids: [],
});

// Priority options
const priorities = ref([
    { label: 'Low', value: 'low', severity: 'success' },
    { label: 'Medium', value: 'medium', severity: 'warning' },
    { label: 'High', value: 'high', severity: 'danger' },
    { label: 'Urgent', value: 'urgent', severity: 'danger' }
]);

// Statistics
const statistics = computed(() => {
    const requestData = requests.value?.data || [];
    const cartData = Array.isArray(cartItems.value) ? cartItems.value : [];
    
    return {
        totalRequests: requests.value?.total || 0,
        pending: requestData.filter(req => req.status === 'pending').length,
        approved: requestData.filter(req => req.status === 'approved').length,
        rejected: requestData.filter(req => req.status === 'rejected').length,
        completed: requestData.filter(req => req.status === 'completed').length,
        cartItems: cartData.length || 0,
        cartTotal: cartData.reduce((sum, item) => {
            const costPrice = item.stationary_item?.cost_price || 0;
            return sum + (item.quantity * costPrice);
        }, 0) || 0,
    };
});

// Watchers
watch(() => props.requests, (newRequests) => {
    requests.value = newRequests;
}, { immediate: true });

watch(() => props.cartItems, (newCartItems) => {
    cartItems.value = newCartItems || [];
}, { immediate: true });

watch(() => props.stationaryItems, (newItems) => {
    stationaryItems.value = newItems;
}, { immediate: true });

watch(() => props.categories, (newCategories) => {
    categories.value = newCategories;
}, { immediate: true });

// Search and filter watchers with debounce
let searchTimeout;
watch(search, (newSearch) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadStationaryItems(1);
    }, 500);
});

watch(category, (newCategory) => {
    loadStationaryItems(1);
});

watch(showAddToCartDialog, (val) => {
    if (!val) {
        cartForm.reset();
        selectedItem.value = null;
    }
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("user.request-items.index"), {
        search: search.value,
        category: category.value,
        page: page,
        stationary_page: stationaryItems.value.current_page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const loadStationaryItems = (page = null) => {
    const targetPage = page || stationaryItems.value.current_page;
    
    router.get(route("user.request-items.index"), {
        search: search.value,
        category: category.value,
        page: requests.value.current_page,
        stationary_page: targetPage,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const loadMoreItems = () => {
    if (loadingMore.value) return;
    
    const nextPage = stationaryItems.value.current_page + 1;
    if (nextPage > stationaryItems.value.last_page) return;
    
    loadingMore.value = true;
    
    router.get(route("user.request-items.index"), {
        search: search.value,
        category: category.value,
        page: requests.value.current_page,
        stationary_page: nextPage,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onSuccess: () => {
            loadingMore.value = false;
        },
        onError: () => {
            loadingMore.value = false;
        }
    });
};

const openAddToCartDialog = (item) => {
    selectedItem.value = item;
    cartForm.stationary_item_id = item.id;
    cartForm.quantity = 1;
    cartForm.notes = '';
    showAddToCartDialog.value = true;
};

const addToCart = () => {
    if (!cartForm.stationary_item_id) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Please select an item',
            life: 3000
        });
        return;
    }

    cartForm.post(route('user.cart-list.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Item added to cart',
                life: 3000
            });
            showAddToCartDialog.value = false;
            router.reload();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.quantity?.[0] || 'Failed to add item to cart',
                life: 3000
            });
        }
    });
};

const removeFromCart = (cartItemId) => {
    confirm.require({
        message: "Are you sure you want to remove this item from your cart?",
        header: "Remove Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("user.cart-list.destroy", cartItemId), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Removed",
                        detail: "Item removed from cart",
                        life: 3000,
                    });
                    router.reload();
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to remove item",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const updateCartQuantity = (cartItemId, newQuantity) => {
    if (newQuantity < 1) {
        removeFromCart(cartItemId);
        return;
    }

    const form = useForm({
        quantity: newQuantity
    });

    form.put(route('user.cart-list.update', cartItemId), {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to update quantity",
                life: 3000,
            });
        }
    });
};

const openRequestDialog = () => {
    const hasCartItems = Array.isArray(cartItems.value) && cartItems.value.length > 0;
    
    if (!hasCartItems) {
        toast.add({
            severity: 'warn',
            summary: 'Empty Cart',
            detail: 'Please add items to your cart first',
            life: 3000
        });
        return;
    }

    requestForm.purpose = '';
    requestForm.priority = 'medium';
    requestForm.needed_by = null;
    requestForm.notes = '';
    requestForm.cart_item_ids = cartItems.value.map(item => item.id);
    showRequestDialog.value = true;
};

const submitRequest = () => {
    if (requestForm.cart_item_ids.length === 0) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No items in cart',
            life: 3000
        });
        return;
    }

    requestForm.post(route('user.request-items.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Request submitted successfully',
                life: 3000
            });
            showRequestDialog.value = false;
            router.reload();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to submit request',
                life: 3000
            });
        }
    });
};

const cancelRequest = (requestId) => {
    confirm.require({
        message: "Are you sure you want to cancel this request?",
        header: "Cancel Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("user.request-items.destroy", requestId), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Cancelled",
                        detail: "Request cancelled successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to cancel request",
                        life: 3000,
                    });
                }
            });
        },
    });
};

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

const getPrioritySeverity = (priority) => {
    const priorityMap = {
        low: 'success',
        medium: 'warning',
        high: 'danger',
        urgent: 'danger'
    };
    return priorityMap[priority] || 'secondary';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStockStatus = (item) => {
    if (!item || item.current_stock === 0) return { text: 'Out of Stock', severity: 'danger' };
    if (item.current_stock <= item.min_stock) return { text: 'Low Stock', severity: 'warning' };
    return { text: 'In Stock', severity: 'success' };
};

const scrollToAvailableItems = () => {
    const element = document.getElementById('available-items-section');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
    showCartDialog.value = false;
};

const clearFilters = () => {
    search.value = '';
    category.value = '';
};
</script>

<template>
    <Head title="Request Items" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Request Stationary Items</h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Request office supplies and track your requests</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:gap-3 w-full sm:w-auto">
                    <Button label="View Cart" icon="pi pi-shopping-cart" severity="info" 
                        @click="showCartDialog = true"
                        :badge="statistics.cartItems.toString()" badgeClass="p-badge-danger"
                        class="flex-1 sm:flex-initial responsive-button" />
                    <Button label="Submit Request" icon="pi pi-send" severity="success"
                        @click="openRequestDialog" 
                        :disabled="statistics.cartItems === 0"
                        class="flex-1 sm:flex-initial responsive-button" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">{{ statistics.totalRequests }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full">
                                <i class="text-lg sm:text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Pending</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">{{ statistics.pending }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full">
                                <i class="text-lg sm:text-xl text-yellow-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Approved</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">{{ statistics.approved }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full">
                                <i class="text-lg sm:text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Cart Items</p>
                                <p class="mt-1 text-xl sm:text-2xl font-bold text-gray-900">{{ statistics.cartItems }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full">
                                <i class="text-lg sm:text-xl text-red-600 pi pi-shopping-cart"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Available Items Section -->
            <Card id="available-items-section" class="shadow-lg">
                <template #content>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 mb-4 sm:mb-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800">Available Stationary Items</h2>
                        <div class="text-sm text-gray-500">
                            Showing {{ stationaryItems.data?.length || 0 }} of {{ stationaryItems.total || 0 }} items
                        </div>
                    </div>

                    <!-- Search and Filter Bar -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 sm:mb-6">
                        <div class="sm:col-span-1">
                            <span class="p-input-icon-left block w-full">
                                <i class="pi pi-search" />
                                <InputText v-model="search" placeholder="Search items..." 
                                    class="w-full pl-10" />
                            </span>
                        </div>
                        <div class="sm:col-span-1">
                            <Select v-model="category" 
                                :options="categories" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Filter by category"
                                class="w-full" />
                        </div>
                        <div class="sm:col-span-1">
                            <Button label="Clear Filters" icon="pi pi-filter-slash" severity="secondary" outlined
                                @click="clearFilters" 
                                :disabled="!search && !category"
                                class="w-full responsive-button" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                        <div v-for="item in stationaryItems.data" :key="item.id" 
                            class="border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow flex flex-col">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-semibold text-sm sm:text-base text-gray-900 flex-1 min-w-0 pr-2">{{ item.name }}</h3>
                                <Badge :value="getStockStatus(item).text"
                                    :severity="getStockStatus(item).severity"
                                    class="responsive-badge flex-shrink-0" />
                            </div>
                            
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 line-clamp-2 flex-grow-0">{{ item.description }}</p>
                            
                            <div class="space-y-1 text-xs sm:text-sm text-gray-600 flex-grow">
                                <div class="flex justify-between">
                                    <span>Category:</span>
                                    <span class="font-medium capitalize">{{ item.category }}</span>
                                </div>
                                <!-- <div class="flex justify-between">
                                    <span>Stock:</span>
                                    <span class="font-medium">{{ item.current_stock }} {{ item.unit }}</span>
                                </div> -->
                                <!-- <div class="flex justify-between">
                                    <span>Price:</span>
                                    <span class="font-medium">{{ formatCurrency(item.cost_price) }}</span>
                                </div> -->
                            </div>

                            <div class="mt-3 sm:mt-4">
                                <Button label="Add to Cart" icon="pi pi-cart-plus" severity="success"
                                    @click="openAddToCartDialog(item)"
                                    :disabled="item.current_stock === 0"
                                    class="w-full responsive-button" />
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="stationaryItems.data?.length === 0" class="col-span-full">
                            <div class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
                                <div class="p-4 sm:p-6 mb-3 sm:mb-4 bg-gray-100 rounded-full">
                                    <i class="text-4xl sm:text-6xl text-gray-400 pi pi-box"></i>
                                </div>
                                <h3 class="mb-2 text-lg sm:text-xl font-semibold text-gray-700">No Items Found</h3>
                                <p class="text-sm sm:text-base text-gray-500 text-center">
                                    {{ search || category ? 'Try adjusting your search or filters' : 'No stationary items are currently available for request.' }}
                                </p>
                                <Button v-if="search || category" label="Clear Filters" severity="primary"
                                    @click="clearFilters" class="mt-3 responsive-button" />
                            </div>
                        </div>
                    </div>

                    <!-- Load More Button -->
                    <div v-if="stationaryItems.data?.length > 0 && stationaryItems.current_page < stationaryItems.last_page" 
                         class="flex justify-center mt-6">
                        <Button label="Load More Items" icon="pi pi-chevron-down" severity="secondary" outlined
                            @click="loadMoreItems" 
                            :loading="loadingMore"
                            class="responsive-button" />
                    </div>

                    <!-- Showing information -->
                    <div v-if="stationaryItems.data?.length > 0" class="text-center text-sm text-gray-500 mt-4">
                        Showing page {{ stationaryItems.current_page }} of {{ stationaryItems.last_page }} • 
                        {{ stationaryItems.total }} total items
                    </div>
                </template>
            </Card>

            <!-- My Requests Section -->
            <Card class="shadow-lg">
                <template #content>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4 sm:mb-6">My Requests</h2>

                    <div class="overflow-x-auto">
                        <DataTable :value="requests.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="requests.per_page" :totalRecords="requests.total"
                            :first="(requests.current_page - 1) * requests.per_page" @page="onPageChange"
                            responsiveLayout="scroll" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
                                    <div class="p-4 sm:p-6 mb-3 sm:mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-inbox"></i>
                                    </div>
                                    <h3 class="mb-2 text-lg sm:text-xl font-semibold text-gray-700">No Requests Found</h3>
                                    <p class="mb-3 sm:mb-4 text-sm sm:text-base text-gray-500 text-center">You haven't made any requests yet.</p>
                                    <Button label="Browse Items" icon="pi pi-shopping-cart" severity="success"
                                        @click="scrollToAvailableItems" class="responsive-button" />
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(requests.current_page - 1) * requests.per_page + slotProps.index + 1"
                                        severity="secondary" class="responsive-badge" />
                                </template>
                            </Column>

                            <Column field="purpose" header="Purpose" sortable style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-sm sm:text-base text-gray-900">{{ slotProps.data.purpose }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500">
                                        {{ slotProps.data.items?.length || 0 }} item(s)
                                    </div>
                                </template>
                            </Column>

                            <Column header="Priority" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.priority"
                                        :severity="getPrioritySeverity(slotProps.data.priority)"
                                        class="capitalize responsive-badge" />
                                </template>
                            </Column>

                            <Column header="Status" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data.status)"
                                        :severity="getStatusSeverity(slotProps.data.status)"
                                        class="capitalize responsive-badge" />
                                </template>
                            </Column>

                            <Column header="Needed By" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.needed_by) }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Requested" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 120px">
                                <template #body="slotProps">
                                    <div class="flex flex-wrap gap-2">
                                        <Button icon="pi pi-eye" outlined rounded severity="info"
                                            v-tooltip.top="'View Details'" 
                                            @click="router.get(route('user.request-items.show', slotProps.data.id))"
                                            class="responsive-icon-button" />

                                        <Button v-if="slotProps.data.status === 'pending'" 
                                            icon="pi pi-times" outlined rounded severity="danger"
                                            v-tooltip.top="'Cancel Request'" 
                                            @click="cancelRequest(slotProps.data.id)"
                                            class="responsive-icon-button" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Add to Cart Dialog -->
            <Dialog v-model:visible="showAddToCartDialog" modal header="Add to Cart" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-4 sm:space-y-6" v-if="selectedItem">
                    <div class="space-y-3 sm:space-y-4">
                        <div>
                            <h3 class="font-semibold text-sm sm:text-base text-gray-900">{{ selectedItem.name }}</h3>
                            <p class="text-xs sm:text-sm text-gray-600">{{ selectedItem.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
                            <!-- <div>
                                <span class="text-gray-500">Current Stock:</span>
                                <p class="font-medium">{{ selectedItem.current_stock }} {{ selectedItem.unit }}</p>
                            </div> -->
                            <!-- <div>
                                <span class="text-gray-500">Price:</span>
                                <p class="font-medium">{{ formatCurrency(selectedItem.cost_price) }}</p>
                            </div> -->
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Quantity</label>
                            <InputNumber v-model="cartForm.quantity" 
                                :min="1" 
                                :max="selectedItem.current_stock"
                                class="w-full"
                                :class="{ 'p-invalid': cartForm.errors.quantity }" />
                            <small class="text-red-500 text-xs" v-if="cartForm.errors.quantity">
                                {{ cartForm.errors.quantity }}
                            </small>
                            <small class="text-gray-500 text-xs">
                                Maximum available: {{ selectedItem.current_stock }} {{ selectedItem.unit }}
                            </small>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes (Optional)</label>
                            <Textarea v-model="cartForm.notes" rows="3" placeholder="Add any special notes..."
                                class="w-full" />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showAddToCartDialog = false"
                            :disabled="cartForm.processing"
                            class="w-full sm:w-auto responsive-button" />
                        <Button label="Add to Cart" icon="pi pi-cart-plus" severity="success" 
                            @click="addToCart" :loading="cartForm.processing"
                            class="w-full sm:w-auto responsive-button" />
                    </div>
                </div>
            </Dialog>

            <!-- Cart Dialog -->
            <Dialog v-model:visible="showCartDialog" modal header="My Cart" 
                :style="{ width: '95vw', maxWidth: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-4 sm:space-y-6">
                    <div v-if="Array.isArray(cartItems) && cartItems.length > 0">
                        <div class="space-y-3 sm:space-y-4">
                            <div v-for="cartItem in cartItems" :key="cartItem.id"
                                class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 p-3 sm:p-4 border border-gray-200 rounded-lg">
                                <div class="flex-1 w-full">
                                    <h4 class="font-semibold text-sm sm:text-base text-gray-900">{{ cartItem.stationary_item?.name }}</h4>
                                    <p class="text-xs sm:text-sm text-gray-600">{{ cartItem.stationary_item?.description }}</p>
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        <!-- {{ formatCurrency(cartItem.stationary_item?.cost_price || 0) }} per {{ cartItem.stationary_item?.unit }} -->
                                    </p>
                                    <p v-if="cartItem.notes" class="text-xs sm:text-sm text-gray-500 mt-1">
                                        Notes: {{ cartItem.notes }}
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 w-full sm:w-auto">
                                    <div class="flex items-center gap-2">
                                        <Button icon="pi pi-minus" outlined rounded severity="secondary"
                                            @click="updateCartQuantity(cartItem.id, cartItem.quantity - 1)"
                                            class="responsive-icon-button" />
                                        <span class="font-semibold w-8 text-center text-sm sm:text-base">{{ cartItem.quantity }}</span>
                                        <Button icon="pi pi-plus" outlined rounded severity="secondary"
                                            @click="updateCartQuantity(cartItem.id, cartItem.quantity + 1)"
                                            :disabled="cartItem.quantity >= (cartItem.stationary_item?.current_stock || 0)"
                                            class="responsive-icon-button" />
                                    </div>
                                    
                                    <div class="text-right min-w-20">
                                        <p class="font-semibold text-sm sm:text-base text-gray-900">
                                            <!-- {{ formatCurrency(cartItem.quantity * (cartItem.stationary_item?.cost_price || 0)) }} -->
                                        </p>
                                    </div>
                                    
                                    <Button icon="pi pi-trash" outlined rounded severity="danger"
                                        @click="removeFromCart(cartItem.id)"
                                        class="responsive-icon-button" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-3 sm:pt-4 mt-3 sm:mt-4">
                            <div class="flex justify-between items-center mb-3 sm:mb-4">
                                <span class="font-semibold text-sm sm:text-base text-gray-900">Total Items:</span>
                                <span class="font-semibold text-sm sm:text-base text-gray-900">{{ statistics.cartItems }}</span>
                            </div>
                            <!-- <div class="flex justify-between items-center">
                                <span class="font-semibold text-sm sm:text-base text-gray-900">Estimated Total:</span>
                                <span class="font-semibold text-base sm:text-lg text-blue-600">{{ formatCurrency(statistics.cartTotal) }}</span>
                            </div> -->
                        </div>
                    </div>

                    <div v-else class="text-center py-6 sm:py-8">
                        <div class="p-4 sm:p-6 mb-3 sm:mb-4 bg-gray-100 rounded-full inline-block">
                            <i class="text-3xl sm:text-4xl text-gray-400 pi pi-shopping-cart"></i>
                        </div>
                        <h3 class="mb-2 text-lg sm:text-xl font-semibold text-gray-700">Your Cart is Empty</h3>
                        <p class="text-sm sm:text-base text-gray-500 mb-3 sm:mb-4">Add some items to your cart to make a request.</p>
                        <Button label="Browse Items" severity="primary" 
                            @click="scrollToAvailableItems" class="responsive-button" />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-3 sm:pt-4 border-t" v-if="Array.isArray(cartItems) && cartItems.length > 0">
                        <Button label="Continue Choosing Items" severity="secondary" outlined 
                            @click="scrollToAvailableItems"
                            class="w-full sm:w-auto responsive-button" />
                        <Button label="Submit Request" icon="pi pi-send" severity="success" 
                            @click="showCartDialog = false; openRequestDialog()"
                            class="w-full sm:w-auto responsive-button" />
                    </div>
                </div>
            </Dialog>

            <!-- Submit Request Dialog -->
            <Dialog v-model:visible="showRequestDialog" modal header="Submit Request" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-4 sm:space-y-6">
                    <div class="space-y-3 sm:space-y-4">
                        <div class="space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Purpose <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="requestForm.purpose" 
                                placeholder="Enter the purpose of this request" 
                                class="w-full"
                                :class="{ 'p-invalid': requestForm.errors.purpose }" />
                            <small class="text-red-500 text-xs" v-if="requestForm.errors.purpose">
                                {{ requestForm.errors.purpose }}
                            </small>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-2">
                                <label class="block text-xs sm:text-sm font-semibold text-gray-700">Priority</label>
                                <Select v-model="requestForm.priority" 
                                    :options="priorities" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    class="w-full" />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs sm:text-sm font-semibold text-gray-700">Needed By</label>
                                <InputText v-model="requestForm.needed_by" 
                                    type="date"
                                    class="w-full" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Additional Notes</label>
                            <Textarea v-model="requestForm.notes" rows="3" 
                                placeholder="Add any additional information..."
                                class="w-full" />
                        </div>

                        <div class="border rounded-lg p-3 sm:p-4">
                            <h4 class="font-semibold text-sm sm:text-base text-gray-900 mb-2 sm:mb-3">Items in Request</h4>
                            <div class="space-y-2">
                                <div v-for="cartItem in (Array.isArray(cartItems) ? cartItems : [])" :key="cartItem.id"
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 py-2 border-b border-gray-100 last:border-b-0">
                                    <div class="w-full sm:w-auto">
                                        <p class="font-medium text-sm sm:text-base text-gray-900">{{ cartItem.stationary_item?.name }}</p>
                                        <p class="text-xs sm:text-sm text-gray-600">
                                            {{ cartItem.quantity }} {{ cartItem.stationary_item?.unit }}
                                        </p>
                                    </div>
                                    <!-- <p class="font-medium text-sm sm:text-base text-gray-900 sm:text-right">
                                        {{ formatCurrency(cartItem.quantity * (cartItem.stationary_item?.cost_price || 0)) }}
                                    </p> -->
                                </div>
                            </div>
                            <!-- <div class="flex justify-between items-center mt-2 sm:mt-3 pt-2 sm:pt-3 border-t">
                                <span class="font-semibold text-sm sm:text-base text-gray-900">Total:</span>
                                <span class="font-semibold text-base sm:text-lg text-blue-600">
                                    {{ formatCurrency(statistics.cartTotal) }}
                                </span>
                            </div> -->
                        </div>
                    </div> 

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showRequestDialog = false"
                            :disabled="requestForm.processing"
                            class="w-full sm:w-auto responsive-button" />
                        <Button label="Submit Request" icon="pi pi-send" severity="success" 
                            @click="submitRequest" :loading="requestForm.processing"
                            class="w-full sm:w-auto responsive-button" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 1rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-datatable .p-paginator) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-paginator) {
        padding: 1rem;
    }
}

/* Fix search icon alignment */
:deep(.p-input-icon-left > i:first-of-type) {
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    position: absolute;
}

:deep(.p-input-icon-left > .p-inputtext) {
    padding-left: 2.5rem;
}

:deep(.p-input-icon-left) {
    position: relative;
    display: block;
}

/* Responsive Button Styles */
.responsive-button :deep(.p-button-label) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 0.875rem;
    }
}

@media (min-width: 1024px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 1rem;
    }
}

.responsive-button :deep(.p-button-icon) {
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    .responsive-button :deep(.p-button-icon) {
        font-size: 1rem;
    }
}

.responsive-button :deep(.p-button) {
    padding: 0.5rem 0.75rem;
}

@media (min-width: 640px) {
    .responsive-button :deep(.p-button) {
        padding: 0.625rem 1rem;
    }
}

@media (min-width: 1024px) {
    .responsive-button :deep(.p-button) {
        padding: 0.75rem 1.25rem;
    }
}

/* Responsive Icon Button Styles */
.responsive-icon-button :deep(.p-button) {
    width: 2rem;
    height: 2rem;
    padding: 0;
}

@media (min-width: 640px) {
    .responsive-icon-button :deep(.p-button) {
        width: 2.25rem;
        height: 2.25rem;
    }
}

@media (min-width: 1024px) {
    .responsive-icon-button :deep(.p-button) {
        width: 2.5rem;
        height: 2.5rem;
    }
}

.responsive-icon-button :deep(.p-button-icon) {
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    .responsive-icon-button :deep(.p-button-icon) {
        font-size: 1rem;
    }
}

/* Responsive Badge Styles */
.responsive-badge :deep(.p-badge) {
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
}

@media (min-width: 640px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
}

@media (min-width: 1024px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.875rem;
        padding: 0.35rem 0.7rem;
    }
}

.responsive-badge {
    flex-shrink: 0;
}

:deep(.p-button .p-badge) {
    font-size: 0.625rem;
    min-width: 1rem;
    height: 1rem;
    line-height: 1rem;
    white-space: nowrap;
}

@media (min-width: 640px) {
    :deep(.p-button .p-badge) {
        font-size: 0.75rem;
        min-width: 1.25rem;
        height: 1.25rem;
        line-height: 1.25rem;
    }
}

:deep(.p-dialog .p-dialog-header) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-header) {
        padding: 1.5rem;
    }
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1.5rem;
    }
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Mobile table improvements */
@media (max-width: 768px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 0.75rem 0.5rem;
    }
    
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        padding: 0.75rem 0.5rem;
    }
}

/* Ensure buttons don't wrap text awkwardly on small screens */
@media (max-width: 639px) {
    .responsive-button :deep(.p-button) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>