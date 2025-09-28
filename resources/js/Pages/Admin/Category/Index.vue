<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const categories = ref(props.categories);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Form data
const createForm = useForm({
    category_name: '',
});

const editForm = useForm({
    id: null,
    category_name: '',
});

watch(() => props.categories, (newCategories) => {
    categories.value = newCategories;
});

watch(search, (newSearch) => {
    router.get(route("admin.categories.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(
        route("admin.categories.index"),
        { search: search.value, page: event.page + 1 },
        { preserveState: true, replace: true }
    );
};

// Create functions
const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route("admin.categories.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Category created successfully",
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Please check the form for errors",
                life: 3000,
            });
        }
    });
};

// Edit functions
const openEditModal = (category) => {
    editForm.id = category.id;
    editForm.category_name = category.category_name;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route("admin.categories.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Category updated successfully",
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Please check the form for errors",
                life: 3000,
            });
        }
    });
};

const deleteCategory = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this category?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.categories.destroy", { category: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Category deleted successfully",
                        life: 3000,
                    });
                },
            });
        },
    });
};

// Computed properties for form validation
const isCreateFormValid = computed(() => {
    return createForm.category_name && createForm.category_name.trim().length > 0;
});

const isEditFormValid = computed(() => {
    return editForm.category_name && editForm.category_name.trim().length > 0;
});
</script>

<template>
    <Head title="Categories" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Create Modal -->
        <Dialog v-model:visible="showCreateModal" modal header="Create New Category" :style="{ width: '400px' }" 
                :closable="!createForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="create_category_name" class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                    <InputText
                        id="create_category_name"
                        v-model="createForm.category_name"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.category_name }"
                        placeholder="Enter category name"
                        autofocus
                    />
                    <small v-if="createForm.errors.category_name" class="p-error">{{ createForm.errors.category_name }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" @click="closeCreateModal" 
                        class="p-button-text" :disabled="createForm.processing" />
                <Button label="Create" icon="pi pi-check" @click="submitCreate" 
                        :disabled="!isCreateFormValid || createForm.processing" 
                        :loading="createForm.processing" />
            </template>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog v-model:visible="showEditModal" modal header="Edit Category" :style="{ width: '400px' }"
                :closable="!editForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="edit_category_name" class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                    <InputText
                        id="edit_category_name"
                        v-model="editForm.category_name"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.category_name }"
                        placeholder="Enter category name"
                    />
                    <small v-if="editForm.errors.category_name" class="p-error">{{ editForm.errors.category_name }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" @click="closeEditModal" 
                        class="p-button-text" :disabled="editForm.processing" />
                <Button label="Update" icon="pi pi-check" @click="submitEdit" 
                        :disabled="!isEditFormValid || editForm.processing" 
                        :loading="editForm.processing" />
            </template>
        </Dialog>

        <div class="p-6 card">
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <Button label="Create New Category" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="openCreateModal" />
                <InputText v-model="search" placeholder="Search categories..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable :value="categories.data" showGridlines :rowHover="true" paginator lazy
                :rows="categories.per_page" :totalRecords="categories.total"
                :first="(categories.current_page - 1) * categories.per_page" @page="onPageChange"
                responsiveLayout="scroll" tableStyle="min-width: 40rem">

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No categories found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or add a new category.</p>
                    </div>
                </template>

                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{ (categories.current_page - 1) * categories.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="category_name" header="Category Name" />
                <Column field="created_at_formatted" header="Added On" />

                <Column header="Actions" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="openEditModal(slotProps.data)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="deleteCategory(slotProps.data.id)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}

.field {
    margin-bottom: 1rem;
}

.space-y-4 > * + * {
    margin-top: 1rem;
}
</style>