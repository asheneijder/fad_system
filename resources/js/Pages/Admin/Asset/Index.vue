<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Toast from 'primevue/toast';

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    assets: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const page = ref(props.filters?.page || 1);

watch(search, (newSearch) => {
    router.get(route("admin.index.asset"), { search: newSearch }, { preserveState: true, replace: true });
});

watch(page, (newPage) => {
    router.get(route("admin.index.asset"), { search: search.value, page: newPage }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.index.asset"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const editAsset = (id) => {
    router.get(route("admin.asset.edit", id));
};

const deleteAsset = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this asset?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.assets.destroy", { asset: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Asset deleted successfully",
                        life: 3000,
                    });
                },
            });
        }
    });
};

const createAsset = () => {
    router.get(route("admin.asset.create"));
};
</script>

<template>

    <Head title="Assets" />
    <app-layout>
        <Toast />
        <ConfirmDialog />
        <div class="card">
            <div class="flex justify-between mb-4">
                <Button 
                    label="Create New Asset" 
                    icon="pi pi-plus" 
                    class="p-button-sm p-button-success"
                    @click="createAsset" 
                />
                <InputText 
                    v-model="search" 
                    placeholder="Search assets..." 
                    class="p-inputtext-sm" 
                />
            </div>

            <DataTable 
                :value="assets.data" 
                showGridlines 
                :rowHover="true" 
                :loading="assets.loading" 
                paginator
                :rows="assets.per_page" 
                :totalRecords="assets.total"
                :first="(assets.current_page - 1) * assets.per_page" 
                lazy @page="onPageChange"
                tableStyle="min-width: 50rem" 
                responsiveLayout="scroll">
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No assets found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or adding new assets.</p>
                    </div>
                </template>

                <Column header="#" style="width: 50px;">
                    <template #body="slotProps">
                        {{ (assets.current_page - 1) * assets.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="asset_name" header="Asset Name"></Column>
                <Column field="asset_tag_no" header="Asset Tag No."></Column>
                <Column field="serial_no" header="Serial No."></Column>
                <Column field="modelType.model_name" header="Model"></Column>
                <Column field="modelType.categoryType.category_name" header="Category"></Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <span :class="{
                            'bg-green-100 text-green-800 border border-green-300': slotProps.data.status === 1,
                            'bg-red-100 text-red-800 border border-red-300': slotProps.data.status !== 1
                        }" class="px-3 py-1 text-sm font-semibold rounded-full">
                            {{ slotProps.data.status === 1 ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                </Column>
                <Column field="qty" header="Quantity"></Column>
                <Column field="location" header="Location"></Column>
                <Column field="purchase_cost" header="Purchase Cost (MYR)"></Column>
                <Column field="current_value" header="Current Value (MYR)"></Column>
                <Column field="users.name" header="Created By"></Column>
                <Column field="created_at" header="Added On"></Column>
                <Column header="Actions" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-pencil" 
                            outlined 
                            rounded 
                            class="mr-2"
                            @click="editAsset(slotProps.data.id)" />
                        <Button 
                            icon="pi pi-trash" 
                            outlined 
                            rounded 
                            severity="danger"
                            @click="deleteAsset(slotProps.data.id)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </app-layout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
</style>
