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
import usePermissions from '@/composables/usePermissions'; 
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Card from "primevue/card";
import DatePicker from 'primevue/datepicker';
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
    licenses: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'License Management' }];

const search = ref(props.filters?.search || "");
const licenses = ref(props.licenses);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const showQuantityDialog = ref(false);
const isEditMode = ref(false);
const selectedLicenseId = ref(null);
const viewLicenseData = ref(null);
const selectedLicenses = ref([]);
const actionMenu = ref();
const currentPerPage = ref(props.licenses?.per_page || 10);

// Forms
const licenseForm = useForm({
    license_name: '',
    product_key: '',
    expiration_date: null,
    licensed_email: '',
    licensed_name: '',
    manufacturer: '',
    min_qty: 1,
    total_qty: 1,
    available_qty: 0,
    status: true,
});

const quantityForm = useForm({
    available_qty: 0,
    operation: 'set',
    notes: '',
});

const bulkForm = useForm({
    license_ids: [],
    status: true,
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

// Statistics
const statistics = computed(() => {
    const data = licenses.value?.data || [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    return {
        total: licenses.value?.total || 0,
        active: data.filter(l => l.status).length,
        expired: data.filter(l => {
            if (!l.expiration_date) return false;
            const expDate = new Date(l.expiration_date);
            expDate.setHours(0, 0, 0, 0);
            return expDate < today;
        }).length,
        expiringSoon: data.filter(l => {
            if (!l.expiration_date) return false;
            const expDate = new Date(l.expiration_date);
            expDate.setHours(0, 0, 0, 0);
            const thirtyDaysFromNow = new Date();
            thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
            thirtyDaysFromNow.setHours(0, 0, 0, 0);
            return expDate > today && expDate <= thirtyDaysFromNow;
        }).length,
        lowStock: data.filter(l => l.available_qty <= l.min_qty && l.available_qty > 0).length,
        outOfStock: data.filter(l => l.available_qty === 0).length,
    };
});

// Watchers
watch(() => props.licenses, (newLicenses) => {
    licenses.value = newLicenses;
    currentPerPage.value = newLicenses?.per_page || 10;
}, { immediate: true });

let debounceTimeout = null;

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            router.get(route("admin.licenses.index"), {
                search: newSearch,
                page: 1,
                per_page: currentPerPage.value
            }, {
                preserveState: true,
                replace: true,
                preserveScroll: true
            });
        }, 300);
    }
});

watch(showCreateEditDialog, (val) => {
    if (!val) {
        resetForm();
    }
});

