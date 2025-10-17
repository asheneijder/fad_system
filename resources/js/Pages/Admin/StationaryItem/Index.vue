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
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Textarea from 'primevue/textarea';
import Menu from 'primevue/menu';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    stationaryItems: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Stationary Item Management' }];

const search = ref(props.filters?.search || "");
const stationaryItems = ref(props.stationaryItems);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const showStockDialog = ref(false);
const showMovementDialog = ref(false);
const isEditMode = ref(false);
const selectedItemId = ref(null);
const viewItemData = ref(null);
const selectedItems = ref([]);
const actionMenu = ref();

// Forms
const itemForm = useForm({
    name: '',
    description: '',
    category: 'writing',
    unit: 'pcs',
    sku: '',
    min_stock: 0,
    current_stock: 0,
    cost_price: 0,
    selling_price: 0,
    supplier: '',
    location: '',
    status: true,
});

const stockForm = useForm({
    quantity: 0,
    operation: 'add',
    type: 'in',
    notes: '',
    movement_date: new Date(),
});

const movementForm = useForm({
    type: 'in',
    quantity: 0,
    reference: '',
    notes: '',
    movement_date: new Date(),
});

// Action menu items
const actionItems = ref([
    {
        label: 'Bulk Actions',
        items: [
            {
                label: 'Activate Selected',
                icon: 'pi pi-check',
                command: () => bulkUpdateStatus(true)
            },
            {
                label: 'Deactivate Selected',
                icon: 'pi pi-times',
                command: () => bulkUpdateStatus(false)
            },
            {
                label: 'Export Selected',
                icon: 'pi pi-download',
                command: () => exportSelected()
            }
        ]
    }
]);

// Category options
const categories = ref([
    { label: 'Writing Instruments', value: 'writing' },
    { label: 'Paper Products', value: 'paper' },
    { label: 'Desk Accessories', value: 'desk' },
    { label: 'Filing Supplies', value: 'filing' },
    { label: 'Computer Supplies', value: 'computer' },
    { label: 'Mailing Supplies', value: 'mailing' },
    { label: 'Cleaning Supplies', value: 'cleaning' },
    { label: 'Other', value: 'other' }
]);

// Unit options
const units = ref([
    { label: 'Pieces', value: 'pcs' },
    { label: 'Boxes', value: 'boxes' },
    { label: 'Packs', value: 'packs' },
    { label: 'Reams', value: 'reams' },
    { label: 'Sets', value: 'sets' },
    { label: 'Bottles', value: 'bottles' },
    { label: 'Rolls', value: 'rolls' },
    { label: 'Units', value: 'units' }
]);

// Movement types
const movementTypes = ref([
    { label: 'Stock In', value: 'in' },
    { label: 'Stock Out', value: 'out' },
    { label: 'Adjustment', value: 'adjustment' },
    { label: 'Return', value: 'return' }
]);

// Statistics
const statistics = computed(() => {
    const data = stationaryItems.value?.data || [];
    
    return {
        total: stationaryItems.value?.total || 0,
        active: data.filter(item => item.status).length,
        lowStock: data.filter(item => item.current_stock <= item.min_stock && item.current_stock > 0).length,
        outOfStock: data.filter(item => item.current_stock === 0).length,
        inStock: data.filter(item => item.current_stock > 0).length,
        totalValue: data.reduce((sum, item) => sum + (item.current_stock * item.cost_price), 0),
        writing: data.filter(item => item.category === 'writing').length,
        paper: data.filter(item => item.category === 'paper').length,
        desk: data.filter(item => item.category === 'desk').length,
    };
});

// Watchers
watch(() => props.stationaryItems, (newItems) => {
    stationaryItems.value = newItems;
}, { immediate: true });

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        router.get(route("admin.stationary-items.index"), {
            search: newSearch
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }
});

watch(showCreateEditDialog, (val) => {
    if (!val) {
        resetForm();
    }
});

