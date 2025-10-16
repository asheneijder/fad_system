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
import Menu from 'primevue/menu';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { Link, router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed, reactive, onMounted } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    assets: Object,
    filters: Object,
    users: Array,
    categories: Array,
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Asset Management' }];

const search = ref(props.filters?.search || "");
const assets = ref(props.assets);
const selectedAssetId = ref(null);
const showAssignToDialog = ref(false);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const showStatusDialog = ref(false);
const showUnassignDialog = ref(false);
const selectedUserId = ref(null);
const showFilters = ref(false);
const isEditMode = ref(false);
const viewAssetData = ref(null);
const statusForm = useForm({
    status: '',
    remarks: '',
    returned_at: null,
});
const unassignForm = useForm({
    remarks: '',
    returned_at: null,
});

// Filter states
const selectedStatuses = ref(props.filters?.statuses || []);
const selectedCategories = ref(props.filters?.categories || []);
const dateRange = ref(null);

// Form data
const assetForm = useForm({
    asset_name: '',
    asset_tag_no: '',
    serial_no: '',
    qty: 1,
    model_type_id: null,
    category_id: null,
    status: 'available',
    description: '',
    purchase_date: null,
    purchase_price: null,
    warranty_expiry: null,
    image: null,
});

// Models data
const models = ref([]);

// Export menu
const exportMenu = ref();
const exportItems = ref([
    {
        label: 'Export as Excel',
        icon: 'pi pi-file-excel',
        command: () => exportData('excel')
    },
    {
        label: 'Export as PDF',
        icon: 'pi pi-file-pdf',
        command: () => exportData('pdf')
    },
    {
        label: 'Export as CSV',
        icon: 'pi pi-file',
        command: () => exportData('csv')
    }
]);

// Status options
const statusOptions = [
    { label: 'Available', value: 'available' },
    { label: 'Assigned', value: 'assigned' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Damaged', value: 'damaged' },
    { label: 'Lost', value: 'lost' },
    { label: 'Retired', value: 'retired' },
    { label: 'Disposed', value: 'disposed' }
];

// Statistics
const statistics = computed(() => {
    const data = assets.value.data || [];
    return {
        total: assets.value.total || 0,
        available: data.filter(a => a.status === 'available').length,
        assigned: data.filter(a => a.status === 'assigned').length,
        damaged: data.filter(a => ['damaged', 'lost', 'retired', 'disposed'].includes(a.status)).length
    };
});

// Watch for props changes
watch(() => props.assets, (newAssets) => {
    assets.value = newAssets;
});

watch(search, (newSearch) => {
    applyFilters();
});

watch(selectedStatuses, () => {
    applyFilters();
});

watch(selectedCategories, () => {
    applyFilters();
});

watch(showAssignToDialog, (val) => {
    if (!val) {
        selectedAssetId.value = null;
        selectedUserId.value = null;
    }
});

watch(showCreateEditDialog, (val) => {
    if (!val) {
        resetForm();
    }
});

watch(() => assetForm.category_id, async (newCategoryId) => {
    if (newCategoryId) {
        await loadModels(newCategoryId);
    } else {
        models.value = [];
        assetForm.model_type_id = null;
    }
});

const loadModels = async (categoryId) => {
    try {
        const response = await fetch(route('admin.assets.models', categoryId));
        models.value = await response.json();
    } catch (error) {
        console.error('Error loading models:', error);
        models.value = [];
    }
};

const applyFilters = () => {
    router.get(route("admin.assets.index"), {
        search: search.value,
        statuses: selectedStatuses.value,
        categories: selectedCategories.value,
        date_range: dateRange.value
    }, {
        preserveState: true,
        replace: true
    });
};

const clearFilters = () => {
    search.value = "";
    selectedStatuses.value = [];
    selectedCategories.value = [];
    dateRange.value = null;
    applyFilters();
};

const onPageChange = (event) => {
    router.get(route("admin.assets.index"), {
        search: search.value,
        page: event.page + 1,
        statuses: selectedStatuses.value,
        categories: selectedCategories.value
    }, {
        preserveState: true,
        replace: true
    });
};

const toggleExportMenu = (event) => {
    exportMenu.value.toggle(event);
};

const exportData = (format) => {
    toast.add({
        severity: 'info',
        summary: 'Exporting',
        detail: `Exporting data as ${format.toUpperCase()}...`,
        life: 3000
    });
    
    router.post(route('admin.assets.export'), {
        format: format,
        filters: {
            search: search.value,
            statuses: selectedStatuses.value,
            categories: selectedCategories.value
        }
    });
};

const resetForm = () => {
    assetForm.reset();
    isEditMode.value = false;
    selectedAssetId.value = null;
    models.value = [];
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    showCreateEditDialog.value = true;
};

const openEditDialog = (asset) => {
    isEditMode.value = true;
    selectedAssetId.value = asset.id;
    
    assetForm.asset_name = asset.asset_name;
    assetForm.asset_tag_no = asset.asset_tag_no;
    assetForm.serial_no = asset.serial_no;
    assetForm.qty = asset.qty;
    assetForm.model_type_id = asset.model_type_id;
    assetForm.category_id = asset.model_type?.category_type_id || null;
    assetForm.status = asset.status;
    assetForm.description = asset.description || '';
    assetForm.purchase_date = asset.purchase_date;
    assetForm.purchase_price = asset.purchase_price;
    assetForm.warranty_expiry = asset.warranty_expiry;
    
    // Load models for the category
    if (asset.model_type?.category_type_id) {
        loadModels(asset.model_type.category_type_id);
    }
    
    showCreateEditDialog.value = true;
};

const viewAsset = (asset) => {
    viewAssetData.value = asset;
    showViewDialog.value = true;
};

const openStatusDialog = (asset) => {
    selectedAssetId.value = asset.id;
    statusForm.status = asset.status;
    statusForm.remarks = '';
    statusForm.returned_at = null;
    showStatusDialog.value = true;
};

const openUnassignDialog = (asset) => {
    selectedAssetId.value = asset.id;
    unassignForm.remarks = '';
    unassignForm.returned_at = new Date();
    showUnassignDialog.value = true;
};

const saveAsset = () => {
    if (isEditMode.value) {
        assetForm.put(route('admin.assets.update', selectedAssetId.value), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Asset updated successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Please check all required fields',
                    life: 3000
                });
            }
        });
    } else {
        assetForm.post(route('admin.assets.store'), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Asset created successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Please check all required fields',
                    life: 3000
                });
            }
        });
    }
};

