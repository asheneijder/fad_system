<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const categories = ref(props.categories);

watch(() => props.categories, (newCategories) => {
    categories.value = newCategories;
});

watch(search, (newSearch) => {
    router.get(route("admin.categories.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.categories.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const createCategory = () => router.get(route("admin.categories.create"));
const editCategory = (id) => router.get(route("admin.categories.edit", id));
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
</script>

<template>

    <Head title="Categories" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 card">
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <Button label="Create New Category" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="createCategory" />
                <InputText v-model="search" placeholder="Search categories..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable :value="categories.data" showGridlines :rowHover="true" paginator lazy
                :rows="categories.per_page" :totalRecords="categories.total"
                :first="(categories.current_page - 1) * categories.per_page" @page="onPageChange"
                responsiveLayout="scroll" tableStyle="min-width: 40rem">

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
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
                            @click="editCategory(slotProps.data.id)" />
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
</style>