watch(showStockDialog, (val) => {
    if (!val) {
        stockForm.reset();
        selectedItemId.value = null;
    }
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("admin.stationary-items.index"), {
        search: search.value,
        page: page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const resetForm = () => {
    itemForm.reset();
    isEditMode.value = false;
    selectedItemId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    itemForm.status = true;
    itemForm.min_stock = 0;
    itemForm.current_stock = 0;
    itemForm.cost_price = 0;
    itemForm.selling_price = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (item) => {
    isEditMode.value = true;
    selectedItemId.value = item.id;
    
    itemForm.name = item.name;
    itemForm.description = item.description || '';
    itemForm.category = item.category || 'writing';
    itemForm.unit = item.unit || 'pcs';
    itemForm.sku = item.sku || '';
    itemForm.min_stock = item.min_stock || 0;
    itemForm.current_stock = item.current_stock || 0;
    itemForm.cost_price = item.cost_price || 0;
    itemForm.selling_price = item.selling_price || 0;
    itemForm.supplier = item.supplier || '';
    itemForm.location = item.location || '';
    itemForm.status = item.status;
    
    showCreateEditDialog.value = true;
};

const viewItem = (item) => {
    viewItemData.value = item;
    showViewDialog.value = true;
};

const openStockDialog = (item) => {
    selectedItemId.value = item.id;
    stockForm.quantity = 0;
    stockForm.operation = 'add';
    stockForm.type = 'in';
    stockForm.notes = '';
    stockForm.movement_date = new Date();
    showStockDialog.value = true;
};

const openMovementDialog = (item) => {
    selectedItemId.value = item.id;
    movementForm.type = 'in';
    movementForm.quantity = 0;
    movementForm.reference = '';
    movementForm.notes = '';
    movementForm.movement_date = new Date();
    showMovementDialog.value = true;
};

const generateSKU = () => {
    const prefix = 'STN';
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    itemForm.sku = `${prefix}-${random}`;
};

const saveItem = () => {
    if (isEditMode.value) {
        itemForm.put(route('admin.stationary-items.update', selectedItemId.value), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Stationary item updated successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) {
                    errorMessage = errors.name[0];
                } else if (errors.sku) {
                    errorMessage = 'SKU already exists';
                }
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: errorMessage,
                    life: 3000
                });
            }
        });
    } else {
        itemForm.post(route('admin.stationary-items.store'), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Stationary item created successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) {
                    errorMessage = errors.name[0];
                } else if (errors.sku) {
                    errorMessage = 'SKU already exists';
                }
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: errorMessage,
                    life: 3000
                });
            }
        });
    }
};

const updateStock = () => {
    if (!selectedItemId.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No item selected',
            life: 3000
        });
        return;
    }

    stockForm.post(route('admin.stationary-items.update-stock', selectedItemId.value), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Stock updated successfully',
                life: 3000
            });
            showStockDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.quantity?.[0] || 'Failed to update stock',
                life: 3000
            });
        }
    });
};

const recordMovement = () => {
    if (!selectedItemId.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No item selected',
            life: 3000
        });
        return;
    }

    movementForm.post(route('admin.stationary-items.record-movement', selectedItemId.value), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Movement recorded successfully',
                life: 3000
            });
            showMovementDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.quantity?.[0] || 'Failed to record movement',
                life: 3000
            });
        }
    });
};

