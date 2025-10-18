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
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    assets: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', category: '' })
    },
    categories: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    },
    models: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({})
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Asset Management' }];

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const categoryFilter = ref(props.filters?.category || "");
const assets = ref(props.assets);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const showAssignDialog = ref(false);
const showBulkAssignDialog = ref(false);
const isEditMode = ref(false);
const selectedAssetId = ref(null);
const viewAssetData = ref(null);
const selectedAssets = ref([]);
const actionMenu = ref();
const currentPerPage = ref(props.assets?.per_page || 10);

// Forms
const assetForm = useForm({
    name: '',
    asset_tag: '',
    serial_number: '',
    model_id: null,
    status: 'available',
    purchase_date: null,
    purchase_cost: 0,
    warranty_months: 0,
    notes: '',
    image: '',
    location: '',
});

const assignForm = useForm({
    asset_id: null,
    user_id: null,
    notes: '',
    condition_assigned: '',
});

const bulkAssignForm = useForm({
    asset_ids: [],
    user_id: null,
    notes: '',
    condition_assigned: '',
});

const bulkStatusForm = useForm({
    asset_ids: [],
    status: 'available',
});

// Status options
const statusOptions = ref([
    { label: 'All Status', value: '' },
    { label: 'Available', value: 'available' },
    { label: 'Assigned', value: 'assigned' },
    { label: 'Maintenance', value: 'maintenance' },
    { label: 'Retired', value: 'retired' },
]);

// Category options
const categoryOptions = computed(() => {
    return [
        { name: 'All Categories', id: '' },
        ...props.categories
    ];
});

// Action menu items
const actionItems = ref([
    {
        label: 'Bulk Actions',
        items: [
            {
                label: 'Bulk Assign',
                icon: 'pi pi-user-plus',
                command: () => openBulkAssignDialog()
            },
            {
                label: 'Set Available',
                icon: 'pi pi-check',
                command: () => bulkUpdateStatus('available')
            },
            {
                label: 'Set Maintenance',
                icon: 'pi pi-wrench',
                command: () => bulkUpdateStatus('maintenance')
            },
            {
                label: 'Set Retired',
                icon: 'pi pi-times',
                command: () => bulkUpdateStatus('retired')
            },
            {
                label: 'Export Selected',
                icon: 'pi pi-download',
                command: () => exportSelected()
            }
        ]
    }
]);

// Watchers
watch(() => props.assets, (newAssets) => {
    assets.value = newAssets;
    currentPerPage.value = newAssets?.per_page || 10;
}, { immediate: true });

watch([search, statusFilter, categoryFilter], ([newSearch, newStatus, newCategory], [oldSearch, oldStatus, oldCategory]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newCategory !== oldCategory) {
        router.get(route("admin.assets.index"), {
            search: newSearch,
            status: newStatus,
            category: newCategory,
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
    if (!val) resetForm();
});

watch(showAssignDialog, (val) => {
    if (!val) {
        assignForm.reset();
        selectedAssetId.value = null;
    }
});

watch(showBulkAssignDialog, (val) => {
    if (!val) bulkAssignForm.reset();
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.assets.index"), {
        search: search.value,
        status: statusFilter.value,
        category: categoryFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const resetForm = () => {
    assetForm.reset();
    isEditMode.value = false;
    selectedAssetId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    assetForm.status = 'available';
    assetForm.purchase_cost = 0;
    assetForm.warranty_months = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (asset) => {
    isEditMode.value = true;
    selectedAssetId.value = asset.id;
    
    assetForm.name = asset.name;
    assetForm.asset_tag = asset.asset_tag;
    assetForm.serial_number = asset.serial_number || '';
    assetForm.model_id = asset.model_id;
    assetForm.status = asset.status;
    assetForm.purchase_date = asset.purchase_date ? new Date(asset.purchase_date) : null;
    assetForm.purchase_cost = asset.purchase_cost || 0;
    assetForm.warranty_months = asset.warranty_months || 0;
    assetForm.notes = asset.notes || '';
    assetForm.image = asset.image || '';
    assetForm.location = asset.location || '';
    
    showCreateEditDialog.value = true;
};

const viewAsset = (asset) => {
    viewAssetData.value = asset;
    showViewDialog.value = true;
};

const openAssignDialog = (asset) => {
    assignForm.reset();
    assignForm.asset_id = asset.id;
    showAssignDialog.value = true;
};

const openBulkAssignDialog = () => {
    if (selectedAssets.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select assets first',
            life: 3000
        });
        return;
    }
    
    bulkAssignForm.reset();
    bulkAssignForm.asset_ids = selectedAssets.value.map(asset => asset.id);
    showBulkAssignDialog.value = true;
};

const saveAsset = () => {
    if (isEditMode.value) {
        assetForm.put(route('admin.assets.update', selectedAssetId.value), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Asset updated successfully', life: 3000 });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.asset_tag) errorMessage = 'Asset tag already exists';
                else if (errors.serial_number) errorMessage = 'Serial number already exists';
                toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
            }
        });
    } else {
        assetForm.post(route('admin.assets.store'), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Asset created successfully', life: 3000 });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.asset_tag) errorMessage = 'Asset tag already exists';
                else if (errors.serial_number) errorMessage = 'Serial number already exists';
                toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
            }
        });
    }
};

