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
        type: Array,
        default: () => []
    },
    cartItems: {
        type: Array,
        default: () => []
    },
    requests: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

// FIXED: Use window.route if route is not available
const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Request Items' }];

const search = ref(props.filters?.search || "");
const stationaryItems = ref(props.stationaryItems || []); // FIXED: Direct array, no .data
const cartItems = ref(props.cartItems || []);
const requests = ref(props.requests);
const showAddToCartDialog = ref(false);
const showCartDialog = ref(false);
const showRequestDialog = ref(false);
const selectedItem = ref(null);

// Debug log to check data
onMounted(() => {
    console.log('Stationary Items:', stationaryItems.value);
    console.log('Cart Items:', cartItems.value);
    console.log('Requests:', requests.value);
});

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

// Statistics - FIXED with proper null checks
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

// Watchers - FIXED: Remove .data from stationaryItems
watch(() => props.requests, (newRequests) => {
    requests.value = newRequests;
}, { immediate: true });

watch(() => props.cartItems, (newCartItems) => {
    cartItems.value = newCartItems || [];
}, { immediate: true });

watch(() => props.stationaryItems, (newItems) => {
    stationaryItems.value = newItems || []; // FIXED: Direct assignment
}, { immediate: true });

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        router.get(route("user.request-items.index"), {
            search: newSearch
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }
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
        page: page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
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
            // Refresh the page to get updated cart items
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
                    // Refresh the page to get updated cart items
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
            // Refresh the page to get updated cart items
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
    // FIXED: Proper null checking for cartItems
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
            // Refresh the page to get updated data
            router.reload();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.purpose?.[0] || 'Failed to submit request',
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
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
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

// Function to scroll to available items section
const scrollToAvailableItems = () => {
    const element = document.getElementById('available-items-section');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
    showCartDialog.value = false;
};
</script>

<template>
    <Head title="Request Items" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

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
                    <h1 class="text-3xl font-bold text-gray-800">Request Stationary Items</h1>
                    <p class="mt-1 text-gray-500">Request office supplies and track your requests</p>
                </div>
                <div class="flex gap-3">
                    <Button label="View Cart" icon="pi pi-shopping-cart" severity="info" 
                        @click="showCartDialog = true"
                        :badge="statistics.cartItems.toString()" badgeClass="p-badge-danger" />
                    <Button label="Submit Request" icon="pi pi-send" severity="success"
                        @click="openRequestDialog" 
                        :disabled="statistics.cartItems === 0" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.totalRequests }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-inbox"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.pending }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.approved }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Cart Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.cartItems }}</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-xl text-red-600 pi pi-shopping-cart"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Available Items Section -->
            <Card id="available-items-section" class="shadow-lg">
                <template #content>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Available Stationary Items</h2>
                        <div class="w-full lg:w-auto">
                            <span class="p-input-icon-left">
                                <i class="pi pi-search" />
                                <InputText v-model="search" placeholder="Search items..." 
                                    class="w-full lg:w-80" />
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <div v-for="item in stationaryItems" :key="item.id" 
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-gray-900">{{ item.name }}</h3>
                                <Badge :value="getStockStatus(item).text"
                                    :severity="getStockStatus(item).severity"
                                    class="text-xs" />
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2">{{ item.description }}</p>
                            
                            <div class="space-y-1 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span>Category:</span>
                                    <span class="font-medium">{{ item.category }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Stock:</span>
                                    <span class="font-medium">{{ item.current_stock }} {{ item.unit }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Price:</span>
                                    <span class="font-medium">{{ formatCurrency(item.cost_price) }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-2">
                                <Button label="Add to Cart" icon="pi pi-cart-plus" severity="success" size="small"
                                    @click="openAddToCartDialog(item)"
                                    :disabled="item.current_stock === 0"
                                    class="flex-1" />
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="stationaryItems.length === 0" class="col-span-full">
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                    <i class="text-6xl text-gray-400 pi pi-box"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-700">No Items Available</h3>
                                <p class="text-gray-500">No stationary items are currently available for request.</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- My Requests Section -->
            <Card class="shadow-lg">
                <template #content>
                    <h2 class="text-xl font-bold text-gray-800 mb-6">My Requests</h2>

                    <DataTable :value="requests.data" showGridlines stripedRows
                        :rowHover="true" paginator :rows="requests.per_page" :totalRecords="requests.total"
                        :first="(requests.current_page - 1) * requests.per_page" @page="onPageChange"
                        responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                    <i class="text-6xl text-gray-400 pi pi-inbox"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-700">No Requests Found</h3>
                                <p class="mb-4 text-gray-500">You haven't made any requests yet.</p>
                                <Button label="Browse Items" icon="pi pi-shopping-cart" severity="success"
                                    @click="scrollToAvailableItems" />
                            </div>
                        </template>

                        <!-- Columns -->
                        <Column header="#" style="width: 60px;">
                            <template #body="slotProps">
                                <Badge :value="(requests.current_page - 1) * requests.per_page + slotProps.index + 1"
                                    severity="secondary" />
                            </template>
                        </Column>

                        <Column field="purpose" header="Purpose" sortable>
                            <template #body="slotProps">
                                <div class="font-semibold text-gray-900">{{ slotProps.data.purpose }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ slotProps.data.items?.length || 0 }} item(s)
                                </div>
                            </template>
                        </Column>

                        <Column header="Priority" sortable style="width: 120px;">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.priority"
                                    :severity="getPrioritySeverity(slotProps.data.priority)"
                                    class="capitalize" />
                            </template>
                        </Column>

                        <Column header="Status" sortable style="width: 120px;">
                            <template #body="slotProps">
                                <Badge :value="getStatusText(slotProps.data.status)"
                                    :severity="getStatusSeverity(slotProps.data.status)"
                                    class="capitalize" />
                            </template>
                        </Column>

                        <Column header="Needed By" sortable style="width: 120px;">
                            <template #body="slotProps">
                                <div class="text-sm text-gray-600">
                                    {{ formatDate(slotProps.data.needed_by) }}
                                </div>
                            </template>
                        </Column>

                        <Column header="Requested" sortable style="width: 120px;">
                            <template #body="slotProps">
                                <div class="text-sm text-gray-600">
                                    {{ formatDate(slotProps.data.created_at) }}
                                </div>
                            </template>
                        </Column>

                        <!-- Actions -->
                        <Column header="Actions" style="min-width: 120px">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                        v-tooltip.top="'View Details'" 
                                        @click="router.get(route('user.request-items.show', slotProps.data.id))" />

                                    <Button v-if="slotProps.data.status === 'pending'" 
                                        icon="pi pi-times" outlined rounded severity="danger" size="small"
                                        v-tooltip.top="'Cancel Request'" 
                                        @click="cancelRequest(slotProps.data.id)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Add to Cart Dialog -->
            <Dialog v-model:visible="showAddToCartDialog" modal header="Add to Cart" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6" v-if="selectedItem">
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ selectedItem.name }}</h3>
                            <p class="text-sm text-gray-600">{{ selectedItem.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Current Stock:</span>
                                <p class="font-medium">{{ selectedItem.current_stock }} {{ selectedItem.unit }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Price:</span>
                                <p class="font-medium">{{ formatCurrency(selectedItem.cost_price) }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Quantity</label>
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
                            <label class="block text-sm font-semibold text-gray-700">Notes (Optional)</label>
                            <Textarea v-model="cartForm.notes" rows="3" placeholder="Add any special notes..."
                                class="w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showAddToCartDialog = false"
                            :disabled="cartForm.processing" />
                        <Button label="Add to Cart" icon="pi pi-cart-plus" severity="success" 
                            @click="addToCart" :loading="cartForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- Cart Dialog -->
            <Dialog v-model:visible="showCartDialog" modal header="My Cart" :style="{ width: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-6">
                    <div v-if="Array.isArray(cartItems) && cartItems.length > 0">
                        <div class="space-y-4">
                            <div v-for="cartItem in cartItems" :key="cartItem.id"
                                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">{{ cartItem.stationary_item?.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ cartItem.stationary_item?.description }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ formatCurrency(cartItem.stationary_item?.cost_price || 0) }} per {{ cartItem.stationary_item?.unit }}
                                    </p>
                                    <p v-if="cartItem.notes" class="text-sm text-gray-500 mt-1">
                                        Notes: {{ cartItem.notes }}
                                    </p>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <Button icon="pi pi-minus" outlined rounded severity="secondary" size="small"
                                            @click="updateCartQuantity(cartItem.id, cartItem.quantity - 1)" />
                                        <span class="font-semibold w-8 text-center">{{ cartItem.quantity }}</span>
                                        <Button icon="pi pi-plus" outlined rounded severity="secondary" size="small"
                                            @click="updateCartQuantity(cartItem.id, cartItem.quantity + 1)"
                                            :disabled="cartItem.quantity >= (cartItem.stationary_item?.current_stock || 0)" />
                                    </div>
                                    
                                    <div class="text-right min-w-20">
                                        <p class="font-semibold text-gray-900">
                                            {{ formatCurrency(cartItem.quantity * (cartItem.stationary_item?.cost_price || 0)) }}
                                        </p>
                                    </div>
                                    
                                    <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                        @click="removeFromCart(cartItem.id)" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <div class="flex justify-between items-center mb-4">
                                <span class="font-semibold text-gray-900">Total Items:</span>
                                <span class="font-semibold text-gray-900">{{ statistics.cartItems }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-900">Estimated Total:</span>
                                <span class="font-semibold text-lg text-blue-600">{{ formatCurrency(statistics.cartTotal) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <div class="p-6 mb-4 bg-gray-100 rounded-full inline-block">
                            <i class="text-4xl text-gray-400 pi pi-shopping-cart"></i>
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-700">Your Cart is Empty</h3>
                        <p class="text-gray-500 mb-4">Add some items to your cart to make a request.</p>
                        <Button label="Browse Items" severity="primary" 
                            @click="scrollToAvailableItems" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t" v-if="Array.isArray(cartItems) && cartItems.length > 0">
                        <Button label="Continue Shopping" severity="secondary" outlined 
                            @click="scrollToAvailableItems" />
                        <Button label="Submit Request" icon="pi pi-send" severity="success" 
                            @click="showCartDialog = false; openRequestDialog()" />
                    </div>
                </div>
            </Dialog>

            <!-- Submit Request Dialog -->
            <Dialog v-model:visible="showRequestDialog" modal header="Submit Request" :style="{ width: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
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

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700">Priority</label>
                                <Select v-model="requestForm.priority" 
                                    :options="priorities" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    class="w-full" />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700">Needed By</label>
                                <InputText v-model="requestForm.needed_by" 
                                    type="date"
                                    class="w-full" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Additional Notes</label>
                            <Textarea v-model="requestForm.notes" rows="3" 
                                placeholder="Add any additional information..."
                                class="w-full" />
                        </div>

                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 mb-3">Items in Request</h4>
                            <div class="space-y-2">
                                <div v-for="cartItem in (Array.isArray(cartItems) ? cartItems : [])" :key="cartItem.id"
                                    class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ cartItem.stationary_item?.name }}</p>
                                        <p class="text-sm text-gray-600">
                                            {{ cartItem.quantity }} {{ cartItem.stationary_item?.unit }}
                                        </p>
                                    </div>
                                    <p class="font-medium text-gray-900">
                                        {{ formatCurrency(cartItem.quantity * (cartItem.stationary_item?.cost_price || 0)) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-3 pt-3 border-t">
                                <span class="font-semibold text-gray-900">Total:</span>
                                <span class="font-semibold text-lg text-blue-600">
                                    {{ formatCurrency(statistics.cartTotal) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showRequestDialog = false"
                            :disabled="requestForm.processing" />
                        <Button label="Submit Request" icon="pi pi-send" severity="success" 
                            @click="submitRequest" :loading="requestForm.processing" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>