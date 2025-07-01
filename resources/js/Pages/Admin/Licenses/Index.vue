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
import Tooltip from 'primevue/tooltip';
import Badge from 'primevue/badge';
import { ref, watch } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    licenses: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const licenses = ref(props.licenses);

watch(() => props.licenses, (newLicenses) => {
    licenses.value = newLicenses;
});

watch(search, (newSearch) => {
    router.get(route("admin.licenses.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.licenses.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const createLicense = () => router.get(route("admin.licenses.create"));
const editLicense = (id) => router.get(route("admin.licenses.edit", id));
const viewLicense = (id) => router.get(route("admin.licenses.show", id));

const deleteLicense = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this license?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.licenses.destroy", { license: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "License deleted successfully",
                        life: 3000,
                    });
                },
            });
        },
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.add({
            severity: 'info',
            summary: 'Copied',
            detail: 'Product key copied to clipboard',
            life: 2000,
        });
    });
};
</script>

<template>

    <Head title="Licenses" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 card">
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <Button label="Create New License" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="createLicense" />
                <InputText v-model="search" placeholder="Search licenses..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable :value="licenses.data" showGridlines :rowHover="true" paginator lazy :rows="licenses.per_page"
                :totalRecords="licenses.total" :first="(licenses.current_page - 1) * licenses.per_page"
                @page="onPageChange" responsiveLayout="scroll" tableStyle="min-width: 70rem">

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <p class="mt-2 text-lg font-medium text-gray-500">No licenses found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or add a new license.</p>
                    </div>
                </template>
                
                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{ (licenses.current_page - 1) * licenses.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="license_name" header="Name" />
                <Column header="Product Key">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-1 font-mono text-sm bg-blue-100 text-blue-800 border border-blue-300 rounded truncate max-w-[200px]">
                                {{ slotProps.data.product_key }}
                            </span>
                            <Button icon="pi pi-copy" class="p-button-text p-button-sm"
                                @click="copyToClipboard(slotProps.data.product_key)" />
                        </div>
                    </template>
                </Column>
                <Column field="expiration_date" header="Expiration Date" />
                <Column field="min_qty" header="Minimum Quantity" />
                <Column field="available_qty" header="Available" />
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <span :class="{
                            'bg-green-100 text-green-800 border border-green-300': slotProps.data.status,
                            'bg-red-100 text-red-800 border border-red-300': !slotProps.data.status
                        }" class="px-3 py-1 text-sm font-semibold rounded-full">
                            {{ slotProps.data.status ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 14rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-eye" outlined rounded severity="info" class="mr-2"
                            @click="viewLicense(slotProps.data.id)" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="editLicense(slotProps.data.id)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="deleteLicense(slotProps.data.id)" />
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