const openAssignDialog = (assetId) => {
    selectedAssetId.value = assetId;
    selectedUserId.value = null;
    showAssignToDialog.value = true;
};

const assignAsset = () => {
    if (!selectedUserId.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validation',
            detail: 'Please select a user.',
            life: 3000
        });
        return;
    }

    router.post(route('admin.asset.assign'), {
        asset_id: selectedAssetId.value,
        user_id: selectedUserId.value
    }, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Asset assigned successfully.',
                life: 3000
            });

            showAssignToDialog.value = false;
            selectedUserId.value = null;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.message || 'Failed to assign asset',
                life: 3000
            });
        }
    });
};

const updateStatus = () => {
    statusForm.put(route('admin.asset.status', selectedAssetId.value), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Asset status updated successfully.',
                life: 3000
            });
            showStatusDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update asset status',
                life: 3000
            });
        }
    });
};

const unassignAsset = () => {
    unassignForm.post(route('admin.asset.unassign', selectedAssetId.value), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Asset unassigned successfully.',
                life: 3000
            });
            showUnassignDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.message || 'Failed to unassign asset',
                life: 3000
            });
        }
    });
};

const deleteAsset = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this asset?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.assets.destroy", { asset: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Asset deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete asset",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'available': return 'success';
        case 'assigned': return 'info';
        case 'active': return 'warning';
        case 'inactive': return 'secondary';
        case 'damaged': return 'danger';
        case 'lost': return 'danger';
        case 'retired': return 'contrast';
        case 'disposed': return 'contrast';
        default: return 'info';
    }
};

