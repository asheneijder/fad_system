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
import usePermissions from '@/composables/usePermissions'; 
import Toast from "primevue/toast";
import Card from "primevue/card";
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
    models: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    categories: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', category_id: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Model Management' }];

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const categoryFilter = ref(props.filters?.category_id || "");
const models = ref(props.models);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const isEditMode = ref(false);
const selectedModelId = ref(null);
const viewModelData = ref(null);
const selectedModels = ref([]);
const actionMenu = ref();
const currentPerPage = ref(props.models?.per_page || 10);

// Forms
const modelForm = useForm({
    name: '',
    description: '',
    category_id: null,
    brand: '',
    model_number: '',
    specifications: {},
    warranty_period: 0,
    status: true,
    image: '',
    sort_order: 0,
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
            },
            // {
            //     label: 'Delete Selected',
            //     icon: 'pi pi-trash',
            //     command: () => bulkDelete()
            // }
        ]
    }
]);

// Status filter options
const statusOptions = ref([
    { label: 'All Status', value: '' },
    { label: 'Active', value: '1' },
    { label: 'Inactive', value: '0' },
]);

// Watchers
watch(() => props.models, (newModels) => {
    models.value = newModels;
    currentPerPage.value = newModels?.per_page || 10;
}, { immediate: true });

let debounceTimeout = null;

