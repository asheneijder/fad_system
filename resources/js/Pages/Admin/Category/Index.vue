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
    categories: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Category Management' }];

const search = ref(props.filters?.search || "");
const categories = ref(props.categories);
const showCreateEditDialog = ref(false);
const showViewDialog = ref(false);
const isEditMode = ref(false);
const selectedCategoryId = ref(null);
const viewCategoryData = ref(null);
const selectedCategories = ref([]);
const actionMenu = ref();

// Forms
const categoryForm = useForm({
    name: '',
    description: '',
    type: 'product',
    status: true,
    parent_id: null,
    sort_order: 0,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
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

// Category type options
const categoryTypes = ref([
    { label: 'Product Category', value: 'product' },
    { label: 'Blog Category', value: 'blog' },
    { label: 'Service Category', value: 'service' },
    { label: 'Document Category', value: 'document' },
    { label: 'General Category', value: 'general' }
]);

// Statistics
const statistics = computed(() => {
    const data = categories.value?.data || [];
    
    return {
        total: categories.value?.total || 0,
        active: data.filter(c => c.status).length,
        inactive: data.filter(c => !c.status).length,
        product: data.filter(c => c.type === 'product').length,
        blog: data.filter(c => c.type === 'blog').length,
        service: data.filter(c => c.type === 'service').length,
        withParent: data.filter(c => c.parent_id).length,
        withoutParent: data.filter(c => !c.parent_id).length,
    };
});

// Watchers
watch(() => props.categories, (newCategories) => {
    categories.value = newCategories;
}, { immediate: true });

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        router.get(route("admin.categories.index"), {
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

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("admin.categories.index"), {
        search: search.value,
        page: page,
    }, {
        preserveState: true,
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
    categoryForm.type = 'product';
    categoryForm.sort_order = 0;
    showCreateEditDialog.value = true;
};

const openEditDialog = (category) => {
    isEditMode.value = true;
    selectedCategoryId.value = category.id;
    
    categoryForm.name = category.name;
    categoryForm.description = category.description || '';
    categoryForm.type = category.type || 'product';
    categoryForm.status = category.status;
    categoryForm.parent_id = category.parent_id || null;
    categoryForm.sort_order = category.sort_order || 0;
    categoryForm.meta_title = category.meta_title || '';
    categoryForm.meta_description = category.meta_description || '';
    categoryForm.meta_keywords = category.meta_keywords || '';
    
    showCreateEditDialog.value = true;
};

const viewCategory = (category) => {
    viewCategoryData.value = category;
    showViewDialog.value = true;
};

const saveCategory = () => {
    if (isEditMode.value) {
        categoryForm.put(route('admin.categories.update', selectedCategoryId.value), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category updated successfully',
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
        categoryForm.post(route('admin.categories.store'), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category created successfully',
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
    if (selectedCategories.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select categories first',
            life: 3000
        });
        return;
    }

    const form = useForm({
        category_ids: selectedCategories.value.map(cat => cat.id),
        status: status
    });

    form.post(route('admin.categories.bulk-update-status'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${selectedCategories.value.length} category(s) ${status ? 'activated' : 'deactivated'}`,
                life: 3000
            });
            selectedCategories.value = [];
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update categories',
                life: 3000
            });
        }
    });
};

const bulkDelete = () => {
    if (selectedCategories.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select categories first',
            life: 3000
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete ${selectedCategories.value.length} selected category(s)? This action cannot be undone.`,
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            const form = useForm({
                category_ids: selectedCategories.value.map(cat => cat.id)
            });

            form.post(route('admin.categories.bulk-delete'), {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: `${selectedCategories.value.length} category(s) deleted successfully`,
                        life: 3000,
                    });
                    selectedCategories.value = [];
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to delete categories",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const exportSelected = () => {
    if (selectedCategories.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select categories to export',
            life: 3000
        });
        return;
    }

    const categoryIds = selectedCategories.value.map(cat => cat.id);
    
    // Create a temporary form to submit the export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.categories.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    categoryIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'category_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const deleteCategory = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this category? This action cannot be undone.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.categories.destroy", id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Category deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete category",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getStatusSeverity = (category) => {
    return category.status ? 'success' : 'danger';
};

const getStatusText = (category) => {
    return category.status ? 'Active' : 'Inactive';
};

const getTypeBadge = (type) => {
    const typeMap = {
        product: { label: 'Product', severity: 'primary' },
        blog: { label: 'Blog', severity: 'info' },
        service: { label: 'Service', severity: 'warning' },
        document: { label: 'Document', severity: 'help' },
        general: { label: 'General', severity: 'secondary' }
    };
    
    return typeMap[type] || { label: type, severity: 'secondary' };
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getParentCategoryName = (parentId, categories) => {
    if (!parentId) return '—';
    const parent = categories.find(cat => cat.id === parentId);
    return parent ? parent.name : '—';
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};
</script>

<template>
    <Head title="Category Management" />
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
                    <h1 class="text-3xl font-bold text-gray-800">Category Management</h1>
                    <p class="mt-1 text-gray-500">Organize and manage your content categories</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" />
                    <Button label="Create Category" icon="pi pi-plus" severity="success"
                        @click="openCreateDialog" class="font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Categories</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-folder"></i>
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
                                <p class="text-sm font-medium text-gray-500">Product</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.product }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-shopping-bag"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-indigo-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Blog</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.blog }}</p>
                            </div>
                            <div class="p-3 bg-indigo-100 rounded-full">
                                <i class="text-xl text-indigo-600 pi pi-book"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Service</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.service }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="text-xl text-orange-600 pi pi-cog"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-teal-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">With Parent</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.withParent }}</p>
                            </div>
                            <div class="p-3 bg-teal-100 rounded-full">
                                <i class="text-xl text-teal-600 pi pi-sitemap"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-pink-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Root Categories</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.withoutParent }}</p>
                            </div>
                            <div class="p-3 bg-pink-100 rounded-full">
                                <i class="text-xl text-pink-600 pi pi-home"></i>
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
                            <Button label="Create Category" icon="pi pi-plus" severity="success"
                                @click="openCreateDialog" class="font-semibold" />
                            <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                                @click="toggleActionMenu" />
                        </div>

                        <div class="w-full lg:w-auto">
                            <span class="p-input-icon-left">
                                <i class="pi pi-search" />
                                <InputText v-model="search" placeholder="Search categories..." 
                                    class="w-full lg:w-80" />
                            </span>
                        </div>
                    </div>

                    <!-- Selected Categories Info -->
                    <div v-if="selectedCategories.length > 0" class="p-3 mt-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800">
                                {{ selectedCategories.length }} category(s) selected
                            </span>
                            <Button label="Clear" icon="pi pi-times" severity="secondary" text
                                @click="selectedCategories = []" />
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="categories.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="categories.per_page" :totalRecords="categories.total"
                            :first="(categories.current_page - 1) * categories.per_page" @page="onPageChange"
                            v-model:selection="selectedCategories" dataKey="id"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-folder"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Categories Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your search or create a new category.</p>
                                    <Button label="Create First Category" icon="pi pi-plus" severity="success"
                                        @click="openCreateDialog" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 3rem" />

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(categories.current_page - 1) * categories.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column field="name" header="Category Name" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500" v-if="slotProps.data.description">
                                        {{ slotProps.data.description.substring(0, 50) }}{{ slotProps.data.description.length > 50 ? '...' : '' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="type" header="Type" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getTypeBadge(slotProps.data.type).label"
                                        :severity="getTypeBadge(slotProps.data.type).severity"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Parent" sortable style="width: 140px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ getParentCategoryName(slotProps.data.parent_id, categories.data) }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="sort_order" header="Sort Order" sortable style="width: 100px;">
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
                                            v-tooltip.top="'View Details'" @click="viewCategory(slotProps.data)" />

                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit Category'" @click="openEditDialog(slotProps.data)" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete Category'" @click="deleteCategory(slotProps.data.id)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Category Dialog -->
            <Dialog v-model:visible="showCreateEditDialog" modal 
                :header="isEditMode ? 'Edit Category' : 'Create New Category'" 
                :style="{ width: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Category Name -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="categoryForm.name" 
                                placeholder="Enter category name" 
                                class="w-full"
                                :class="{ 'p-invalid': categoryForm.errors.name }" />
                            <small class="text-red-500 text-xs" v-if="categoryForm.errors.name">
                                {{ categoryForm.errors.name }}
                            </small>
                        </div>

                        <!-- Category Type -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Category Type <span class="text-red-500">*</span>
                            </label>
                            <Select v-model="categoryForm.type" 
                                :options="categoryTypes" 
                                optionLabel="label" 
                                optionValue="value"
                                placeholder="Select category type"
                                class="w-full"
                                :class="{ 'p-invalid': categoryForm.errors.type }" />
                            <small class="text-red-500 text-xs" v-if="categoryForm.errors.type">
                                {{ categoryForm.errors.type }}
                            </small>
                        </div>

                        <!-- Parent Category -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Parent Category</label>
                            <Select v-model="categoryForm.parent_id" 
                                :options="categories.data" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Select parent category"
                                class="w-full"
                                :filter="true"
                                :showClear="true" />
                        </div>

                        <!-- Sort Order -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Sort Order</label>
                            <InputNumber v-model="categoryForm.sort_order" 
                                :min="0" 
                                class="w-full" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2">
                            <Checkbox v-model="categoryForm.status" :binary="true" inputId="status" />
                            <label for="status" class="text-sm font-semibold text-gray-700">Active Category</label>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Description</label>
                            <Textarea v-model="categoryForm.description" 
                                rows="3" 
                                placeholder="Enter category description..."
                                class="w-full" />
                        </div>

                        <!-- Meta Title -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Meta Title</label>
                            <InputText v-model="categoryForm.meta_title" 
                                placeholder="Enter meta title for SEO" 
                                class="w-full" />
                        </div>

                        <!-- Meta Description -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Meta Description</label>
                            <Textarea v-model="categoryForm.meta_description" 
                                rows="2" 
                                placeholder="Enter meta description for SEO"
                                class="w-full" />
                        </div>

                        <!-- Meta Keywords -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Meta Keywords</label>
                            <InputText v-model="categoryForm.meta_keywords" 
                                placeholder="Enter meta keywords (comma separated)" 
                                class="w-full" />
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showCreateEditDialog = false"
                            :disabled="categoryForm.processing" />
                        <Button :label="isEditMode ? 'Update Category' : 'Create Category'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveCategory"
                            :loading="categoryForm.processing" />
                    </div>
                </div>
            </Dialog>

            <!-- View Category Dialog -->
            <Dialog v-model:visible="showViewDialog" modal header="Category Details" :style="{ width: '600px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }">
                <div v-if="viewCategoryData" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Category Name</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewCategoryData.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Type</p>
                            <Badge :value="getTypeBadge(viewCategoryData.type).label"
                                :severity="getTypeBadge(viewCategoryData.type).severity"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Parent Category</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ getParentCategoryName(viewCategoryData.parent_id, categories.data) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Sort Order</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ viewCategoryData.sort_order }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <Badge :value="getStatusText(viewCategoryData)"
                                :severity="getStatusSeverity(viewCategoryData)"
                                class="mt-1 capitalize" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created Date</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ formatDate(viewCategoryData.created_at) }}
                            </p>
                        </div>
                        <div class="md:col-span-2" v-if="viewCategoryData.description">
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewCategoryData.description }}</p>
                        </div>
                        <div class="md:col-span-2" v-if="viewCategoryData.meta_title">
                            <p class="text-sm font-medium text-gray-500">Meta Title</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewCategoryData.meta_title }}</p>
                        </div>
                        <div class="md:col-span-2" v-if="viewCategoryData.meta_description">
                            <p class="text-sm font-medium text-gray-500">Meta Description</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewCategoryData.meta_description }}</p>
                        </div>
                        <div class="md:col-span-2" v-if="viewCategoryData.meta_keywords">
                            <p class="text-sm font-medium text-gray-500">Meta Keywords</p>
                            <p class="mt-1 text-base text-gray-900">{{ viewCategoryData.meta_keywords }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Close" severity="secondary" outlined @click="showViewDialog = false" />
                        <Button label="Edit Category" icon="pi pi-pencil" severity="warning" 
                            @click="showViewDialog = false; openEditDialog(viewCategoryData)" />
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