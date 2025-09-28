<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    models: Object,
    filters: Object,
    categoryTypes: Array,
});

const search = ref(props.filters?.search || "");
const models = ref(props.models);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Form data
const createForm = useForm({
    model_name: '',
    model_no: '',
    description: '',
    category_type_id: null,
});

const editForm = useForm({
    id: null,
    model_name: '',
    model_no: '',
    description: '',
    category_type_id: null,
});

watch(() => props.models, (newModels) => {
    models.value = newModels;
});

watch(search, (newSearch) => {
    router.get(route("admin.models.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.models.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
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
    createForm.post(route("admin.models.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Model created successfully",
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
const openEditModal = (model) => {
    editForm.id = model.id;
    editForm.model_name = model.model_name;
    editForm.model_no = model.model_no;
    editForm.description = model.description;
    editForm.category_type_id = model.category_type_id;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route("admin.models.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Model updated successfully",
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

const viewModel = (id) => router.get(route("admin.models.show", id));

const deleteModel = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this model?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.models.destroy", { model: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Model deleted successfully",
                        life: 3000,
                    });
                },
            });
        },
    });
};

// Computed properties for form validation
const isCreateFormValid = computed(() => {
    return createForm.model_name && createForm.description && createForm.category_type_id;
});

const isEditFormValid = computed(() => {
    return editForm.model_name && editForm.description && editForm.category_type_id;
});
</script>

<template>
    <Head title="Model Listing" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Create Modal -->
        <Dialog v-model:visible="showCreateModal" modal header="Create New Model" :style="{ width: '450px' }" 
                :closable="!createForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="create_model_name" class="block text-sm font-medium text-gray-700 mb-2">Model Name *</label>
                    <InputText
                        id="create_model_name"
                        v-model="createForm.model_name"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.model_name }"
                        placeholder="Enter model name"
                    />
                    <small v-if="createForm.errors.model_name" class="p-error">{{ createForm.errors.model_name }}</small>
                </div>

                <div class="field">
                    <label for="create_model_no" class="block text-sm font-medium text-gray-700 mb-2">Model No.</label>
                    <InputText
                        id="create_model_no"
                        v-model="createForm.model_no"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.model_no }"
                        placeholder="Enter model number"
                    />
                    <small v-if="createForm.errors.model_no" class="p-error">{{ createForm.errors.model_no }}</small>
                </div>

                <div class="field">
                    <label for="create_category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <Dropdown
                        id="create_category"
                        v-model="createForm.category_type_id"
                        :options="categoryTypes"
                        optionLabel="category_name"
                        optionValue="id"
                        placeholder="Select a category"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.category_type_id }"
                    />
                    <small v-if="createForm.errors.category_type_id" class="p-error">{{ createForm.errors.category_type_id }}</small>
                </div>

                <div class="field">
                    <label for="create_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <Textarea
                        id="create_description"
                        v-model="createForm.description"
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.description }"
                        placeholder="Enter description"
                    />
                    <small v-if="createForm.errors.description" class="p-error">{{ createForm.errors.description }}</small>
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
        <Dialog v-model:visible="showEditModal" modal header="Edit Model" :style="{ width: '450px' }"
                :closable="!editForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="edit_model_name" class="block text-sm font-medium text-gray-700 mb-2">Model Name *</label>
                    <InputText
                        id="edit_model_name"
                        v-model="editForm.model_name"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.model_name }"
                        placeholder="Enter model name"
                    />
                    <small v-if="editForm.errors.model_name" class="p-error">{{ editForm.errors.model_name }}</small>
                </div>

                <div class="field">
                    <label for="edit_model_no" class="block text-sm font-medium text-gray-700 mb-2">Model No.</label>
                    <InputText
                        id="edit_model_no"
                        v-model="editForm.model_no"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.model_no }"
                        placeholder="Enter model number"
                    />
                    <small v-if="editForm.errors.model_no" class="p-error">{{ editForm.errors.model_no }}</small>
                </div>

                <div class="field">
                    <label for="edit_category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <Dropdown
                        id="edit_category"
                        v-model="editForm.category_type_id"
                        :options="categoryTypes"
                        optionLabel="category_name"
                        optionValue="id"
                        placeholder="Select a category"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.category_type_id }"
                    />
                    <small v-if="editForm.errors.category_type_id" class="p-error">{{ editForm.errors.category_type_id }}</small>
                </div>

                <div class="field">
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <Textarea
                        id="edit_description"
                        v-model="editForm.description"
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.description }"
                        placeholder="Enter description"
                    />
                    <small v-if="editForm.errors.description" class="p-error">{{ editForm.errors.description }}</small>
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
                <Button label="Create New Model" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="openCreateModal" />
                <InputText v-model="search" placeholder="Search models..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable :value="models.data" :loading="models.loading" showGridlines :rowHover="true" paginator lazy
                :rows="models.per_page" :totalRecords="models.total"
                :first="(models.current_page - 1) * models.per_page" @page="onPageChange" responsiveLayout="scroll"
                tableStyle="min-width: 50rem">

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No models found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or adding new models.</p>
                    </div>
                </template>

                <Column header="#" style="width: 50px;">
                    <template #body="slotProps">
                        {{ (models.current_page - 1) * models.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="model_name" header="Model Name" />
                <Column field="model_no" header="Model No." />
                <Column field="description" header="Description" />
                <Column header="Category">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.category_type?.category_name || '—'" severity="info" />
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="openEditModal(slotProps.data)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="deleteModel(slotProps.data.id)" />
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