watch(showQuantityDialog, (val) => {
    if (!val) {
        quantityForm.reset();
        selectedLicenseId.value = null;
    }
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.licenses.index"), {
        search: search.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const onRowsPerPageChange = (newPerPage) => {
    currentPerPage.value = newPerPage;
    router.get(route("admin.licenses.index"), {
        search: search.value,
        page: 1,
        per_page: newPerPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const resetForm = () => {
    licenseForm.reset();
    isEditMode.value = false;
    selectedLicenseId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    licenseForm.status = true;
    licenseForm.min_qty = 1;
    licenseForm.total_qty = 1;
    licenseForm.available_qty = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (license) => {
    isEditMode.value = true;
    selectedLicenseId.value = license.id;
    
    licenseForm.license_name = license.license_name;
    licenseForm.product_key = license.product_key;
    licenseForm.expiration_date = license.expiration_date ? new Date(license.expiration_date) : null;
    licenseForm.licensed_email = license.licensed_email || '';
    licenseForm.licensed_name = license.licensed_name || '';
    licenseForm.manufacturer = license.manufacturer || '';
    licenseForm.min_qty = license.min_qty || 1;
    licenseForm.total_qty = license.total_qty || 1;
    licenseForm.available_qty = license.available_qty || 0;
    licenseForm.status = license.status;
    
    showCreateEditDialog.value = true;
};

const viewLicense = (license) => {
    viewLicenseData.value = license;
    showViewDialog.value = true;
};

const openQuantityDialog = (license) => {
    selectedLicenseId.value = license.id;
    quantityForm.available_qty = license.available_qty || 0;
    quantityForm.operation = 'set';
    quantityForm.notes = '';
    showQuantityDialog.value = true;
};

const generateProductKey = async () => {
    try {
        const response = await fetch(route('admin.licenses.generate-product-key'));
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        
        const data = await response.json();
        
        if (data.success) {
            licenseForm.product_key = data.product_key;
            toast.add({
                severity: 'success',
                summary: 'Generated',
                detail: 'New product key generated',
                life: 3000
            });
        } else {
            throw new Error(data.message || 'Failed to generate product key');
        }
    } catch (error) {
        console.error('Failed to generate product key:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to generate product key. Please try again.',
            life: 3000
        });
    }
};

const saveLicense = () => {
    const formData = {
        ...licenseForm,
        expiration_date: licenseForm.expiration_date ? licenseForm.expiration_date.toISOString().split('T')[0] : null
    };

    if (isEditMode.value) {
        licenseForm.put(route('admin.licenses.update', selectedLicenseId.value), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'License updated successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.product_key) {
                    errorMessage = 'Product key already exists';
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
        licenseForm.post(route('admin.licenses.store'), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'License created successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.product_key) {
                    errorMessage = 'Product key already exists';
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

const updateQuantity = () => {
    if (!selectedLicenseId.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No license selected',
            life: 3000
        });
        return;
    }

    quantityForm.post(route('admin.licenses.update-quantity', selectedLicenseId.value), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'License quantity updated successfully',
                life: 3000
            });
            showQuantityDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.available_qty?.[0] || 'Failed to update quantity',
                life: 3000
            });
        }
    });
};

const bulkUpdateStatus = (status) => {
    if (selectedLicenses.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select licenses first',
            life: 3000
        });
        return;
    }

    bulkForm.license_ids = selectedLicenses.value.map(license => license.id);
    bulkForm.status = status;

    bulkForm.post(route('admin.licenses.bulk-update-status'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${selectedLicenses.value.length} license(s) ${status ? 'activated' : 'deactivated'}`,
                life: 3000
            });
            selectedLicenses.value = [];
            bulkForm.reset();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update licenses',
                life: 3000
            });
        }
    });
};

const exportSelected = () => {
    if (selectedLicenses.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select licenses to export',
            life: 3000
        });
        return;
    }

    const licenseIds = selectedLicenses.value.map(license => license.id);
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.licenses.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    licenseIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'license_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const deleteLicense = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this license? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.licenses.destroy", id), {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "License deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete license",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getStatusSeverity = (license) => {
    if (!license.status) return 'danger';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (license.expiration_date) {
        const expDate = new Date(license.expiration_date);
        expDate.setHours(0, 0, 0, 0);
        
        if (expDate < today) return 'danger';
        
        const thirtyDaysFromNow = new Date();
        thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
        thirtyDaysFromNow.setHours(0, 0, 0, 0);
        
        if (expDate <= thirtyDaysFromNow) return 'warning';
    }
    
    if (license.available_qty === 0) return 'warning';
    
    return 'success';
};

const getStatusText = (license) => {
    if (!license.status) return 'Inactive';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (license.expiration_date) {
        const expDate = new Date(license.expiration_date);
        expDate.setHours(0, 0, 0, 0);
        
        if (expDate < today) return 'Expired';
        
        const thirtyDaysFromNow = new Date();
        thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
        thirtyDaysFromNow.setHours(0, 0, 0, 0);
        
        if (expDate <= thirtyDaysFromNow) return 'Expiring Soon';
    }
    
    if (license.available_qty === 0) return 'Out of Stock';
    
    return 'Active';
};

const getStockStatus = (license) => {
    if (license.available_qty === 0) return { text: 'Out of Stock', severity: 'danger' };
    if (license.available_qty <= license.min_qty) return { text: 'Low Stock', severity: 'warning' };
    return { text: 'In Stock', severity: 'success' };
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const daysUntilExpiry = (expirationDate) => {
    if (!expirationDate) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const expDate = new Date(expirationDate);
    expDate.setHours(0, 0, 0, 0);
    const diffTime = expDate - today;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};
</script>

<template>
    <Head title="License Management" />
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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">License Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Manage software licenses and subscriptions</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" class="flex-1 min-w-fit text-xs sm:text-sm" />
                    <Button label="Create License" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="flex-1 min-w-fit text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Licenses</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-key"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Active</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.active }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Expired</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.expired }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-red-600 pi pi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Expiring Soon</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.expiringSoon }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-yellow-600 pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Low Stock</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.lowStock }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-exclamation-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Out of Stock</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.outOfStock }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-times-circle"></i>
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
                                    <InputText v-model="search" placeholder="Search..." 
                                        class="w-full sm:w-64 md:w-80 text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>

                        <!-- Selected Licenses Info -->
                        <div v-if="selectedLicenses.length > 0" class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs sm:text-sm font-medium text-blue-800">
                                    {{ selectedLicenses.length }} license(s) selected
                                </span>
                                <Button label="Clear" icon="pi pi-times" severity="secondary" text size="small"
                                    @click="selectedLicenses = []" class="text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="licenses.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="licenses.per_page" 
                            :totalRecords="licenses.total"
                            :first="(licenses.current_page - 1) * licenses.per_page" 
                            @page="onPageChange"
                            v-model:selection="selectedLicenses" 
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['license_name', 'product_key', 'manufacturer']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-key"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Licenses Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Try adjusting your search or create a new license.</p>
                                    <Button label="Create First License" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(licenses.current_page - 1) * licenses.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="license_name" header="License Name" sortable style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.license_name }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ slotProps.data.manufacturer || 'No manufacturer' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="product_key" header="Product Key" sortable style="min-width: 140px;">
                                <template #body="slotProps">
                                    <code class="px-1 sm:px-2 py-1 text-xs font-mono bg-gray-100 rounded break-all">
                                        {{ slotProps.data.product_key.substring(0, 12) }}...
                                    </code>
                                </template>
                            </Column>

                            <Column header="Expiration" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ formatDate(slotProps.data.expiration_date) }}
                                        </div>
                                        <div v-if="daysUntilExpiry(slotProps.data.expiration_date) !== null" 
                                            class="text-xs font-medium" 
                                            :class="{
                                                'text-red-600': daysUntilExpiry(slotProps.data.expiration_date) < 0,
                                                'text-yellow-600': daysUntilExpiry(slotProps.data.expiration_date) >= 0 && daysUntilExpiry(slotProps.data.expiration_date) <= 30,
                                                'text-green-600': daysUntilExpiry(slotProps.data.expiration_date) > 30
                                            }">
                                            {{ daysUntilExpiry(slotProps.data.expiration_date) }}d
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Qty" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.available_qty }}/{{ slotProps.data.total_qty }}
                                        </div>
                                        <Badge :value="getStockStatus(slotProps.data).text"
                                            :severity="getStockStatus(slotProps.data).severity"
                                            class="text-xs mt-1" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getStatusText(slotProps.data)"
                                        :severity="getStatusSeverity(slotProps.data)"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 140px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View'" @click="viewLicense(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-box" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Quantity'" @click="openQuantityDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="usePermissions().hasPermission('can.delete.user')" icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" @click="deleteLicense(slotProps.data.id)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ licenses.current_page }} of {{ Math.ceil(licenses.total / licenses.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit License Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit License' : 'Create New License'" 
                :style="{ width: '95vw', maxWidth: '750px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- License Name -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                License Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="licenseForm.license_name" 
                                placeholder="Enter license name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': licenseForm.errors.license_name }" />
                            <small class="text-red-500 text-xs" v-if="licenseForm.errors.license_name">
                                {{ licenseForm.errors.license_name }}
                            </small>
                        </div>

                        <!-- Product Key -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Product Key <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-1 sm:gap-2">
                                <InputText v-model="licenseForm.product_key" 
                                    placeholder="Enter product key" 
                                    class="flex-1 text-xs sm:text-sm"
                                    :class="{ 'p-invalid': licenseForm.errors.product_key }" />
                                <Button icon="pi pi-refresh" severity="secondary" size="small"
                                    @click="generateProductKey"
                                    v-tooltip="'Generate'" class="px-2 sm:px-3" />
                            </div>
                            <small class="text-red-500 text-xs" v-if="licenseForm.errors.product_key">
                                {{ licenseForm.errors.product_key }}
                            </small>
                        </div>

                        <!-- Expiration Date -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Expiration Date <span class="text-red-500">*</span>
                            </label>
                            <DatePicker v-model="licenseForm.expiration_date" 
                                dateFormat="yy-mm-dd" 
                                placeholder="Select date"
                                class="w-full text-xs sm:text-sm"
                                :minDate="new Date()"
                                showIcon
                                :class="{ 'p-invalid': licenseForm.errors.expiration_date }" />
                            <small class="text-red-500 text-xs" v-if="licenseForm.errors.expiration_date">
                                {{ licenseForm.errors.expiration_date }}
                            </small>
                        </div>

                        <!-- Manufacturer -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Manufacturer</label>
                            <InputText v-model="licenseForm.manufacturer" 
                                placeholder="Enter manufacturer" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Licensed Email -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Licensed Email</label>
                            <InputText v-model="licenseForm.licensed_email" 
                                placeholder="Enter licensed email" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Licensed Name -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Licensed To</label>
                            <InputText v-model="licenseForm.licensed_name" 
                                placeholder="Enter licensed name" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Minimum Quantity -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Minimum Quantity</label>
                            <InputNumber v-model="licenseForm.min_qty" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Total Quantity -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Total Quantity</label>
                            <InputNumber v-model="licenseForm.total_qty" 
                                :min="1" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Available Quantity -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Available Quantity</label>
                            <InputNumber v-model="licenseForm.available_qty" 
                                :min="0" 
                                :max="licenseForm.total_qty"
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox v-model="licenseForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-xs sm:text-sm font-semibold text-gray-700">Active License</label>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="licenseForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update' : 'Create'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveLicense"
                            :loading="licenseForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- View License Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="License Details" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                <div v-if="viewLicenseData" class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">License Name</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ viewLicenseData.license_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Product Key</p>
                            <code class="mt-1 text-xs font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded block break-all">
                                {{ viewLicenseData.product_key }}
                            </code>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Manufacturer</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewLicenseData.manufacturer || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Expiration Date</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatDate(viewLicenseData.expiration_date) }}
                            </p>
                            <p class="text-xs font-medium" :class="{
                                'text-red-600': daysUntilExpiry(viewLicenseData.expiration_date) < 0,
                                'text-yellow-600': daysUntilExpiry(viewLicenseData.expiration_date) >= 0 && daysUntilExpiry(viewLicenseData.expiration_date) <= 30,
                                'text-green-600': daysUntilExpiry(viewLicenseData.expiration_date) > 30
                            }">
                                {{ daysUntilExpiry(viewLicenseData.expiration_date) }} days
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Licensed Email</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900 break-all">
                                {{ viewLicenseData.licensed_email || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Licensed To</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewLicenseData.licensed_name || '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Quantity</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewLicenseData.available_qty }} / {{ viewLicenseData.total_qty }} available
                            </p>
                            <Badge :value="getStockStatus(viewLicenseData).text"
                                :severity="getStockStatus(viewLicenseData).severity"
                                class="mt-1 text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewLicenseData)"
                                :severity="getStatusSeverity(viewLicenseData)"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Minimum Quantity</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewLicenseData.min_qty }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatDate(viewLicenseData.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Edit" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewLicenseData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Quantity" icon="pi pi-box" severity="help" 
                            @click="showViewDialog = false; openQuantityDialog(viewLicenseData)" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- Quantity Management Dialog -->
            <Dialog v-model:visible="showQuantityDialog" modal header="Manage License Quantity" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Operation</label>
                        <Select v-model="quantityForm.operation" :options="[
                            { label: 'Set to specific quantity', value: 'set' },
                            { label: 'Add to current quantity', value: 'add' },
                            { label: 'Subtract from current quantity', value: 'subtract' }
                        ]" optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="quantityForm.available_qty" 
                            :min="0" 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': quantityForm.errors.available_qty }" />
                        <small class="text-red-500 text-xs" v-if="quantityForm.errors.available_qty">
                            {{ quantityForm.errors.available_qty }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes (Optional)</label>
                        <Textarea v-model="quantityForm.notes" rows="3" placeholder="Add notes..."
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showQuantityDialog = false"
                            :disabled="quantityForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Update" icon="pi pi-check" severity="success" 
                            @click="updateQuantity" :loading="quantityForm.processing"
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