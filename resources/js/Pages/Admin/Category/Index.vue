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
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    categories: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Category Management' }];

const search = ref(props.filters?.search || "");
const typeFilter = ref(props.filters?.type || "");
const categories = ref(props.categories);
const showCreateEditDialog = ref(false);
const isEditMode = ref(false);
const selectedCategoryId = ref(null);
const currentPerPage = ref(props.categories?.per_page || 10);

// Forms
const categoryForm = useForm({
    name: '',
    description: '',
    type: 'stationary',
    status: true,
    parent_id: null,
    sort_order: 0,
});

// Category type options
const categoryTypes = ref([
    { label: 'All Types', value: '' },
    { label: 'Stationary', value: 'stationary' },
    { label: 'Asset', value: 'asset' },
    { label: 'Equipment', value: 'equipment' },
    { label: 'Furniture', value: 'furniture' },
    { label: 'Electronic', value: 'electronic' },
    { label: 'Other', value: 'other' }
]);

const categoryTypesCreate = ref([
    { label: 'Stationary', value: 'stationary' },
    { label: 'Asset', value: 'asset' },
    { label: 'Equipment', value: 'equipment' },
    { label: 'Furniture', value: 'furniture' },
    { label: 'Electronic', value: 'electronic' },
    { label: 'Other', value: 'other' }
]);

// Statistics
const statistics = computed(() => {
    const data = categories.value?.data || [];
    
    return {
        total: categories.value?.total || 0,
        active: data.filter(c => c.status).length,
        stationary: data.filter(c => c.type === 'stationary').length,
        asset: data.filter(c => c.type === 'asset').length,
        equipment: data.filter(c => c.type === 'equipment').length,
        withParent: data.filter(c => c.parent_id).length,
    };
});

// Watchers
watch(() => props.categories, (newCategories) => {
    categories.value = newCategories;
    currentPerPage.value = newCategories?.per_page || 10;
}, { immediate: true });

watch([search, typeFilter], ([newSearch, newType], [oldSearch, oldType]) => {
    if (newSearch !== oldSearch || newType !== oldType) {
        router.get(route("admin.categories.index"), {
            search: newSearch,
            type: newType,
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

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.categories.index"), {
        search: search.value,
        type: typeFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const resetForm = () => {
    categoryForm.reset();
    isEditMode.value = false;
    selectedCategoryId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    isEditMode.value = false;
    categoryForm.status = true;
    categoryForm.type = 'stationary';
    categoryForm.sort_order = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (category) => {
    isEditMode.value = true;
    selectedCategoryId.value = category.id;
    
    categoryForm.name = category.name;
    categoryForm.description = category.description || '';
    categoryForm.type = category.type || 'stationary';
    categoryForm.status = category.status;
    categoryForm.parent_id = category.parent_id || null;
    categoryForm.sort_order = category.sort_order || 0;
    
    showCreateEditDialog.value = true;
};

const saveCategory = () => {
    if (isEditMode.value) {
        categoryForm.put(route('admin.categories.update', selectedCategoryId.value), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Category updated successfully', life: 3000 });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) errorMessage = errors.name[0];
                toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
            }
        });
    } else {
        categoryForm.post(route('admin.categories.store'), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Category created successfully', life: 3000 });
                showCreateEditDialog.value = false;
                resetForm();
            },
            onError: (errors) => {
                let errorMessage = 'Please check all required fields';
                if (errors.name) errorMessage = errors.name[0];
                toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
            }
        });
    }
};

const deleteCategory = (id) => {
    confirm.require({
        message: "Delete this category? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.categories.destroy", id), {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: "success", summary: "Deleted", detail: "Category deleted successfully", life: 3000 }),
                onError: (errors) => toast.add({ severity: "error", summary: "Error", detail: errors.message || "Failed to delete category", life: 3000 })
            });
        },
    });
};

const getStatusSeverity = (category) => category.status ? 'success' : 'danger';

const getStatusText = (category) => category.status ? 'Active' : 'Inactive';

const getTypeBadge = (type) => {
    const typeMap = {
        stationary: { label: 'Stationary', severity: 'primary' },
        asset: { label: 'Asset', severity: 'info' },
        equipment: { label: 'Equipment', severity: 'warning' },
        furniture: { label: 'Furniture', severity: 'help' },
        electronic: { label: 'Electronic', severity: 'success' },
        other: { label: 'Other', severity: 'secondary' }
    };
    return typeMap[type] || { label: type, severity: 'secondary' };
};

