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
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

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
const currentPerPage = ref(props.stationaryItems?.per_page || 10);

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
    currentPerPage.value = newItems?.per_page || 10;
}, { immediate: true });

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        router.get(route("admin.stationary-items.index"), {
            search: newSearch,
            page: 1,
            per_page: currentPerPage.value
        }, {
            preserveState: false,
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
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.stationary-items.index"), {
        search: search.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
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
                preserveState: false,
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

        <div class="p-3 sm:p-4 md:p-6 space-y-4 md:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-2 sm:mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-xs sm:text-sm">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Stationary Item Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Manage office stationary items and inventory</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                    <Button label="Create Item" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="flex-1 sm:flex-none text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Items</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-box"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">In Stock</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.inStock }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Low Stock</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.lowStock }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-yellow-600 pi pi-exclamation-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Out of Stock</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.outOfStock }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-red-600 pi pi-times-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Value</p>
                                <p class="mt-1 text-base sm:text-lg md:text-xl font-bold text-gray-900 break-words">{{ formatCurrency(statistics.totalValue) }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-dollar"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-3">
                            <div class="flex flex-wrap gap-1 sm:gap-2 w-full sm:w-auto">
                                <Button label="Create" icon="pi pi-plus" severity="success"
                                    @click="openCreateDialog" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <Button label="Actions" icon="pi pi-cog" severity="secondary" outlined
                                    @click="toggleActionMenu" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                            </div>

                            <div class="w-full sm:w-auto">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search items..." 
                                        class="w-full sm:w-64 md:w-80 text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>

                        <!-- Selected Items Info -->
                        <div v-if="selectedItems.length > 0" class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs sm:text-sm font-medium text-blue-800">
                                    {{ selectedItems.length }} item(s) selected
                                </span>
                                <Button label="Clear" icon="pi pi-times" severity="secondary" text size="small"
                                    @click="selectedItems = []" class="text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="stationaryItems.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="stationaryItems.per_page" 
                            :totalRecords="stationaryItems.total"
                            :first="(stationaryItems.current_page - 1) * stationaryItems.per_page" 
                            @page="onPageChange"
                            v-model:selection="selectedItems" 
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['name', 'sku', 'supplier']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-box"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Stationary Items Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Try adjusting your search or create a new item.</p>
                                    <Button label="Create First Item" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(stationaryItems.current_page - 1) * stationaryItems.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="name" header="Item Name" sortable style="min-width: 140px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        SKU: {{ slotProps.data.sku || 'No SKU' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="category" header="Category" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getCategoryBadge(slotProps.data.category).label"
                                        :severity="getCategoryBadge(slotProps.data.category).severity"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <Column header="Stock" sortable style="min-width: 110px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.current_stock }} {{ slotProps.data.unit }}
                                        </div>
                                        <Badge :value="getStockStatus(slotProps.data).text"
                                            :severity="getStockStatus(slotProps.data).severity"
                                            class="text-xs mt-1" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Price" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ formatCurrency(slotProps.data.cost_price) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Sell: {{ formatCurrency(slotProps.data.selling_price) }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="supplier" header="Supplier" sortable style="min-width: 90px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600 truncate">
                                        {{ slotProps.data.supplier || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable style="min-width: 90px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data)"
                                        :severity="getStatusSeverity(slotProps.data)"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 160px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View'" @click="viewItem(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-box" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Stock'" @click="openStockDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-history" outlined rounded severity="secondary" size="small"
                                            v-tooltip.top="'Movement'" @click="openMovementDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" @click="deleteItem(slotProps.data.id)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-history" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View Movement History'" 
                                            @click="router.visit(route('admin.stationary-items.movements', slotProps.data.id))" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ stationaryItems.current_page }} of {{ Math.ceil(stationaryItems.total / stationaryItems.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Item Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Stationary Item' : 'Create New Stationary Item'" 
                :style="{ width: '95vw', maxWidth: '750px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2">
                        <!-- Item Name -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Item Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="itemForm.name" 
                                placeholder="Enter item name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': itemForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.name">
                                {{ itemForm.errors.name }}
                            </small>
                        </div>

                        <!-- SKU -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">SKU</label>
                            <div class="flex gap-1 sm:gap-2">
                                <InputText v-model="itemForm.sku" 
                                    placeholder="Enter SKU" 
                                    class="flex-1 text-xs sm:text-sm"
                                    :class="{ 'p-invalid': itemForm.errors.sku }" />
                                <Button icon="pi pi-refresh" severity="secondary" size="small"
                                    @click="generateSKU"
                                    v-tooltip="'Generate'" class="px-2 sm:px-3" />
                            </div>
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.sku">
                                {{ itemForm.errors.sku }}
                            </small>
                        </div>

                        <!-- Category -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="itemForm.category" 
                                :options="categories" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select category"
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': itemForm.errors.category }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.category">
                                {{ itemForm.errors.category }}
                            </small>
                        </div>

                        <!-- Unit -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Unit <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="itemForm.unit" 
                                :options="units" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select unit"
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': itemForm.errors.unit }" />
                            <small class="text-red-500 text-xs" v-if="itemForm.errors.unit">
                                {{ itemForm.errors.unit }}
                            </small>
                        </div>

                        <!-- Cost Price -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Cost Price</label>
                            <InputNumber v-model="itemForm.cost_price" 
                                :min="0" 
                                mode="currency" 
                                currency="MYR" 
                                locale="ms-MY"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Selling Price -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Selling Price</label>
                            <InputNumber v-model="itemForm.selling_price" 
                                :min="0" 
                                mode="currency" 
                                currency="MYR" 
                                locale="ms-MY"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Current Stock -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Current Stock</label>
                            <InputNumber v-model="itemForm.current_stock" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Minimum Stock -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Minimum Stock</label>
                            <InputNumber v-model="itemForm.min_stock" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Supplier -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Supplier</label>
                            <InputText v-model="itemForm.supplier" 
                                placeholder="Enter supplier name" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Location -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Location</label>
                            <InputText v-model="itemForm.location" 
                                placeholder="Enter storage location" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox v-model="itemForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-xs sm:text-sm font-semibold text-gray-700">Active Item</label>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Description</label>
                            <Textarea v-model="itemForm.description" 
                                rows="3" 
                                placeholder="Enter item description..."
                                class="w-full text-xs sm:text-sm" />
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="itemForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update Item' : 'Create Item'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveItem"
                            :loading="itemForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- View Item Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Item Details" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                <div v-if="viewItemData" class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Item Name</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ viewItemData.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">SKU</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewItemData.sku || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Category</p>
                            <Badge :value="getCategoryBadge(viewItemData.category).label"
                                :severity="getCategoryBadge(viewItemData.category).severity"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Unit</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewItemData.unit }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Current Stock</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewItemData.current_stock }} {{ viewItemData.unit }}
                            </p>
                            <Badge :value="getStockStatus(viewItemData).text"
                                :severity="getStockStatus(viewItemData).severity"
                                class="mt-1 text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Minimum Stock</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewItemData.min_stock }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Cost Price</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatCurrency(viewItemData.cost_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Selling Price</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatCurrency(viewItemData.selling_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Supplier</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewItemData.supplier || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Location</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewItemData.location || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewItemData)"
                                :severity="getStatusSeverity(viewItemData)"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatDate(viewItemData.created_at) }}
                            </p>
                        </div>
                        <div class="sm:col-span-2" v-if="viewItemData.description">
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ viewItemData.description }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Edit" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewItemData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Stock" icon="pi pi-box" severity="help" 
                            @click="showViewDialog = false; openStockDialog(viewItemData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Stock Management Dialog -->
            <Dialog v-model:visible="showStockDialog" modal header="Manage Stock" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Operation</label>
                        <Select v-model="stockForm.operation" :options="[
                            { label: 'Add to stock', value: 'add' },
                            { label: 'Set stock to', value: 'set' },
                            { label: 'Subtract from stock', value: 'subtract' }
                        ]" optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="stockForm.type" :options="movementTypes" 
                            optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="stockForm.quantity" 
                            :min="0" 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': stockForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="stockForm.errors.quantity">
                            {{ stockForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="stockForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="stockForm.notes" rows="3" placeholder="Add notes about this stock movement..."
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showStockDialog = false"
                            :disabled="stockForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Update Stock" icon="pi pi-check" severity="success" 
                            @click="updateStock" :loading="stockForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Record Movement Dialog -->
            <Dialog v-model:visible="showMovementDialog" modal header="Record Movement" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="movementForm.type" :options="movementTypes" 
                            optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="movementForm.quantity" 
                            :min="0" 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': movementForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="movementForm.errors.quantity">
                            {{ movementForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Reference</label>
                        <InputText v-model="movementForm.reference" 
                            placeholder="Enter reference (PO, invoice, etc.)" 
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="movementForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="movementForm.notes" rows="3" placeholder="Add notes about this movement..."
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showMovementDialog = false"
                            :disabled="movementForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Record Movement" icon="pi pi-check" severity="success" 
                            @click="recordMovement" :loading="movementForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
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

/* Responsive button sizing */
:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        font-size: 0.875rem;
    }
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