const submitAssignment = () => {
    assignForm.post(route('admin.assets.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignDialog.value = false;
            toast.add({ severity: 'success', summary: 'Success', detail: 'Asset assigned successfully', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to assign asset', life: 3000 });
        }
    });
};

const submitBulkAssignment = () => {
    bulkAssignForm.post(route('admin.assets.bulk-assign'), {
        preserveScroll: true,
        onSuccess: (response) => {
            showBulkAssignDialog.value = false;
            selectedAssets.value = [];
            toast.add({ severity: 'success', summary: 'Success', detail: response.props.success_count + ' asset(s) assigned', life: 5000 });
            
            if (response.props.failed_assets?.length > 0) {
                toast.add({ severity: 'warn', summary: 'Partial Success', detail: 'Some assets could not be assigned', life: 6000 });
            }
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to assign assets', life: 3000 });
        }
    });
};

const bulkUpdateStatus = (status) => {
    if (selectedAssets.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'No Selection', detail: 'Please select assets first', life: 3000 });
        return;
    }

    bulkStatusForm.asset_ids = selectedAssets.value.map(asset => asset.id);
    bulkStatusForm.status = status;

    bulkStatusForm.post(route('admin.assets.bulk-update-status'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Success', detail: `${selectedAssets.value.length} asset(s) updated`, life: 3000 });
            selectedAssets.value = [];
            bulkStatusForm.reset();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update assets status', life: 3000 });
        }
    });
};

const returnAsset = (asset) => {
    confirm.require({
        message: `Return this asset from ${asset.user?.name || 'user'}?`,
        header: "Return Asset Confirmation",
        icon: "pi pi-arrow-left",
        accept: () => {
            router.post(route('admin.assets.return', asset.id), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: "success", summary: "Returned", detail: "Asset returned successfully", life: 3000 }),
                onError: () => toast.add({ severity: "error", summary: "Error", detail: "Failed to return asset", life: 3000 })
            });
        },
    });
};

const deleteAsset = (id) => {
    confirm.require({
        message: "Delete this asset? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.assets.destroy", id), {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: "success", summary: "Deleted", detail: "Asset deleted successfully", life: 3000 }),
                onError: (errors) => toast.add({ severity: "error", summary: "Error", detail: errors.message || "Failed to delete asset", life: 3000 })
            });
        },
    });
};

const exportSelected = () => {
    if (selectedAssets.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'No Selection', detail: 'Please select assets to export', life: 3000 });
        return;
    }

    const assetIds = selectedAssets.value.map(asset => asset.id);
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.assets.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    assetIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'asset_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const getStatusSeverity = (status) => {
    const map = { available: 'success', assigned: 'info', maintenance: 'warning', retired: 'danger' };
    return map[status] || 'secondary';
};

const getStatusText = (status) => status.charAt(0).toUpperCase() + status.slice(1);

const formatDate = (date) => {
    if (!date) return '—';
    try {
        return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return '—';
    }
};

const formatCurrency = (amount) => {
    if (!amount) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};