const getParentCategoryName = (parentId, categories) => {
    if (!parentId) return '—';
    const parent = categories.find(cat => cat.id === parentId);
    return parent ? parent.name : '—';
};
</script>

<template>
    <Head title="Category Management" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Category Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Organize your inventory categories</p>
                </div>
                <Button label="Create Category" icon="pi pi-plus" severity="success"
                    @click="openCreateDialog" class="w-full sm:w-auto text-xs sm:text-sm font-semibold" />
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Categories</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-folder"></i>
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

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Stationary</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.stationary }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-pencil"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Assets</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.asset }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-desktop"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-teal-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Equipment</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.equipment }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-teal-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-teal-600 pi pi-cog"></i>
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
                            <Button label="Create" icon="pi pi-plus" severity="success"
                                @click="openCreateDialog" class="w-full sm:w-auto text-xs sm:text-sm" />

                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <Select v-model="typeFilter" :options="categoryTypes" optionLabel="label" 
                                    optionValue="value" placeholder="All Types" 
                                    class="w-full sm:w-36 text-xs sm:text-sm" />
                                <IconField iconPosition="left" class="w-full sm:w-64">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search..." 
                                        class="w-full text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="categories.data" showGridlines stripedRows :rowHover="true" 
                            paginator :rows="categories.per_page" :totalRecords="categories.total"
                            :first="(categories.current_page - 1) * categories.per_page" @page="onPageChange"
                            dataKey="id" 
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-folder"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Categories Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Create your first category to get started.</p>
                                    <Button label="Create Category" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(categories.current_page - 1) * categories.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="name" header="Category Name" sortable style="min-width: 180px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500 truncate" v-if="slotProps.data.description">
                                        {{ slotProps.data.description }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="type" header="Type" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <Badge :value="getTypeBadge(slotProps.data.type).label"
                                        :severity="getTypeBadge(slotProps.data.type).severity"
                                        class="capitalize text-xs" />
                                </template>
                            </Column>

                            <Column header="Parent" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                                        {{ getParentCategoryName(slotProps.data.parent_id, categories.data) }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="sort_order" header="Order" sortable style="min-width: 80px;">
                                <template #body="slotProps">
                                    <div class="text-center">
                                        <Badge :value="slotProps.data.sort_order" severity="info" class="text-xs" />
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
                            <Column header="Actions" style="min-width: 100px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditDialog(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" @click="deleteCategory(slotProps.data.id)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ categories.current_page }} of {{ Math.ceil(categories.total / categories.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Category Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Category' : 'Create New Category'" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '85vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <!-- Category Name -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <InputText v-model="categoryForm.name" 
                            placeholder="e.g., Pens, Paper, Computers" 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': categoryForm.errors.name }" />
                        <small class="text-red-500 text-xs" v-if="categoryForm.errors.name">
                            {{ categoryForm.errors.name }}
                        </small>
                    </div>

                    <!-- Category Type -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                            Category Type <span class="text-red-500">*</span>
                        </label>
                        <Select v-model="categoryForm.type" 
                            :options="categoryTypesCreate" 
                            optionLabel="label" 
                            optionValue="value"
                            placeholder="Select category type"
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': categoryForm.errors.type }" />
                    </div>

                    <!-- Parent Category -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Parent Category (Optional)</label>
                        <Select v-model="categoryForm.parent_id" 
                            :options="categories.data" 
                            optionLabel="name" 
                            optionValue="id"
                            placeholder="Select parent category"
                            class="w-full text-xs sm:text-sm"
                            :filter="true"
                            :showClear="true" />
                    </div>

                    <!-- Description -->
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Description (Optional)</label>
                        <Textarea v-model="categoryForm.description" 
                            rows="2" 
                            placeholder="Brief description..."
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <!-- Sort Order -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Sort Order</label>
                            <InputNumber v-model="categoryForm.sort_order" 
                                :min="0" 
                                class="w-full text-xs sm:text-sm" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2 pt-4 sm:pt-6">
                            <Checkbox v-model="categoryForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-xs sm:text-sm font-semibold text-gray-700">Active</label>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="categoryForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update' : 'Create'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveCategory"
                            :loading="categoryForm.processing"
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

:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        padding: 0.5rem 0.75rem;
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
