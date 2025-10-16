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
            {
                label: 'Delete Selected',
                icon: 'pi pi-trash',
                command: () => bulkDelete()
            }
        ]
    }
]);

// Watchers
watch(() => props.models, (newModels) => {
    models.value = newModels;
}, { immediate: true });

watch([search, statusFilter, categoryFilter], ([newSearch, newStatus, newCategory], [oldSearch, oldStatus, oldCategory]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus || newCategory !== oldCategory) {
        router.get(route("admin.models.index"), {
            search: newSearch,
            status: newStatus,
            category_id: newCategory    
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

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("admin.models.index"), {
        search: search.value,
        status: statusFilter.value,
        category_id: categoryFilter.value,
        page: page,
    }, {
        preserveState: true,
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
    
    // Create a temporary form to submit the export request
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
                preserveState: true,
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

const getCategoryTypeName = (categoryTypeId) => {
    if (!categoryTypeId) return '—';
    const categoryType = props.categories.find(ct => ct.id === categoryTypeId);
    return categoryType ? categoryType.name : '—';
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};

const addSpecification = () => {
    if (!modelForm.specifications) {
        modelForm.specifications = {};
    }
    // This would need a more complex implementation for dynamic key-value pairs
};

// Status filter options
const statusOptions = ref([
    { label: 'All Status', value: '' },
    { label: 'Active', value: '1' },
    { label: 'Inactive', value: '0' },
]);
</script>

<template>
    <Head title="Model Management" />
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
                    <h1 class="text-3xl font-bold text-gray-800">Model Management</h1>
                    <p class="mt-1 text-gray-500">Manage product models and specifications</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" />
                    <Button label="Create Model" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Models</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-cube"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.active }}</p>
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
                                <p class="text-sm font-medium text-gray-500">Inactive</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.inactive }}</p>
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
                                <p class="text-sm font-medium text-gray-500">With Images</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.with_images }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-image"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Without Images</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.without_images }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="text-xl text-orange-600 pi pi-ban"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-teal-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Categories</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ categories.length }}</p>
                            </div>
                            <div class="p-3 bg-teal-100 rounded-full">
                                <i class="text-xl text-teal-600 pi pi-tags"></i>
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
                            <Button label="Create Model" icon="pi pi-plus" severity="success"
                                @click="openCreateDialog" class="font-semibold" />
                            <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                                @click="toggleActionMenu" />
                        </div>

                        <div class="flex flex-col lg:flex-row gap-4">
                            <div class="w-full lg:w-48">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Status" class="w-full" />
                            </div>
                            <div class="w-full lg:w-48">
                                <Select v-model="categoryTypeFilter" :options="categories" optionLabel="name" 
                                    optionValue="id" placeholder="Filter by Category" class="w-full" />
                            </div>
                            <div class="w-full lg:w-80">
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search models..." class="w-full" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Models Info -->
                    <div v-if="selectedModels.length > 0" class="p-3 mt-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800">
                                {{ selectedModels.length }} model(s) selected
                            </span>
                            <Button label="Clear" icon="pi pi-times" severity="secondary" text
                                @click="selectedModels = []" />
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="models.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="models.per_page" :totalRecords="models.total"
                            :first="(models.current_page - 1) * models.per_page" @page="onPageChange"
                            v-model:selection="selectedModels" dataKey="id"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-cube"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Models Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your search or create a new model.</p>
                                    <Button label="Create First Model" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 3rem" />

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(models.current_page - 1) * models.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column field="name" header="Model Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ slotProps.data.brand }} • {{ slotProps.data.model_number || 'No model number' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Category" sortable style="width: 150px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ getCategoryTypeName(slotProps.data.category_type_id) }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="brand" header="Brand" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="slotProps.data.brand" severity="info" class="capitalize" />
                                </template>
                            </Column>

                            <Column field="warranty_period" header="Warranty" sortable style="width: 100px;">
                                <template #body="slotProps">
                                    <div class="text-center">
                                        <span v-if="slotProps.data.warranty_period" class="font-semibold text-gray-900">
                                            {{ slotProps.data.warranty_period }} months
                                        </span>
                                        <Badge v-else value="No" severity="secondary" />
                                    </div>
                                </template>
                            </Column>

                            <Column field="sort_order" header="Sort" sortable style="width: 80px;">
                                <template #body="slotProps">
                                    <div class="text-center">
                                        <Badge :value="slotProps.data.sort_order" severity="info" />
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

                            <Column header="Created" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 150px">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                            v-tooltip.top="'View Details'" @click="viewModel(slotProps.data)" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit Model'" @click="openEditDialog(slotProps.data)" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete Model'" @click="deleteModel(slotProps.data.id)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Model Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Model' : 'Create New Model'" 
                :style="{ width: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Model Name -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Model Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="modelForm.name" 
                                placeholder="Enter model name" 
                                class="w-full"
                                :class="{ 'p-invalid': modelForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.name">
                                {{ modelForm.errors.name }}
                            </small>
                        </div>

                        <!-- Brand -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Brand <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="modelForm.brand" 
                                placeholder="Enter brand name" 
                                class="w-full"
                                :class="{ 'p-invalid': modelForm.errors.brand }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.brand">
                                {{ modelForm.errors.brand }}
                            </small>
                        </div>

                        <!-- Model Number -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Model Number</label>
                            <InputText v-model="modelForm.model_number" 
                                placeholder="Enter model number" 
                                class="w-full" />
                        </div>

                        <!-- Category Type -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Category Type <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="modelForm.category_id" 
                                :options="categories" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Select category"
                                class="w-full"
                                :class="{ 'p-invalid': modelForm.errors.category_id }" />
                            <small class="text-red-500 text-xs" v-if="modelForm.errors.category_id">
                                {{ modelForm.errors.category_id }}
                            </small>
                        </div>

                        <!-- Warranty Period -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Warranty Period (months)</label>
                            <InputNumber v-model="modelForm.warranty_period" 
                                :min="0" 
                                class="w-full" />
                        </div>

                        <!-- Sort Order -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Sort Order</label>
                            <InputNumber v-model="modelForm.sort_order" 
                                :min="0" 
                                class="w-full" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2">
                            <Checkbox v-model="modelForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-sm font-semibold text-gray-700">Active Model</label>
                        </div>

                        <!-- Image URL -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Image URL</label>
                            <InputText v-model="modelForm.image" 
                                placeholder="Enter image URL" 
                                class="w-full" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Description</label>
                            <Textarea v-model="modelForm.description" 
                                rows="3" 
                                placeholder="Enter model description..."
                                class="w-full" />
                        </div>

                        <!-- Specifications -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Specifications</label>
                            <Textarea v-model="modelForm.specifications" 
                                rows="3" 
                                placeholder='Enter specifications as JSON (e.g., {"color": "black", "size": "large"})'
                                class="w-full" />
                            <small class="text-gray-500 text-xs">
                                Enter specifications as a JSON object with key-value pairs
                            </small>
                            <input type="hidden" v-model="modelForm.specifications" />
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="modelForm.processing" />
                        <Button :label="isEditMode ? 'Update Model' : 'Create Model'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveModel"
                            :loading="modelForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- View Model Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Model Details" :style="{ width: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div v-if="viewModelData" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Model Name</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewModelData.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Brand</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewModelData.brand }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Model Number</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewModelData.model_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Category Type</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ getCategoryTypeName(viewModelData.category_type_id) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Warranty Period</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ viewModelData.warranty_period ? viewModelData.warranty_period + ' months' : 'No warranty' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Sort Order</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewModelData.sort_order }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewModelData)"
                                :severity="getStatusSeverity(viewModelData)"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(viewModelData.created_at) }}
                            </p>
                        </div>
                        <div class="md:col-span-2" v-if="viewModelData.description">
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewModelData.description }}</p>
                        </div>
                        <div class="md:col-span-2" v-if="viewModelData.image">
                            <p class="text-sm font-medium text-gray-500">Image URL</p>
                            <p class="mt-1 text-sm text-blue-600 break-all">{{ viewModelData.image }}</p>
                        </div>
                        <div class="md:col-span-2" v-if="viewModelData.specifications && Object.keys(viewModelData.specifications).length > 0">
                            <p class="text-sm font-medium text-gray-500">Specifications</p>
                            <div class="mt-2 space-y-1">
                                <div v-for="(value, key) in viewModelData.specifications" :key="key" 
                                    class="flex justify-between border-b border-gray-100 py-1">
                                    <span class="font-medium text-gray-700 capitalize">{{ key }}:</span>
                                    <span class="text-gray-900">{{ value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" />
                        <Button label="Edit Model" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewModelData)" />
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