<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    models: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const models = ref(props.models);

watch(() => props.models, (newModels) => {
    models.value = newModels;
});

watch(search, (newSearch) => {
    router.get(route("admin.models.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.models.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const createModel = () => router.get(route("admin.models.create"));
const viewModel = (id) => router.get(route("admin.models.show", id));
const editModel = (id) => router.get(route("admin.models.edit", id));

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
</script>

<template>

    <Head title="Model Listing" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 card">
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <Button label="Create New Model" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="createModel" />
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
                        <Button icon="pi pi-eye" outlined rounded severity="info" class="mr-2"
                            @click="viewModel(slotProps.data.id)" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="editModel(slotProps.data.id)" />
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
</style>