const bulkUpdateStatus = (status) => {
    if (selectedItems.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select items first',
            life: 3000
        });
        return;
    }

    const form = useForm({
        item_ids: selectedItems.value.map(item => item.id),
        status: status
    });

    form.post(route('admin.stationary-items.bulk-update-status'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${selectedItems.value.length} item(s) ${status ? 'activated' : 'deactivated'}`,
                life: 3000
            });
            selectedItems.value = [];
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update items',
                life: 3000
            });
        }
    });
};

const exportSelected = () => {
    if (selectedItems.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select items to export',
            life: 3000
        });
        return;
    }

    const itemIds = selectedItems.value.map(item => item.id);
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.stationary-items.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    itemIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'item_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const deleteItem = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this stationary item? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.stationary-items.destroy", id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Stationary item deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete item",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getStatusSeverity = (item) => {
    return item.status ? 'success' : 'danger';
};

const getStatusText = (item) => {
    return item.status ? 'Active' : 'Inactive';
};

const getStockStatus = (item) => {
    if (item.current_stock === 0) return { text: 'Out of Stock', severity: 'danger' };
    if (item.current_stock <= item.min_stock) return { text: 'Low Stock', severity: 'warning' };
    return { text: 'In Stock', severity: 'success' };
};

const getCategoryBadge = (category) => {
    const categoryMap = {
        writing: { label: 'Writing', severity: 'primary' },
        paper: { label: 'Paper', severity: 'info' },
        desk: { label: 'Desk', severity: 'warning' },
        filing: { label: 'Filing', severity: 'help' },
        computer: { label: 'Computer', severity: 'success' },
        mailing: { label: 'Mailing', severity: 'danger' },
        cleaning: { label: 'Cleaning', severity: 'secondary' },
        other: { label: 'Other', severity: 'contrast' }
    };
    
    return categoryMap[category] || { label: category, severity: 'secondary' };
};

const formatCurrency = (amount) => {
    return 'RM ' + new Intl.NumberFormat('ms-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
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

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};
</script>

<template>
    <Head title="Stationary Item Management" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />
        <Menu ref="actionMenu" :model="actionItems" :popup="true" />

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
                    <h1 class="text-3xl font-bold text-gray-800">Stationary Item Management</h1>
                    <p class="mt-1 text-gray-500">Manage office stationary items and inventory</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" />
                    <Button label="Create Item" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-box"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">In Stock</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.inStock }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Low Stock</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.lowStock }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-exclamation-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Out of Stock</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.outOfStock }}</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-xl text-red-600 pi pi-times-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Value</p>
                                <p class="mt-1 text-xl font-bold text-gray-900">{{ formatCurrency(statistics.totalValue) }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-dollar"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-indigo-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Writing</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.writing }}</p>
                            </div>
                            <div class="p-3 bg-indigo-100 rounded-full">
                                <i class="text-xl text-indigo-600 pi pi-pencil"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Paper</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.paper }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="text-xl text-orange-600 pi pi-file"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-teal-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Desk</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.desk }}</p>
                            </div>
                            <div class="p-3 bg-teal-100 rounded-full">
                                <i class="text-xl text-teal-600 pi pi-desktop"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                        <div class="flex gap-3">
                            <Button label="Create Item" icon="pi pi-plus" severity="success"
                                @click="openCreateDialog" class="font-semibold" />
                            <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                                @click="toggleActionMenu" />
                        </div>

                        <div class="w-full lg:w-auto">
                            <span class="p-input-icon-left">
                                <i class="pi pi-search" />
                                <InputText v-model="search" placeholder="Search items..." 
                                    class="w-full lg:w-80" />
                            </span>
                        </div>
                    </div>

                    <!-- Selected Items Info -->
                    <div v-if="selectedItems.length > 0" class="p-3 mt-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800">
                                {{ selectedItems.length }} item(s) selected
                            </span>
                            <Button label="Clear" icon="pi pi-times" severity="secondary" text
                                @click="selectedItems = []" />
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="stationaryItems.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="stationaryItems.per_page" :totalRecords="stationaryItems.total"
                            :first="(stationaryItems.current_page - 1) * stationaryItems.per_page" @page="onPageChange"
                            v-model:selection="selectedItems" dataKey="id"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-box"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Stationary Items Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your search or create a new item.</p>
                                    <Button label="Create First Item" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 3rem" />

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(stationaryItems.current_page - 1) * stationaryItems.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column field="name" header="Item Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        SKU: {{ slotProps.data.sku || 'No SKU' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="category" header="Category" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getCategoryBadge(slotProps.data.category).label"
                                        :severity="getCategoryBadge(slotProps.data.category).severity"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Stock" sortable style="width: 140px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.current_stock }} {{ slotProps.data.unit }}
                                        </div>
                                        <Badge :value="getStockStatus(slotProps.data).text"
                                            :severity="getStockStatus(slotProps.data).severity"
                                            class="text-xs mt-1" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Price" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ formatCurrency(slotProps.data.cost_price) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Sell: {{ formatCurrency(slotProps.data.selling_price) }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="supplier" header="Supplier" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ slotProps.data.supplier || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data)"
                                        :severity="getStatusSeverity(slotProps.data)"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 200px">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View Details'" @click="viewItem(slotProps.data)" />

                                        <Button icon="pi pi-box" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Manage Stock'" @click="openStockDialog(slotProps.data)" />

                                        <Button icon="pi pi-history" outlined rounded severity="secondary" size="small"
                                            v-tooltip.top="'Record Movement'" @click="openMovementDialog(slotProps.data)" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit Item'" @click="openEditDialog(slotProps.data)" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete Item'" @click="deleteItem(slotProps.data.id)" />

                                            <Button icon="pi pi-history" outlined rounded severity="info" size="small"
    v-tooltip.top="'View Movement History'" 
    @click="router.visit(route('admin.stationary-items.movements', slotProps.data.id))" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Item Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Stationary Item' : 'Create New Stationary Item'" 
                :style="{ width: '750px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Item Name -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Item Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="itemForm.name" 
                                placeholder="Enter item name" 
                                class="w-full"
                                :class="{ 'p-invalid': itemForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.name">
                                {{ itemForm.errors.name }}
                            </small>
                        </div>

                        <!-- SKU -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">SKU</label>
                            <div class="flex gap-2">
                                <InputText v-model="itemForm.sku" 
                                    placeholder="Enter SKU" 
                                    class="flex-1"
                                    :class="{ 'p-invalid': itemForm.errors.sku }" />
                                <Button icon="pi pi-refresh" severity="secondary" 
                                    @click="generateSKU"
                                    v-tooltip="'Generate SKU'" />
                            </div>
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.sku">
                                {{ itemForm.errors.sku }}
                            </small>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="itemForm.category" 
                                :options="categories" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select category"
                                class="w-full"
                                :class="{ 'p-invalid': itemForm.errors.category }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.category">
                                {{ itemForm.errors.category }}
                            </small>
                        </div>

                        <!-- Unit -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Unit <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="itemForm.unit" 
                                :options="units" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select unit"
                                class="w-full"
                                :class="{ 'p-invalid': itemForm.errors.unit }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.unit">
                                {{ itemForm.errors.unit }}
                            </small>
                        </div>

                        <!-- Cost Price -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Cost Price</label>
                            <InputNumber v-model="itemForm.cost_price" 
                                :min="0" 
                                mode="currency" 
                                currency="USD" 
                                locale="en-US"
                                class="w-full" />
                        </div>

                        <!-- Selling Price -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Selling Price</label>
                            <InputNumber v-model="itemForm.selling_price" 
                                :min="0" 
                                mode="currency" 
                                currency="USD" 
                                locale="en-US"
                                class="w-full" />
                        </div>

                        <!-- Current Stock -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Current Stock</label>
                            <InputNumber v-model="itemForm.current_stock" 
                                :min="0" 
                                class="w-full" />
                        </div>

                        <!-- Minimum Stock -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Minimum Stock</label>
                            <InputNumber v-model="itemForm.min_stock" 
                                :min="0" 
                                class="w-full" />
                        </div>

                        <!-- Supplier -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Supplier</label>
                            <InputText v-model="itemForm.supplier" 
                                placeholder="Enter supplier name" 
                                class="w-full" />
                        </div>

                        <!-- Location -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Location</label>
                            <InputText v-model="itemForm.location" 
                                placeholder="Enter storage location" 
                                class="w-full" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox v-model="itemForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-sm font-semibold text-gray-700">Active Item</label>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Description</label>
                            <Textarea v-model="itemForm.description" 
                                rows="3" 
                                placeholder="Enter item description..."
                                class="w-full" />
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="itemForm.processing" />
                        <Button :label="isEditMode ? 'Update Item' : 'Create Item'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveItem"
                            :loading="itemForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- View Item Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Item Details" :style="{ width: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div v-if="viewItemData" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Item Name</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewItemData.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">SKU</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewItemData.sku || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Category</p>
                            <Badge :value="getCategoryBadge(viewItemData.category).label"
                                :severity="getCategoryBadge(viewItemData.category).severity"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Unit</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewItemData.unit }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Current Stock</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ viewItemData.current_stock }} {{ viewItemData.unit }}
                            </p>
                            <Badge :value="getStockStatus(viewItemData).text"
                                :severity="getStockStatus(viewItemData).severity"
                                class="mt-1" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Minimum Stock</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewItemData.min_stock }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Cost Price</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatCurrency(viewItemData.cost_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Selling Price</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatCurrency(viewItemData.selling_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Supplier</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ viewItemData.supplier || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Location</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ viewItemData.location || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewItemData)"
                                :severity="getStatusSeverity(viewItemData)"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(viewItemData.created_at) }}
                            </p>
                        </div>
                        <div class="md:col-span-2" v-if="viewItemData.description">
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewItemData.description }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" />
                        <Button label="Edit Item" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewItemData)" />
                        <Button label="Manage Stock" icon="pi pi-box" severity="help" 
                            @click="showViewDialog = false; openStockDialog(viewItemData)" />
                    </div>
                </div>
            </Dialog>

            <!-- Stock Management Dialog -->
            <Dialog v-model:visible="showStockDialog" modal header="Manage Stock" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Operation</label>
                        <Select v-model="stockForm.operation" :options="[
                            { label: 'Add to stock', value: 'add' },
                            { label: 'Set stock to', value: 'set' },
                            { label: 'Subtract from stock', value: 'subtract' }
                        ]" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="stockForm.type" :options="movementTypes" 
                            optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="stockForm.quantity" 
                            :min="0" 
                            class="w-full"
                            :class="{ 'p-invalid': stockForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="stockForm.errors.quantity">
                            {{ stockForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="stockForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="stockForm.notes" rows="3" placeholder="Add notes about this stock movement..."
                            class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showStockDialog = false"
                            :disabled="stockForm.processing" />
                        <Button label="Update Stock" icon="pi pi-check" severity="success" 
                            @click="updateStock" :loading="stockForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- Record Movement Dialog -->
            <Dialog v-model:visible="showMovementDialog" modal header="Record Movement" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="movementForm.type" :options="movementTypes" 
                            optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="movementForm.quantity" 
                            :min="0" 
                            class="w-full"
                            :class="{ 'p-invalid': movementForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="movementForm.errors.quantity">
                            {{ movementForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Reference</label>
                        <InputText v-model="movementForm.reference" 
                            placeholder="Enter reference (PO, invoice, etc.)" 
                            class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="movementForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="movementForm.notes" rows="3" placeholder="Add notes about this movement..."
                            class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showMovementDialog = false"
                            :disabled="movementForm.processing" />
                        <Button label="Record Movement" icon="pi pi-check" severity="success" 
                            @click="recordMovement" :loading="movementForm.processing" />
                    </div>
                </div>
            </Dialog>
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
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-odd) {
    background-color: #ffffff;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-even) {
    background-color: #f8f9fa;
}

:deep(.p-inputtext) {
    border-radius: 0.375rem;
}

:deep(.p-button) {
    border-radius: 0.375rem;
}

:deep(.p-dropdown) {
    border-radius: 0.375rem;
}

:deep(.p-inputnumber-input) {
    border-radius: 0.375rem;
}

:deep(.p-calendar) {
    border-radius: 0.375rem;
}

:deep(.p-dialog .p-dialog-header) {
    background: linear-gradient(to right, #667eea, #764ba2);
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    color: white;
    font-weight: 600;
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon) {
    color: white;
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon:hover) {
    color: #e2e8f0;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1.5rem;
}
</style>