const formatCurrency = (amount) => {
    if (!amount) return '—';
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
</script>

<template>
    <Head title="Assets Management" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />
        <Menu ref="exportMenu" :model="exportItems" :popup="true" />

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
                    <h1 class="text-3xl font-bold text-gray-800">Asset Management</h1>
                    <p class="mt-1 text-gray-500">Manage and track all your company assets</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Assets</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-2xl text-blue-600 pi pi-box"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Available</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900">{{ statistics.available }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-2xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Assigned</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900">{{ statistics.assigned }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-2xl text-yellow-600 pi pi-users"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Issues</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900">{{ statistics.damaged }}</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-2xl text-red-600 pi pi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="space-y-4">
                        <!-- Top Actions -->
                        <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                            <div class="flex gap-3">
                                <Button label="Create Asset" icon="pi pi-plus" severity="success"
                                    @click="openCreateDialog" class="font-semibold" />
                                <Button label="Export" icon="pi pi-download" severity="help" outlined
                                    @click="toggleExportMenu" class="font-semibold" />
                            </div>

                            <div class="flex flex-col w-full gap-3 lg:flex-row lg:w-auto">
                                <InputText v-model="search" placeholder="Search assets..." 
                                    class="lg:w-80">
                                    <template #prefix>
                                        <i class="pi pi-search"></i>
                                    </template>
                                </InputText>
                                <Button :label="showFilters ? 'Hide Filters' : 'Show Filters'" icon="pi pi-filter"
                                    severity="secondary" outlined @click="showFilters = !showFilters"
                                    :badge="(selectedStatuses.length + selectedCategories.length).toString()"
                                    badgeSeverity="info" />
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <div v-if="showFilters"
                            class="p-6 transition-all duration-300 border border-gray-200 rounded-lg bg-gray-50">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-gray-700">Status</label>
                                    <MultiSelect v-model="selectedStatuses" :options="statusOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select Status"
                                        class="w-full" display="chip" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-gray-700">Category</label>
                                    <MultiSelect v-model="selectedCategories" :options="props.categories || []"
                                        optionLabel="category_name" optionValue="id" placeholder="Select Categories"
                                        class="w-full" display="chip" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-gray-700">Date Range</label>
                                    <Calendar v-model="dateRange" selectionMode="range" placeholder="Select Date Range"
                                        class="w-full" dateFormat="yy-mm-dd" />
                                </div>

                                <div class="flex items-end gap-2 md:col-span-3">
                                    <Button label="Apply Filters" icon="pi pi-check" severity="info"
                                        @click="applyFilters" class="flex-1" />
                                    <Button label="Clear" icon="pi pi-times" severity="secondary" outlined
                                        @click="clearFilters" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="assets.data" :loading="assets.loading" showGridlines stripedRows
                            :rowHover="true" paginator lazy :rows="assets.per_page" :totalRecords="assets.total"
                            :first="(assets.current_page - 1) * assets.per_page" @page="onPageChange"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-inbox"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Assets Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your filters or create a new asset.</p>
                                    <Button label="Create First Asset" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" />
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;" class="font-semibold">
                                <template #body="slotProps">
                                    <Badge :value="(assets.current_page - 1) * assets.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column field="asset_name" header="Asset Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.asset_name }}</div>
                                    <div class="text-sm text-gray-500">{{ slotProps.data.asset_tag_no }}</div>
                                </template>
                            </Column>

                            <Column field="serial_no" header="Serial No." sortable>
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.serial_no">{{ slotProps.data.serial_no }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </template>
                            </Column>

                            <Column field="qty" header="Qty" sortable style="width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.qty" severity="info" />
                                </template>
                            </Column>

                            <Column header="Model & Category">
                                <template #body="slotProps">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.model_type?.model_name || '—' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ slotProps.data.model_type?.category_type?.category_name || '—' }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.status" 
                                        :severity="getStatusSeverity(slotProps.data.status)" 
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Assigned To" style="width: 150px;">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.current_assignment">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.current_assignment.user?.name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ formatDate(slotProps.data.current_assignment.assigned_at) }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 200px">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View Details'" @click="viewAsset(slotProps.data)" />

                                        <Button v-if="slotProps.data.status === 'available'" icon="pi pi-user-plus"
                                            outlined rounded severity="help" size="small" v-tooltip.top="'Assign to User'"
                                            @click="openAssignDialog(slotProps.data.id)" />

                                        <Button v-if="slotProps.data.status === 'assigned'" icon="pi pi-user-minus"
                                            outlined rounded severity="warning" size="small" v-tooltip.top="'Unassign'"
                                            @click="openUnassignDialog(slotProps.data)" />

                                        <Button icon="pi pi-cog" outlined rounded severity="secondary" size="small"
                                            v-tooltip.top="'Change Status'" @click="openStatusDialog(slotProps.data)" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit Asset'" @click="openEditDialog(slotProps.data)" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete Asset'" @click="deleteAsset(slotProps.data.id)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Asset Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Asset' : 'Create New Asset'" 
                :style="{ width: '800px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Asset Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Asset Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.asset_name" 
                                placeholder="Enter asset name" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.asset_name }" />
                            <small class="text-red-500" v-if="assetForm.errors.asset_name">
                                {{ assetForm.errors.asset_name }}
                            </small>
                        </div>

                        <!-- Asset Tag No -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Asset Tag No. <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.asset_tag_no" 
                                placeholder="Enter tag number" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.asset_tag_no }" />
                            <small class="text-red-500" v-if="assetForm.errors.asset_tag_no">
                                {{ assetForm.errors.asset_tag_no }}
                            </small>
                        </div>

                        <!-- Serial No -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Serial Number</label>
                            <InputText v-model="assetForm.serial_no" 
                                placeholder="Enter serial number" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.serial_no }" />
                            <small class="text-red-500" v-if="assetForm.errors.serial_no">
                                {{ assetForm.errors.serial_no }}
                            </small>
                        </div>

                        <!-- Quantity -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <InputNumber v-model="assetForm.qty" 
                                :min="1" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.qty }" />
                            <small class="text-red-500" v-if="assetForm.errors.qty">
                                {{ assetForm.errors.qty }}
                            </small>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.category_id" 
                                :options="props.categories || []" 
                                optionLabel="category_name" 
                                optionValue="id"
                                placeholder="Select category" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.category_id }" />
                            <small class="text-red-500" v-if="assetForm.errors.category_id">
                                {{ assetForm.errors.category_id }}
                            </small>
                        </div>

                        <!-- Model -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Model <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.model_type_id" 
                                :options="models" 
                                optionLabel="model_name" 
                                optionValue="id"
                                placeholder="Select model" 
                                class="w-full"
                                :disabled="!assetForm.category_id"
                                :class="{ 'p-invalid': assetForm.errors.model_type_id }" />
                            <small class="text-red-500" v-if="assetForm.errors.model_type_id">
                                {{ assetForm.errors.model_type_id }}
                            </small>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.status" 
                                :options="statusOptions" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select status" 
                                class="w-full"
                                :class="{ 'p-invalid': assetForm.errors.status }" />
                            <small class="text-red-500" v-if="assetForm.errors.status">
                                {{ assetForm.errors.status }}
                            </small>
                        </div>

                        <!-- Purchase Date -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Purchase Date</label>
                            <Calendar v-model="assetForm.purchase_date" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full" />
                        </div>

                        <!-- Purchase Price -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Purchase Price</label>
                            <InputNumber v-model="assetForm.purchase_price" 
                                mode="currency" 
                                currency="USD"
                                placeholder="0.00" 
                                class="w-full" />
                        </div>

                        <!-- Warranty Expiry -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Warranty Expiry</label>
                            <Calendar v-model="assetForm.warranty_expiry" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full" />
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Description</label>
                        <Textarea v-model="assetForm.description" 
                            rows="4" 
                            placeholder="Enter asset description..."
                            class="w-full" />
                    </div>

                    <!-- Image Upload -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Asset Image</label>
                        <input type="file" @change="assetForm.image = $event.target.files[0]" 
                            accept="image/*" class="w-full" />
                        <small class="text-gray-500">Supported formats: JPEG, PNG, JPG, GIF (Max: 2MB)</small>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false" />
                        <Button :label="isEditMode ? 'Update Asset' : 'Create Asset'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveAsset"
                            :loading="assetForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- View Asset Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Asset Details" :style="{ width: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div v-if="viewAssetData" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Asset Name</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.asset_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tag Number</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.asset_tag_no }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Serial Number</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.serial_no || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Quantity</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.qty }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Model</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.model_type?.model_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Category</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewAssetData.model_type?.category_type?.category_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="viewAssetData.status" 
                                :severity="getStatusSeverity(viewAssetData.status)" 
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Purchase Date</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ formatDate(viewAssetData.purchase_date) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Purchase Price</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ formatCurrency(viewAssetData.purchase_price) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Warranty Expiry</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ formatDate(viewAssetData.warranty_expiry) }}</p>
                        </div>
                        <div v-if="viewAssetData.current_assignment" class="col-span-2">
                            <p class="text-sm font-medium text-gray-500">Currently Assigned To</p>
                            <div class="flex items-center gap-3 mt-2 p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-center w-10 h-10 text-white bg-purple-500 rounded-full">
                                    <span class="font-semibold">{{ viewAssetData.current_assignment.user?.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ viewAssetData.current_assignment.user?.name }}</p>
                                    <p class="text-sm text-gray-500">{{ viewAssetData.current_assignment.user?.email }}</p>
                                    <p class="text-xs text-gray-400">Assigned on: {{ formatDate(viewAssetData.current_assignment.assigned_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="viewAssetData.description" class="pt-4 border-t">
                        <p class="text-sm font-medium text-gray-500">Description</p>
                        <p class="mt-2 text-gray-700">{{ viewAssetData.description }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" />
                        <Button label="Edit Asset" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewAssetData)" />
                    </div>
                </div>
            </Dialog>

            <!-- Assign Dialog -->
            <Dialog v-model:visible="showAssignToDialog" modal header="Assign Asset to User" :style="{ width: '450px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="p-4 border-l-4 border-blue-500 rounded bg-blue-50">
                        <p class="text-sm text-blue-800">
                            <i class="mr-2 pi pi-info-circle"></i>
                            Select a user to assign this asset to. The asset status will be updated automatically.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Select User *</label>
                        <Select v-model="selectedUserId" :options="props.users" optionLabel="name" optionValue="id"
                            filter placeholder="Choose a user..." class="w-full">
                            <template #option="slotProps">
                                <div class="flex items-center gap-3 p-2">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 text-white bg-purple-500 rounded-full">
                                        <span class="font-semibold">{{ slotProps.option.name.charAt(0) }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-900">{{ slotProps.option.name }}</span>
                                        <small class="text-gray-500">{{ slotProps.option.email }}</small>
                                    </div>
                                </div>
                            </template>

                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 text-white bg-purple-500 rounded-full">
                                        <span class="text-sm font-semibold">
                                            {{ props.users.find(u => u.id === slotProps.value)?.name.charAt(0) }}
                                        </span>
                                    </div>
                                    <span>{{ props.users.find(u => u.id === slotProps.value)?.name }}</span>
                                </div>
                                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                            </template>
                        </Select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showAssignToDialog = false" />
                        <Button label="Assign Asset" icon="pi pi-check" severity="success" @click="assignAsset" />
                    </div>
                </div>
            </Dialog>

            <!-- Status Update Dialog -->
            <Dialog v-model:visible="showStatusDialog" modal header="Update Asset Status" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Status *</label>
                        <Select v-model="statusForm.status" :options="statusOptions" optionLabel="label" optionValue="value"
                            placeholder="Select status" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Remarks</label>
                        <Textarea v-model="statusForm.remarks" rows="3" placeholder="Enter remarks..."
                            class="w-full" />
                    </div>

                    <div v-if="['retired', 'disposed', 'lost', 'damaged', 'inactive'].includes(statusForm.status)" 
                         class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Return Date *</label>
                        <Calendar v-model="statusForm.returned_at" dateFormat="yy-mm-dd" 
                            placeholder="Select return date" class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showStatusDialog = false" />
                        <Button label="Update Status" icon="pi pi-check" severity="success" 
                            @click="updateStatus" :loading="statusForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- Unassign Dialog -->
            <Dialog v-model:visible="showUnassignDialog" modal header="Unassign Asset" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="p-4 border-l-4 border-yellow-500 rounded bg-yellow-50">
                        <p class="text-sm text-yellow-800">
                            <i class="mr-2 pi pi-exclamation-triangle"></i>
                            This will mark the asset as available and record the return details.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Return Date *</label>
                        <Calendar v-model="unassignForm.returned_at" dateFormat="yy-mm-dd" 
                            placeholder="Select return date" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Remarks</label>
                        <Textarea v-model="unassignForm.remarks" rows="3" placeholder="Enter return remarks..."
                            class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showUnassignDialog = false" />
                        <Button label="Unassign Asset" icon="pi pi-check" severity="warning" 
                            @click="unassignAsset" :loading="unassignForm.processing" />
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
    border-radius: 0.5rem;
}

:deep(.p-button) {
    border-radius: 0.5rem;
}

:deep(.p-multiselect) {
    border-radius: 0.5rem;
}

:deep(.p-calendar) {
    border-radius: 0.5rem;
}

:deep(.p-select) {
    border-radius: 0.5rem;
}

:deep(.p-textarea) {
    border-radius: 0.5rem;
}

:deep(.p-inputnumber-input) {
    border-radius: 0.5rem;
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
    padding: 2rem;
}
</style>