const daysUntilWarrantyExpiry = (asset) => {
    if (!asset.purchase_date || !asset.warranty_months) return null;
    
    const purchaseDate = new Date(asset.purchase_date);
    const warrantyExpiry = new Date(purchaseDate);
    warrantyExpiry.setMonth(purchaseDate.getMonth() + asset.warranty_months);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    warrantyExpiry.setHours(0, 0, 0, 0);
    
    return Math.ceil((warrantyExpiry - today) / (1000 * 60 * 60 * 24));
};

const getWarrantyStatus = (asset) => {
    const days = daysUntilWarrantyExpiry(asset);
    if (days === null) return { text: 'No Warranty', severity: 'secondary' };
    if (days < 0) return { text: 'Expired', severity: 'danger' };
    if (days <= 30) return { text: 'Expiring Soon', severity: 'warning' };
    return { text: 'Active', severity: 'success' };
};

const toggleActionMenu = (event) => actionMenu.value.toggle(event);

const viewAssignmentHistory = (asset) => router.visit(route('admin.assets.assignment-history', asset.id));
</script>

<template>
    <Head title="Asset Management" />
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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Asset Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Manage and track all company assets</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" class="flex-1 min-w-fit text-xs sm:text-sm" />
                    <Button label="Create Asset" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="flex-1 min-w-fit text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Assets</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total || 0 }}</p>
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
                                <p class="text-xs font-medium text-gray-500 truncate">Available</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.available || 0 }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Assigned</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.assigned || 0 }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-user"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Maintenance</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.maintenance || 0 }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-yellow-600 pi pi-wrench"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Retired</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.retired || 0 }}</p>
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
                                <p class="text-xs font-medium text-gray-500 truncate">With Warranty</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">
                                    {{ assets.data.filter(a => a.warranty_months > 0).length }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-shield"></i>
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

                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="All Status" 
                                    class="w-full sm:w-36 text-xs sm:text-sm" />
                                <Select v-model="categoryFilter" :options="categoryOptions" optionLabel="name" 
                                    optionValue="id" placeholder="All Categories" 
                                    class="w-full sm:w-36 text-xs sm:text-sm" />
                                <IconField iconPosition="left" class="w-full sm:w-64">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search..." 
                                        class="w-full text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>

                        <!-- Selected Assets Info -->
                        <div v-if="selectedAssets.length > 0" class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs sm:text-sm font-medium text-blue-800">
                                    {{ selectedAssets.length }} asset(s) selected
                                </span>
                                <Button label="Clear" icon="pi pi-times" severity="secondary" text size="small"
                                    @click="selectedAssets = []" class="text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="assets.data" showGridlines stripedRows :rowHover="true" paginator 
                            :rows="assets.per_page" :totalRecords="assets.total"
                            :first="(assets.current_page - 1) * assets.per_page" @page="onPageChange"
                            v-model:selection="selectedAssets" dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-box"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Assets Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Try adjusting your search or create a new asset.</p>
                                    <Button label="Create First Asset" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(assets.current_page - 1) * assets.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="name" header="Asset Details" sortable style="min-width: 180px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ slotProps.data.asset_tag }} • {{ slotProps.data.model?.brand }} {{ slotProps.data.model?.name }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Category" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900">
                                        {{ slotProps.data.model?.category?.name || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="serial_number" header="Serial No" sortable style="min-width: 110px;">
                                <template #body="slotProps">
                                    <Badge v-if="slotProps.data.serial_number" :value="slotProps.data.serial_number.substring(0, 10)" 
                                        severity="info" class="text-xs" />
                                    <Badge v-else value="—" severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column header="Status" sortable field="status" style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data.status)"
                                        :severity="getStatusSeverity(slotProps.data.status)"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <Column header="Assigned To" sortable style="min-width: 130px;">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.user" class="text-xs">
                                        <div class="font-medium text-gray-900 truncate">{{ slotProps.data.user.name }}</div>
                                        <div class="text-gray-500">{{ formatDate(slotProps.data.assigned_at) }}</div>
                                    </div>
                                    <Badge v-else value="Not Assigned" severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column header="Warranty" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getWarrantyStatus(slotProps.data).text"
                                        :severity="getWarrantyStatus(slotProps.data).severity"
                                        class="text-xs" />
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 180px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View'" @click="viewAsset(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-history" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'History'" 
                                            @click="viewAssignmentHistory(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="slotProps.data.status === 'available'" 
                                            icon="pi pi-user-plus" outlined rounded severity="success" size="small"
                                            v-tooltip.top="'Assign'" 
                                            @click="openAssignDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-else-if="slotProps.data.status === 'assigned'"
                                            icon="pi pi-arrow-left" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Return'" 
                                            @click="returnAsset(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" 
                                            @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" 
                                            @click="deleteAsset(slotProps.data.id)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ assets.current_page }} of {{ Math.ceil(assets.total / assets.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Asset Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Asset' : 'Create New Asset'" 
                :style="{ width: '95vw', maxWidth: '750px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Asset Name -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Asset Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.name" 
                                placeholder="Enter asset name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.name">
                                {{ assetForm.errors.name }}
                            </small>
                        </div>

                        <!-- Asset Tag -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Asset Tag <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.asset_tag" 
                                placeholder="Enter asset tag" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.asset_tag }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.asset_tag">
                                {{ assetForm.errors.asset_tag }}
                            </small>
                        </div>

                        <!-- Serial Number -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Serial Number</label>
                            <InputText v-model="assetForm.serial_number" 
                                placeholder="Enter serial number" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.serial_number }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.serial_number">
                                {{ assetForm.errors.serial_number }}
                            </small>
                        </div>

                        <!-- Model -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Model <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.model_id" :options="models" optionLabel="name" 
                                optionValue="id" placeholder="Select model" class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.model_id }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.model_id">
                                {{ assetForm.errors.model_id }}
                            </small>
                        </div>

                        <!-- Status -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.status" :options="[
                                { label: 'Available', value: 'available' },
                                { label: 'Assigned', value: 'assigned' },
                                { label: 'Maintenance', value: 'maintenance' },
                                { label: 'Retired', value: 'retired' }
                            ]" optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assetForm.errors.status }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.status">
                                {{ assetForm.errors.status }}
                            </small>
                        </div>

                        <!-- Purchase Date -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Purchase Date</label>
                            <DatePicker v-model="assetForm.purchase_date" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full text-xs sm:text-sm"
                                showIcon />
                        </div>

                        <!-- Purchase Cost -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Purchase Cost</label>
                            <InputNumber v-model="assetForm.purchase_cost" 
                                :min="0" 
                                mode="currency" 
                                currency="USD" 
                                locale="en-US"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Warranty Months -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Warranty (Months)</label>
                            <InputNumber v-model="assetForm.warranty_months" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Location -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Location</label>
                            <InputText v-model="assetForm.location" 
                                placeholder="Enter location" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Image URL -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Image URL</label>
                            <InputText v-model="assetForm.image" 
                                placeholder="Enter image URL" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Notes -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes</label>
                            <Textarea v-model="assetForm.notes" 
                                rows="3" 
                                placeholder="Enter asset notes..."
                                class="w-full text-xs sm:text-sm" />
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="assetForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update' : 'Create'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveAsset"
                            :loading="assetForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- View Asset Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Asset Details" 
                :style="{ width: '95vw', maxWidth: '700px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                <div v-if="viewAssetData" class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Asset Name</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900 break-words">{{ viewAssetData.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Asset Tag</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">{{ viewAssetData.asset_tag }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Serial Number</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900 break-all">
                                {{ viewAssetData.serial_number || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Model</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ viewAssetData.model?.brand }} {{ viewAssetData.model?.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Category</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ viewAssetData.model?.category?.name || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewAssetData.status)"
                                :severity="getStatusSeverity(viewAssetData.status)"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Assigned To</p>
                            <div v-if="viewAssetData.user" class="mt-1">
                                <p class="text-xs sm:text-base font-semibold text-gray-900">{{ viewAssetData.user.name }}</p>
                                <p class="text-xs text-gray-500 break-all">{{ viewAssetData.user.email }}</p>
                                <p class="text-xs text-gray-400">Since {{ formatDate(viewAssetData.assigned_at) }}</p>
                            </div>
                            <Badge v-else value="Not Assigned" severity="secondary" class="mt-1 text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Warranty Status</p>
                            <Badge :value="getWarrantyStatus(viewAssetData).text"
                                :severity="getWarrantyStatus(viewAssetData).severity"
                                class="mt-1 text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Purchase Date</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ formatDate(viewAssetData.purchase_date) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Purchase Cost</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ formatCurrency(viewAssetData.purchase_cost) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Location</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ viewAssetData.location || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ formatDate(viewAssetData.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="viewAssetData.notes" class="md:col-span-2">
                        <p class="text-xs sm:text-sm font-medium text-gray-500">Notes</p>
                        <p class="mt-1 text-xs sm:text-base text-gray-900">{{ viewAssetData.notes }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Edit" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button v-if="viewAssetData.status === 'available'" 
                            label="Assign" icon="pi pi-user-plus" severity="success" 
                            @click="showViewDialog = false; openAssignDialog(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button v-else-if="viewAssetData.status === 'assigned'"
                            label="Return" icon="pi pi-arrow-left" severity="warning" 
                            @click="showViewDialog = false; returnAsset(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Assign Asset Dialog -->
            <Dialog v-model:visible="showAssignDialog" modal header="Assign Asset to User" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            User <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="assignForm.user_id" :options="users" optionLabel="name" 
                            optionValue="id" placeholder="Select user" class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assignForm.errors.user_id }" />
                        <small class="text-red-500 text-xs" v-if="assignForm.errors.user_id">
                            {{ assignForm.errors.user_id }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Condition Assigned</label>
                        <InputText v-model="assignForm.condition_assigned" 
                            placeholder="Describe the condition"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assignForm.errors.condition_assigned }" />
                        <small class="text-red-500 text-xs" v-if="assignForm.errors.condition_assigned">
                            {{ assignForm.errors.condition_assigned }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <Textarea v-model="assignForm.notes" 
                            placeholder="Additional notes (optional)"
                            rows="3"
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showAssignDialog = false"
                            :disabled="assignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Assign" icon="pi pi-user-plus" severity="success" 
                            @click="submitAssignment" :loading="assignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Bulk Assign Dialog -->
            <Dialog v-model:visible="showBulkAssignDialog" modal header="Bulk Assign Assets" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs sm:text-sm text-blue-800">
                            Assigning {{ selectedAssets.length }} asset(s) to user
                        </p>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            User <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="bulkAssignForm.user_id" :options="users" optionLabel="name" 
                            optionValue="id" placeholder="Select user" class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': bulkAssignForm.errors.user_id }" />
                        <small class="text-red-500 text-xs" v-if="bulkAssignForm.errors.user_id">
                            {{ bulkAssignForm.errors.user_id }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Condition Assigned</label>
                        <InputText v-model="bulkAssignForm.condition_assigned" 
                            placeholder="Describe the condition"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': bulkAssignForm.errors.condition_assigned }" />
                        <small class="text-red-500 text-xs" v-if="bulkAssignForm.errors.condition_assigned">
                            {{ bulkAssignForm.errors.condition_assigned }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <Textarea v-model="bulkAssignForm.notes" 
                            placeholder="Additional notes (optional)"
                            rows="3"
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showBulkAssignDialog = false"
                            :disabled="bulkAssignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Assign Assets" icon="pi pi-user-plus" severity="success" 
                            @click="submitBulkAssignment" :loading="bulkAssignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
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
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 0.875rem;
    }
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

:deep(.p-inputtext),
:deep(.p-button),
:deep(.p-dropdown),
:deep(.p-calendar),
:deep(.p-inputnumber-input) {
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
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-header .p-dialog-title) {
        font-size: 1rem;
    }
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon) {
    color: white;
}

:deep(.p-dialog .p-dialog-header .p-dialog-header-icon:hover) {
    color: #e2e8f0;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1.5rem;
    }
}

:deep(.p-paginator) {
    flex-wrap: wrap;
    gap: 0.5rem;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 0 0 0.375rem 0.375rem;
}

@media (min-width: 640px) {
    :deep(.p-paginator) {
        padding: 1rem;
    }
}

:deep(.p-paginator .p-paginator-pages) {
    flex-wrap: wrap;
}

:deep(.p-paginator-current) {
    font-size: 0.75rem;
    align-self: center;
}

@media (min-width: 640px) {
    :deep(.p-paginator-current) {
        font-size: 0.875rem;
    }
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

/* Mobile table fixes */
@media (max-width: 640px) {
    :deep(.p-datatable .p-datatable-wrapper) {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    :deep(.p-button.p-button-icon-only) {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    :deep(.p-paginator .p-paginator-pages) {
        width: 100%;
    }
}
</style>