watch([search, statusFilter, categoryFilter], ([newSearch, newStatus, newCategory], [oldSearch, oldStatus, oldCategory]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newCategory !== oldCategory) {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            router.get(route("admin.models.index"), {
                search: newSearch,
                status: newStatus,
                category_id: newCategory,
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

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.models.index"), {
        search: search.value,
        status: statusFilter.value,
        category_id: categoryFilter.value,
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
    router.get(route("admin.models.index"), {
        search: search.value,
        status: statusFilter.value,
        category_id: categoryFilter.value,
        page: 1,
        per_page: newPerPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const resetForm = () => {
    modelForm.reset();
    isEditMode.value = false;
    selectedModelId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    modelForm.status = true;
    modelForm.warranty_period = 0;
    modelForm.sort_order = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (model) => {
    isEditMode.value = true;
    selectedModelId.value = model.id;
    
    modelForm.name = model.name;
    modelForm.description = model.description || '';
    modelForm.category_id = model.category_id || '';
    modelForm.brand = model.brand;
    modelForm.model_number = model.model_number || '';
    modelForm.specifications = model.specifications || {};
    modelForm.warranty_period = model.warranty_period || 0;
    modelForm.status = model.status;
    modelForm.image = model.image || '';
    modelForm.sort_order = model.sort_order || 0;
    
    showCreateEditDialog.value = true;
};

const viewModel = (model) => {
    viewModelData.value = model;
    showViewDialog.value = true;
};

const saveModel = () => {
    if (isEditMode.value) {
        modelForm.put(route('admin.models.update', selectedModelId.value), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Model updated successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) {
                    errorMessage = errors.name[0];
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
        modelForm.post(route('admin.models.store'), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Model created successfully',
                    life: 3000
                });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) {
                    errorMessage = errors.name[0];
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

const bulkUpdateStatus = (status) => {
    if (selectedModels.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select models first',
            life: 3000
        });
        return;
    }

    const form = useForm({
        model_ids: selectedModels.value.map(model => model.id),
        status: status
    });

    form.post(route('admin.models.bulk-update-status'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${selectedModels.value.length} model(s) ${status ? 'activated' : 'deactivated'}`,
                life: 3000
            });
            selectedModels.value = [];
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update models',
                life: 3000
            });
        }
    });
};

const bulkDelete = () => {
    if (selectedModels.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select models first',
            life: 3000
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete ${selectedModels.value.length} selected model(s)? This action cannot be undone.`,
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            const form = useForm({
                model_ids: selectedModels.value.map(model => model.id)
            });

            form.post(route('admin.models.bulk-delete'), {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: `${selectedModels.value.length} model(s) deleted successfully`,
                        life: 3000,
                    });
                    selectedModels.value = [];
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to delete models",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const exportSelected = () => {
    if (selectedModels.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select models to export',
            life: 3000
        });
        return;
    }

    const modelIds = selectedModels.value.map(model => model.id);
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.models.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    modelIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'model_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const deleteModel = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this model? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.models.destroy", id), {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Model deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete model",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getStatusSeverity = (model) => {
    return model.status ? 'success' : 'danger';
};

const getStatusText = (model) => {
    return model.status ? 'Active' : 'Inactive';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getCategoryName = (categoryId) => {
    if (!categoryId) return '—';
    const category = props.categories.find(c => c.id === categoryId);
    return category ? category.name : '—';
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};
</script>

<template>
    <Head title="Model Management" />
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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Model Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Manage product models and specifications</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" class="flex-1 min-w-fit text-xs sm:text-sm" />
                    <Button label="Create Model" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="flex-1 min-w-fit text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Models</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-briefcase"></i>
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
                                <p class="text-xs font-medium text-gray-500 truncate">Inactive</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.inactive }}</p>
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
                                <p class="text-xs font-medium text-gray-500 truncate">With Images</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.with_images }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-image"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Without Images</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.without_images }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-ban"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-teal-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Categories</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ categories.length }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-teal-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-teal-600 pi pi-tags"></i>
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

                            <div class="flex flex-col gap-2 w-full sm:w-auto sm:flex-row">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <Select v-model="categoryFilter" :options="categories" optionLabel="name" 
                                    optionValue="id" placeholder="Filter by Category" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <IconField iconPosition="left" class="flex-1 sm:flex-none">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search..." 
                                        class="w-full sm:w-64 md:w-80 text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>

                        <!-- Selected Models Info -->
                        <div v-if="selectedModels.length > 0" class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs sm:text-sm font-medium text-blue-800">
                                    {{ selectedModels.length }} model(s) selected
                                </span>
                                <Button label="Clear" icon="pi pi-times" severity="secondary" text size="small"
                                    @click="selectedModels = []" class="text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="models.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="models.per_page" 
                            :totalRecords="models.total"
                            :first="(models.current_page - 1) * models.per_page" 
                            @page="onPageChange"
                            v-model:selection="selectedModels" 
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50, 100, 500, 1000]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['name', 'brand', 'model_number']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-cube"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Models Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Try adjusting your search or create a new model.</p>
                                    <Button label="Create First Model" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(models.current_page - 1) * models.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="name" header="Model Name" sortable style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ slotProps.data.brand }} • {{ slotProps.data.model_number || 'No model #' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Category" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getCategoryName(slotProps.data.category_id)"
                                        severity="info" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="warranty_period" header="Warranty" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <span v-if="slotProps.data.warranty_period" class="font-semibold text-gray-900">
                                            {{ slotProps.data.warranty_period }}m
                                        </span>
                                        <Badge v-else value="—" severity="secondary" class="text-xs" />
                                    </div>
                                </template>
                            </Column>

                            <Column field="sort_order" header="Sort" sortable style="min-width: 70px;">
                                <template #body="slotProps">
                                    <div class="text-center">
                                        <Badge :value="slotProps.data.sort_order" severity="info" class="text-xs" />
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
                                            v-tooltip.top="'View'" @click="viewModel(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="usePermissions().hasPermission('can.delete.user')" icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" @click="deleteModel(slotProps.data.id)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ models.current_page }} of {{ Math.ceil(models.total / models.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Model Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Model' : 'Create New Model'" 
                :style="{ width: '95vw', maxWidth: '750px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Model Name -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Model Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="modelForm.name" 
                                placeholder="Enter model name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': modelForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.name">
                                {{ modelForm.errors.name }}
                            </small>
                        </div>

                        <!-- Brand -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Brand <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="modelForm.brand" 
                                placeholder="Enter brand name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': modelForm.errors.brand }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.brand">
                                {{ modelForm.errors.brand }}
                            </small>
                        </div>

                        <!-- Model Number -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Model Number</label>
                            <InputText v-model="modelForm.model_number" 
                                placeholder="Enter model number" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Category -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="modelForm.category_id" 
                                :options="categories" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Select category"
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': modelForm.errors.category_id }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.category_id">
                                {{ modelForm.errors.category_id }}
                            </small>
                        </div>

                        <!-- Warranty Period -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Warranty Period (months)</label>
                            <InputNumber v-model="modelForm.warranty_period" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Sort Order -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Sort Order</label>
                            <InputNumber v-model="modelForm.sort_order" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Image URL -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Image URL</label>
                            <InputText v-model="modelForm.image" 
                                placeholder="Enter image URL" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Description</label>
                            <Textarea v-model="modelForm.description" 
                                rows="3" 
                                placeholder="Enter model description..."
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox v-model="modelForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-xs sm:text-sm font-semibold text-gray-700">Active Model</label>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="modelForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update' : 'Create'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveModel"
                            :loading="modelForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>

            <!-- View Model Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Model Details" 
                :style="{ width: '95vw', maxWidth: '600px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                <div v-if="viewModelData" class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Model Name</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ viewModelData.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Brand</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewModelData.brand }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Model Number</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewModelData.model_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Category</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ getCategoryName(viewModelData.category_id) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Warranty Period</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ viewModelData.warranty_period ? viewModelData.warranty_period + ' months' : 'No warranty' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Sort Order</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">{{ viewModelData.sort_order }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewModelData)"
                                :severity="getStatusSeverity(viewModelData)"
                                class="mt-1 capitalize text-xs" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-gray-900">
                                {{ formatDate(viewModelData.created_at) }}
                            </p>
                        </div>
                        <div class="sm:col-span-2" v-if="viewModelData.description">
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ viewModelData.description }}</p>
                        </div>
                        <div class="sm:col-span-2" v-if="viewModelData.image">
                            <p class="text-xs sm:text-sm font-medium text-gray-500">Image URL</p>
                            <p class="mt-1 text-xs text-blue-600 break-all">{{ viewModelData.image }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" 
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Edit" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewModelData)" 
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

:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        font-size: 0.875rem;
    }
}

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