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
import Menu from "primevue/menu";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import usePermissions from '@/composables/usePermissions'; 
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
        default: () => ({ search: '', status: '', category: '', location: '' })
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
    locations: {
        type: Array,
        default: () => []
    },
    secondaryLocations: {
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
const locationFilter = ref(props.filters?.location || "");
const secondaryLocationFilter = ref(props.filters?.location_2 || "");
const assets = ref(props.assets);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const showAssignDialog = ref(false);
const showBulkAssignDialog = ref(false);
const showReturnDialog = ref(false);
const showSightingDialog = ref(false);
const isEditMode = ref(false);
const selectedAssetId = ref(null);
const viewAssetData = ref(null);
const selectedAssets = ref([]);
const actionMenu = ref();
const currentPerPage = ref(props.assets?.per_page || 10);

// Forms
const assetForm = useForm({
    asset_name: '',
    asset_tag_no: '',
    serial_no: '',
    model_type_id: null,
    category_type_id: null,
    status: 'active',
    qty: 1,
    location: '',
    location_2: '',
    purchase_date: null,
    purchase_cost: 0,
    current_value: 0,
    estimated_life: 0,
    estimated_life_days: 0,
    fully_depreciated_date: null,
    depreciation_cost: 0,
    last_sighting_date: null,
    notes: '',
    image: '',
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
    status: 'active',
});

const returnForm = useForm({
    asset_id: null,
    condition_returned: '',
    notes: '',
});

const sightingForm = useForm({
    asset_id: null,
    last_sighting_date: new Date(),
    notes: '',
});

// Status options
const statusOptions = ref([
    { label: 'All Status', value: '' },
    { label: 'Active', value: 'active' },
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

// Location options
const locationOptions = computed(() => {
    return [
        { name: 'All Locations', id: '' },
        ...props.locations.map(loc => ({ name: loc, id: loc }))
    ];
});

// Secondary Location options
const secondaryLocationOptions = computed(() => {
    return [
        { name: 'All Secondary Locations', id: '' },
        ...props.secondaryLocations.map(loc => ({ name: loc, id: loc }))
    ];
});

// Action menu items
const actionItems = ref([
    {
        label: 'Bulk Actions',
        items: [
            // {
            //     label: 'Bulk Assign',
            //     icon: 'pi pi-user-plus',
            //     command: () => openBulkAssignDialog()
            // },
            // {
            //     label: 'Set Active',
            //     icon: 'pi pi-check',
            //     command: () => bulkUpdateStatus('active')
            // },
            // {
            //     label: 'Set Available',
            //     icon: 'pi pi-check-circle',
            //     command: () => bulkUpdateStatus('available')
            // },
            {
                label: 'Set Maintenance',
                icon: 'pi pi-wrench',
                command: () => bulkUpdateStatus('maintenance')
            },
            // {
            //     label: 'Set Retired',
            //     icon: 'pi pi-times',
            //     command: () => bulkUpdateStatus('retired')
            // },
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

watch([search, statusFilter, categoryFilter, locationFilter], ([newSearch, newStatus, newCategory, newLocation], [oldSearch, oldStatus, oldCategory, oldLocation]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newCategory !== oldCategory || newLocation !== oldLocation || newSecondaryLocation !== oldSecondaryLocation) {
        router.get(route("admin.assets.index"), {
            search: newSearch,
            status: newStatus,
            category: newCategory,
            location: newLocation,
            location_2: newSecondaryLocation,
            page: 1,
            per_page: currentPerPage.value
        }, {
            preserveState: false,
            replace: true,
            preserveScroll: true
        });
    }
}, 300);

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

watch(showReturnDialog, (val) => {
    if (!val) returnForm.reset();
});

watch(showSightingDialog, (val) => {
    if (!val) sightingForm.reset();
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
        location: locationFilter.value,
        location_2: secondaryLocationFilter.value,
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
    assetForm.status = 'active';
    assetForm.qty = 1;
    assetForm.purchase_cost = 0;
    assetForm.current_value = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (asset) => {
    isEditMode.value = true;
    selectedAssetId.value = asset.id;
    
    assetForm.asset_name = asset.asset_name;
    assetForm.asset_tag_no = asset.asset_tag_no;
    assetForm.serial_no = asset.serial_no || '';
    assetForm.model_type_id = asset.model_type_id;
    assetForm.category_type_id = asset.category_type_id;
    assetForm.status = asset.status;
    assetForm.qty = asset.qty;
    assetForm.location = asset.location || '';
    assetForm.location_2 = asset.location_2 || '';
    assetForm.purchase_date = asset.purchase_date ? new Date(asset.purchase_date) : null;
    assetForm.purchase_cost = asset.purchase_cost || 0;
    assetForm.current_value = asset.current_value || 0;
    assetForm.estimated_life = asset.estimated_life || 0;
    assetForm.estimated_life_days = asset.estimated_life_days || 0;
    assetForm.fully_depreciated_date = asset.fully_depreciated_date ? new Date(asset.fully_depreciated_date) : null;
    assetForm.depreciation_cost = asset.depreciation_cost || 0;
    assetForm.last_sighting_date = asset.last_sighting_date ? new Date(asset.last_sighting_date) : null;
    assetForm.notes = asset.notes || '';
    assetForm.image = asset.image || '';
    
    showCreateEditDialog.value = true;
};

const openReturnDialog = (asset) => {
    returnForm.reset();
    returnForm.asset_id = asset.id;
    showReturnDialog.value = true;
};

const viewAsset = (asset) => {
    viewAssetData.value = asset;
    showViewDialog.value = true;
};

const openAssignDialog = (asset) => {
    assignForm.reset();
    assignForm.asset_id = asset.id;
    selectedAssetId.value = asset.id;
    showAssignDialog.value = true;
};

// const openBulkAssignDialog = () => {
//     if (selectedAssets.value.length === 0) {
//         toast.add({
//             severity: 'warn',
//             summary: 'No Selection',
//             detail: 'Please select assets first',
//             life: 3000
//         });
//         return;
//     }
    
//     bulkAssignForm.reset();
//     bulkAssignForm.asset_ids = selectedAssets.value.map(asset => asset.id);
//     showBulkAssignDialog.value = true;
// };

// const openBulkSightingDialog = () => {
//     if (selectedAssets.value.length === 0) {
//         toast.add({
//             severity: 'warn',
//             summary: 'No Selection',
//             detail: 'Please select assets first',
//             life: 3000
//         });
//         return;
//     }
    
//     selectedAssets.value.forEach(asset => {
//         router.post(route('admin.assets.update-sighting', asset.id), {
//             last_sighting_date: new Date().toISOString().split('T')[0],
//             notes: 'Bulk sighting update'
//         }, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 toast.add({ 
//                     severity: 'success', 
//                     summary: 'Success', 
//                     detail: 'Sighting updated for selected assets', 
//                     life: 3000 
//                 });
//             }
//         });
//     });
// };

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
                if (errors.asset_tag_no) errorMessage = 'Asset tag already exists';
                else if (errors.serial_no) errorMessage = 'Serial number already exists';
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
                if (errors.asset_tag_no) errorMessage = 'Asset tag already exists';
                else if (errors.serial_no) errorMessage = 'Serial number already exists';
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
            assignForm.reset();
            selectedAssetId.value = null;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to assign asset', life: 3000 });
        }
    });
};

// const submitBulkAssignment = () => {
//     bulkAssignForm.post(route('admin.assets.bulk-assign'), {
//         preserveScroll: true,
//         onSuccess: (response) => {
//             showBulkAssignDialog.value = false;
//             selectedAssets.value = [];
//             toast.add({ severity: 'success', summary: 'Success', detail: response.props.success_count + ' asset(s) assigned', life: 5000 });
            
//             if (response.props.failed_assets?.length > 0) {
//                 toast.add({ severity: 'warn', summary: 'Partial Success', detail: 'Some assets could not be assigned', life: 6000 });
//             }
//         },
//         onError: () => {
//             toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to assign assets', life: 3000 });
//         }
//     });
// };

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

const submitReturn = () => {
    returnForm.post(route('admin.assets.return', returnForm.asset_id), {
        preserveScroll: true,
        onSuccess: () => {
            showReturnDialog.value = false;
            toast.add({ 
                severity: 'success', 
                summary: 'Success', 
                detail: 'Asset returned successfully', 
                life: 3000 
            });
        },
        onError: (errors) => {
            let errorMessage = 'Failed to return asset';
            if (errors.condition_returned) {
                errorMessage = errors.condition_returned;
            }
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: errorMessage, 
                life: 3000 
            });
        }
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

const exportSelected = async () => {
    if (selectedAssets.value.length === 0) {
        toast.add({ 
            severity: 'warn', 
            summary: 'No Selection', 
            detail: 'Please select assets to export', 
            life: 3000 
        });
        return;
    }

    const assetIds = selectedAssets.value.map(asset => asset.id);
    
    try {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('admin.assets.export');
        
        // Safely get CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfMeta.getAttribute('content');
            form.appendChild(csrfInput);
        } else {
            console.warn('CSRF token meta tag not found');
        }
        
        // Add asset IDs
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
        
    } catch (error) {
        console.error('Export error:', error);
        toast.add({ 
            severity: 'error', 
            summary: 'Export Failed', 
            detail: 'There was an error exporting assets', 
            life: 3000 
        });
    }
};

const getStatusSeverity = (status) => {
    const map = { 
        active: 'success', 
        available: 'success', 
        assigned: 'info', 
        maintenance: 'warn', 
        retired: 'danger' 
    };
    return map[status] || 'secondary';
};

const getStatusText = (status) => status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';

const formatDate = (date) => {
    if (!date) return '—';
    try {
        return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return '—';
    }
};

const formatCurrency = (amount) => {
    if (!amount) return 'RM 0.00';
    return new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(amount);
};

const getWarrantyStatus = (asset) => {
    if (!asset.model?.warranty_period || !asset.purchase_date) {
        return { text: 'No Warranty', severity: 'secondary' };
    }
    
    const purchaseDate = new Date(asset.purchase_date);
    const warrantyExpiry = new Date(purchaseDate);
    warrantyExpiry.setMonth(purchaseDate.getMonth() + asset.model.warranty_period);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    warrantyExpiry.setHours(0, 0, 0, 0);
    
    const daysUntilExpiry = Math.ceil((warrantyExpiry - today) / (1000 * 60 * 60 * 24));
    
    if (daysUntilExpiry < 0) return { text: 'Expired', severity: 'danger' };
    if (daysUntilExpiry <= 30) return { text: 'Expiring Soon', severity: 'warn' };
    return { text: 'Active', severity: 'success' };
};

const getSightingStatus = (asset) => {
    if (!asset.last_sighting_date) {
        return { text: 'Never Sighted', severity: 'danger' };
    }
    
    const lastSighting = new Date(asset.last_sighting_date);
    const today = new Date();
    const daysSinceSighting = Math.ceil((today - lastSighting) / (1000 * 60 * 60 * 24));
    
    if (daysSinceSighting > 365) return { text: 'Overdue', severity: 'danger' };
    if (daysSinceSighting > 180) return { text: 'Due Soon', severity: 'warn' };
    return { text: 'Current', severity: 'success' };
};

const toggleActionMenu = (event) => actionMenu.value.toggle(event);

const viewAssignmentHistory = (asset) => router.visit(route('admin.assets.assignment-history', asset.id));

// Check if asset can be assigned
const canAssign = (asset) => {
    return asset && (asset.status === 'available' || asset.status === 'active');
};

// Check if asset can be returned
const canReturn = (asset) => {
    return asset && asset.status === 'assigned';
};
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
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md hover:shadow-lg transition-shadow">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total</p>
                                <p class="mt-1 text-lg sm:text-xl font-bold text-gray-900">{{ statistics.total || 0 }}</p>
                            </div>
                            <div class="p-2 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg text-blue-600 pi pi-box"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md hover:shadow-lg transition-shadow">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Active</p>
                                <p class="mt-1 text-lg sm:text-xl font-bold text-gray-900">{{ statistics.active || 0 }}</p>
                            </div>
                            <div class="p-2 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md hover:shadow-lg transition-shadow">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Assigned</p>
                                <p class="mt-1 text-lg sm:text-xl font-bold text-gray-900">{{ statistics.assigned || 0 }}</p>
                            </div>
                            <div class="p-2 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg text-orange-600 pi pi-user"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md hover:shadow-lg transition-shadow">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Maintenance</p>
                                <p class="mt-1 text-lg sm:text-xl font-bold text-gray-900">{{ statistics.maintenance || 0 }}</p>
                            </div>
                            <div class="p-2 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg text-yellow-600 pi pi-wrench"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md hover:shadow-lg transition-shadow">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Retired</p>
                                <p class="mt-1 text-lg sm:text-xl font-bold text-gray-900">{{ statistics.retired || 0 }}</p>
                            </div>
                            <div class="p-2 bg-red-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg text-red-600 pi pi-times-circle"></i>
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
                        <div class="flex flex-col lg:flex-row justify-between items-start gap-3">
                            <div class="flex flex-wrap gap-2 w-full lg:w-auto">
                                <Button label="Create" icon="pi pi-plus" severity="success"
                                    @click="openCreateDialog" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <Button label="Actions" icon="pi pi-cog" severity="secondary" outlined
                                    @click="toggleActionMenu" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="All Status" 
                                    class="w-full sm:w-40 text-xs sm:text-sm" />
                                <Select v-model="categoryFilter" :options="categoryOptions" optionLabel="name" 
                                    optionValue="id" placeholder="All Categories" 
                                    class="w-full sm:w-40 text-xs sm:text-sm" />
                                <Select v-model="locationFilter" :options="locationOptions" optionLabel="name" 
                                    optionValue="id" placeholder="All Locations" 
                                    class="w-full sm:w-40 text-xs sm:text-sm" />
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
                    <div class="mt-4 sm:mt-6 overflow-x-auto">
                        <DataTable :value="assets.data" showGridlines stripedRows :rowHover="true" paginator 
                            :rows="assets.per_page" :totalRecords="assets.total"
                            :first="(assets.current_page - 1) * assets.per_page" @page="onPageChange"
                            v-model:selection="selectedAssets" dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50, 100, 500, 1000]"
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
                            <Column selectionMode="multiple" headerStyle="width: 3rem" class="select-column" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(assets.current_page - 1) * assets.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="asset_name" header="Asset Details" sortable style="min-width: 220px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm">{{ slotProps.data.asset_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ slotProps.data.asset_tag_no }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ slotProps.data.model?.brand }} {{ slotProps.data.model?.name }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Category" sortable style="min-width: 130px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900">
                                        {{ slotProps.data.category?.name || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable field="status" style="min-width: 110px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data.status)"
                                        :severity="getStatusSeverity(slotProps.data.status)"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <Column header="Location" sortable style="min-width: 140px;">
                                <template #body="slotProps">
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-900">{{ slotProps.data.location }}</div>
                                        <div v-if="slotProps.data.location_2" class="text-gray-500 mt-1">{{ slotProps.data.location_2 }}</div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Assigned To" sortable style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.user" class="text-xs">
                                        <div class="font-medium text-gray-900">{{ slotProps.data.user.name }}</div>
                                        <div class="text-gray-500 mt-1">{{ formatDate(slotProps.data.assigned_at) }}</div>
                                    </div>
                                    <Badge v-else value="Not Assigned" severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column header="Purchase Cost" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-900">{{ formatCurrency(slotProps.data.purchase_cost) }}</div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 200px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View'" @click="viewAsset(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-history" outlined rounded severity="secondary" size="small"
                                            v-tooltip.top="'Assignment History'" 
                                            @click="viewAssignmentHistory(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="canAssign(slotProps.data)" 
                                            icon="pi pi-user-plus" outlined rounded severity="success" size="small"
                                            v-tooltip.top="'Assign to User'" 
                                            @click="openAssignDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="canReturn(slotProps.data)"
                                            icon="pi pi-arrow-left" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Return Asset'" 
                                            @click="openReturnDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="usePermissions().hasPermission('can.delete.user')" icon="pi pi-trash" outlined rounded severity="danger" size="small"
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

           <!-- Assign Asset Dialog -->
            <Dialog v-model:visible="showAssignDialog" modal header="Assign Asset" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Information Section -->
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="pi pi-info-circle text-blue-600 mt-0.5"></i>
                            <div>
                                <p class="text-xs sm:text-sm text-blue-800 font-medium mb-1">
                                    Assign asset to user
                                </p>
                                <p class="text-xs text-blue-700">
                                    User will receive an email notification and must acknowledge the asset assignment terms.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- User Selection -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            User <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="assignForm.user_id" 
                            :options="users" 
                            optionLabel="name" 
                            optionValue="id" 
                            filter
                            :filterFields="['name', 'email']"
                            placeholder="Select or search user..." 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assignForm.errors.user_id }">
                            <template #option="slotProps">
                                <div class="flex flex-col">
                                    <span class="font-medium text-sm">{{ slotProps.option.name }}</span>
                                    <span class="text-xs text-gray-500">{{ slotProps.option.email }}</span>
                                </div>
                            </template>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <span class="text-sm">{{ users.find(u => u.id === slotProps.value)?.name }}</span>
                                </div>
                                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                            </template>
                        </Select>
                        <small class="text-red-500 text-xs" v-if="assignForm.errors.user_id">
                            {{ assignForm.errors.user_id }}
                        </small>
                    </div>

                    <!-- Condition Assigned -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Condition Assigned <span class="text-red-500">*</span>
                        </label>
                        <InputText v-model="assignForm.condition_assigned" 
                            placeholder="Describe the current condition of the asset"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assignForm.errors.condition_assigned }" />
                        <small class="text-red-500 text-xs" v-if="assignForm.errors.condition_assigned">
                            {{ assignForm.errors.condition_assigned }}
                        </small>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <Textarea v-model="assignForm.notes" 
                            placeholder="Additional notes (optional)"
                            rows="3"
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <!-- Acknowledgment Notice -->
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="pi pi-envelope text-green-600 mt-0.5"></i>
                            <div>
                                <p class="text-xs sm:text-sm text-green-800 font-medium mb-1">
                                    User Acknowledgment Required
                                </p>
                                <p class="text-xs text-green-700">
                                    Selected user will receive an email with assignment details and must acknowledge 
                                    the terms and conditions before the assignment is complete.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined @click="showAssignDialog = false"
                            :disabled="assignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Assign Asset" icon="pi pi-user-plus" severity="success" 
                            @click="submitAssignment" :loading="assignForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- View Dialog - Keeping original implementation -->
            <Dialog v-model:visible="showViewDialog" modal header="Asset Details" 
                :style="{ width: '95vw', maxWidth: '700px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                <div v-if="viewAssetData" class="space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Asset Name</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900 break-words">{{ viewAssetData.asset_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Asset Tag</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">{{ viewAssetData.asset_tag_no }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Serial Number</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900 break-all">
                                {{ viewAssetData.serial_no || '—' }}
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
                                {{ viewAssetData.category?.name || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewAssetData.status)"
                                :severity="getStatusSeverity(viewAssetData.status)"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Quantity</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">{{ viewAssetData.qty }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Location</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">{{ viewAssetData.location }}</p>
                            <p v-if="viewAssetData.location_2" class="text-xs text-gray-500">{{ viewAssetData.location_2 }}</p>
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
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Current Value</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ formatCurrency(viewAssetData.current_value) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Last Sighting</p>
                            <p class="mt-1 text-xs sm:text-base font-semibold text-gray-900">
                                {{ formatDate(viewAssetData.last_sighting_date) }}
                            </p>
                            <Badge :value="getSightingStatus(viewAssetData).text"
                                :severity="getSightingStatus(viewAssetData).severity"
                                class="mt-1 text-xs" />
                        </div>
                    </div>

                    <div v-if="viewAssetData.notes">
                        <p class="text-xs sm:text-sm font-medium text-gray-500">Notes</p>
                        <p class="mt-1 text-xs sm:text-base text-gray-900">{{ viewAssetData.notes }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Edit" icon="pi pi-pencil" severity="warn" 
                            @click="showViewDialog = false; openEditDialog(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button v-if="canAssign(viewAssetData)" 
                            label="Assign" icon="pi pi-user-plus" severity="success" 
                            @click="showViewDialog = false; openAssignDialog(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button v-if="canReturn(viewAssetData)"
                            label="Return" icon="pi pi-arrow-left" severity="warn" 
                            @click="showViewDialog = false; openReturnDialog(viewAssetData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Create/Edit Asset Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Asset' : 'Create New Asset'" 
                :style="{ width: '95vw', maxWidth: '800px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Asset Name -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Asset Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.asset_name" 
                                placeholder="Enter asset name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.asset_name }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.asset_name">
                                {{ assetForm.errors.asset_name }}
                            </small>
                        </div>

                        <!-- Asset Tag -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Asset Tag <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="assetForm.asset_tag_no" 
                                placeholder="Enter asset tag" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.asset_tag_no }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.asset_tag_no">
                                {{ assetForm.errors.asset_tag_no }}
                            </small>
                        </div>

                        <!-- Serial Number -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Serial Number</label>
                            <InputText v-model="assetForm.serial_no" 
                                placeholder="Enter serial number" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.serial_no }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.serial_no">
                                {{ assetForm.errors.serial_no }}
                            </small>
                        </div>

                        <!-- Model -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Model <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="assetForm.model_type_id"
                                :options="models"
                                optionLabel="name"
                                optionValue="id"
                                filter
                                :filterFields="['name', 'brand', 'category']"
                                placeholder="Select or search model..."
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.model_type_id }"
                            >
                                <!-- Custom Option Template -->
                                <template #option="slotProps">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-sm">{{ slotProps.option.name }}</span>
                                        <span class="text-xs text-gray-500">
                                            {{ slotProps.option.brand ?? 'No brand info' }}
                                        </span>
                                    </div>
                                </template>

                                <!-- Custom Value Template -->
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <span class="text-sm">
                                            {{ models.find(m => m.id === slotProps.value)?.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                                </template>
                            </Select>

                            <small class="text-red-500 text-xs" v-if="assetForm.errors.model_type_id">
                                {{ assetForm.errors.model_type_id }}
                            </small>
                        </div>

                        <!-- Category -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="assetForm.category_type_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                filter
                                :filterFields="['name', 'description']"
                                placeholder="Select or search category..."
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.category_type_id }"
                            >
                                <!-- Custom Option Template -->
                                <template #option="slotProps">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-sm">{{ slotProps.option.name }}</span>
                                        <span class="text-xs text-gray-500">
                                            {{ slotProps.option.description ?? 'No description' }}
                                        </span>
                                    </div>
                                </template>

                                <!-- Custom Value Template -->
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <span class="text-sm">
                                            {{ categories.find(c => c.id === slotProps.value)?.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                                </template>
                            </Select>

                            <small class="text-red-500 text-xs" v-if="assetForm.errors.category_type_id">
                                {{ assetForm.errors.category_type_id }}
                            </small>
                        </div>

                        <!-- Status -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="assetForm.status" :options="[
                                { label: 'Active', value: 'active' },
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

                        <!-- Quantity -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <InputNumber v-model="assetForm.qty" 
                                :min="1" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': assetForm.errors.qty }" />
                            <small class="text-red-500 text-xs" v-if="assetForm.errors.qty">
                                {{ assetForm.errors.qty }}
                            </small>
                        </div>

                        <!-- Location -->
                        <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <Select
                            v-model="assetForm.location"
                            :options="locations"
                            placeholder="Select or search location..."
                            filter
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': assetForm.errors.location }"
                        >
                            <template #option="slotProps">
                                <span class="text-sm">{{ slotProps.option }}</span>
                            </template>

                            <template #value="slotProps">
                                <span class="text-sm">{{ slotProps.value || slotProps.placeholder }}</span>
                            </template>
                        </Select>

                        <small class="text-red-500 text-xs" v-if="assetForm.errors.location">
                            {{ assetForm.errors.location }}
                        </small>
                        </div>

                       <!-- Secondary Location -->
                        <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                            Secondary Location
                        </label>
                        <Select
                            v-model="assetForm.location_2"
                            :options="secondaryLocations"
                            placeholder="Select or search secondary location..."
                            filter
                            class="w-full text-xs sm:text-sm"
                        >
                            <template #option="slotProps">
                                <span class="text-sm">{{ slotProps.option }}</span>
                            </template>

                            <template #value="slotProps">
                                <span class="text-sm">{{ slotProps.value || slotProps.placeholder }}</span>
                            </template>
                        </Select>
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
                                currency="MYR" 
                                locale="ms-MY"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Current Value -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Current Value</label>
                            <InputNumber v-model="assetForm.current_value" 
                                :min="0" 
                                mode="currency" 
                                currency="MYR" 
                                locale="ms-MY"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Estimated Life (Years) -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Estimated Life (Years)</label>
                            <InputNumber v-model="assetForm.estimated_life" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Estimated Life (Days) -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Estimated Life (Days)</label>
                            <InputNumber v-model="assetForm.estimated_life_days" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Fully Depreciated Date -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Fully Depreciated Date</label>
                            <DatePicker v-model="assetForm.fully_depreciated_date" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full text-xs sm:text-sm"
                                showIcon />
                        </div>

                        <!-- Depreciation Cost -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Depreciation Cost</label>
                            <InputNumber v-model="assetForm.depreciation_cost" 
                                :min="0" 
                                mode="currency" 
                                currency="MYR" 
                                locale="ms-MY"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Last Sighting Date -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Last Sighting Date</label>
                            <DatePicker v-model="assetForm.last_sighting_date" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full text-xs sm:text-sm"
                                showIcon />
                        </div>

                        <!-- Image URL -->
                        <!-- <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Image URL</label>
                            <InputText v-model="assetForm.image" 
                                placeholder="Enter image URL" 
                                class="w-full text-xs sm:text-sm" />
                        </div> -->

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

            <!-- Bulk Assign Dialog -->
            <!-- <Dialog v-model:visible="showBulkAssignDialog" modal header="Bulk Assign Assets" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs sm:text-sm text-blue-800">
                            Assigning {{ selectedAssets.length }} asset(s) to user
                        </p>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            User <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="bulkAssignForm.user_id" 
                            :options="users" 
                            optionLabel="name" 
                            optionValue="id" 
                            filter
                            :filterFields="['name', 'email']"
                            placeholder="Select or search user..." 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': bulkAssignForm.errors.user_id }">
                            <template #option="slotProps">
                                <div class="flex flex-col">
                                    <span class="font-medium text-sm">{{ slotProps.option.name }}</span>
                                    <span class="text-xs text-gray-500">{{ slotProps.option.email }}</span>
                                </div>
                            </template>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <span class="text-sm">{{ users.find(u => u.id === slotProps.value)?.name }}</span>
                                </div>
                                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                            </template>
                        </Select>
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
            </Dialog> -->

            <!-- Return Asset Dialog -->
            <Dialog v-model:visible="showReturnDialog" modal header="Return Asset" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
        
                <div class="space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Asset Information -->
                    <div v-if="returnForm.asset_id" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="pi pi-box text-blue-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-gray-900 text-sm truncate">
                                    {{ assets.data.find(a => a.id === returnForm.asset_id)?.asset_name || 'Asset' }}
                                </div>
                                <div class="text-xs text-gray-600 truncate">
                                    {{ assets.data.find(a => a.id === returnForm.asset_id)?.asset_tag_no || '' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Currently assigned to: 
                                    <span class="font-medium">
                                        {{ assets.data.find(a => a.id === returnForm.asset_id)?.user?.name || 'User' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Condition Returned <span class="text-red-500">*</span>
                        </label>
                        <InputText v-model="returnForm.condition_returned" 
                            placeholder="Describe the condition when returned"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': returnForm.errors.condition_returned }" />
                        <small class="text-red-500 text-xs" v-if="returnForm.errors.condition_returned">
                            {{ returnForm.errors.condition_returned }}
                        </small>
                        <small class="text-gray-500 text-xs">
                            Describe the physical condition and any damages noted upon return
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Return Notes</label>
                        <Textarea v-model="returnForm.notes" 
                            placeholder="Additional notes about the return (optional)"
                            rows="3"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': returnForm.errors.notes }" />
                        <small class="text-red-500 text-xs" v-if="returnForm.errors.notes">
                            {{ returnForm.errors.notes }}
                        </small>
                        <small class="text-gray-500 text-xs">
                            Any additional information about the return process
                        </small>
                    </div>

                    <!-- Return Checklist -->
                    <div class="space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Return Checklist</label>
                        <div class="grid grid-cols-1 gap-2 text-xs">
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span>Asset is physically inspected</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span>All accessories are returned</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                <i class="pi pi-check-circle text-green-500"></i>
                                <span>Condition is properly documented</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showReturnDialog = false"
                            :disabled="returnForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Return Asset" 
                            icon="pi pi-arrow-left" 
                            severity="warn" 
                            @click="submitReturn" 
                            :loading="returnForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.action-btn {
    width: 2rem !important;
    height: 2rem !important;
    padding: 0 !important;
}

@media (min-width: 640px) {
    .action-btn {
        width: 2.25rem !important;
        height: 2.25rem !important;
    }
}

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
    padding: 0.75rem 0.5rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 0.875rem;
        padding: 1rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.5rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 1rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
    background: #e3f2fd !important;
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

:deep(.p-paginator) {
    flex-wrap: wrap;
    gap: 0.5rem;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    margin-top: 1rem;
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-paginator) {
        padding: 1rem;
    }
}

@media (max-width: 640px) {
    :deep(.p-datatable .p-datatable-wrapper) {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>