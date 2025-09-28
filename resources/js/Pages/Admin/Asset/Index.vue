<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog'
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { Link, router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    assets: Object,
    filters: Object,
    users: Array,
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Asset' },
];

const search = ref(props.filters?.search || "");
const assets = ref(props.assets);

const selectedAssetId = ref(null);

const showAssignToDialog = ref(false)

const selectedUserId = ref(null);

watch(() => props.assets, (newAssets) => {
    assets.value = newAssets;
});

watch(search, (newSearch) => {
    router.get(route("admin.assets.index"), { search: newSearch }, { preserveState: true, replace: true });
});

watch(showAssignToDialog, (val) => {
    if (!val) {
        selectedAssetId.value = null;
        selectedUserId.value = null;
    }
});

const onPageChange = (event) => {
    router.get(route("admin.assets.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const createAsset = () => router.get(route("admin.assets.create"));
const viewAsset = (id) => router.get(route("admin.assets.show", id));
const editAsset = (id) => router.get(route("admin.assets.edit", id));

const openAssignDialog = (assetId) => {
    selectedAssetId.value = assetId;
    showAssignToDialog.value = true;
};

const assignAsset = () => {
    if (!selectedUserId.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validation',
            detail: 'Please select a user.',
            life: 3000
        });
        return;
    }

    router.post(route('admin.asset.assign'), {
        asset_id: selectedAssetId.value,
        user_id: selectedUserId.value
    }, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Asset assigned successfully.',
                life: 3000
            });

            showAssignToDialog.value = false;
            selectedUserId.value = null;
        }
    });
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
        },
    });
};
</script>

<template>

    <Head title="Assets" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 card">
            <!-- Header -->
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <Button label="Create New Asset" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="createAsset" />
                <InputText v-model="search" placeholder="Search assets..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <!-- Table -->
            <DataTable :value="assets.data" :loading="assets.loading" showGridlines :rowHover="true" paginator lazy
                :rows="assets.per_page" :totalRecords="assets.total"
                :first="(assets.current_page - 1) * assets.per_page" @page="onPageChange" responsiveLayout="scroll"
                tableStyle="min-width: 50rem">
                <!-- Empty -->
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No assets found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or adding new assets.</p>
                    </div>
                </template>

                <!-- Columns -->
                <Column header="#" style="width: 50px;">
                    <template #body="slotProps">
                        {{ (assets.current_page - 1) * assets.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="asset_name" header="Asset Name" />
                <Column field="asset_tag_no" header="Asset Tag No." />
                <Column field="serial_no" header="Serial No." />
                <Column field="qty" header="Quantity" />
                <Column header="Model">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.model_type?.model_name || '—'" severity="warn" />
                    </template>
                </Column>
                <Column header="Category">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.model_type?.category_type?.category_name || '—'"
                            severity="info" />
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="slotProps">
                        <span :class="{
                            'bg-green-100 text-green-800 border border-green-300': slotProps.data.status === 'active',
                            'bg-yellow-100 text-yellow-800 border border-yellow-300': slotProps.data.status === 'assigned',
                            'bg-gray-100 text-gray-800 border border-gray-300': slotProps.data.status === 'inactive',
                            'bg-red-100 text-red-800 border border-red-300': slotProps.data.status === 'damaged' || slotProps.data.status === 'lost',
                            'bg-blue-100 text-blue-800 border border-blue-300': slotProps.data.status === 'available'
                        }" class="px-3 py-1 text-sm font-semibold capitalize rounded-full">
                            {{ slotProps.data.status.replace('_', ' ') }}
                        </span>
                    </template>
                </Column>

                <!-- Actions -->
                <Column header="Actions" style="min-width: 20rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-eye" outlined rounded severity="info" class="mr-2"
                            @click="viewAsset(slotProps.data.id)" />

                        <Button v-if="slotProps.data.status === 'available'" icon="pi pi-user-plus" outlined rounded
                            severity="help" class="mr-2" @click="openAssignDialog(slotProps.data.id)" />

                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="editAsset(slotProps.data.id)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="deleteAsset(slotProps.data.id)" />
                    </template>
                </Column>
            </DataTable>
            <Dialog v-model:visible="showAssignToDialog" modal header="Assign To User" :style="{ width: '30vw' }"
                :breakpoints="{ '1199px': '50vw', '575px': '80vw' }">
                <div class="space-y-4">
                    <Select v-model="selectedUserId" :options="props.users" optionLabel="name" optionValue="id" filter
                        placeholder="Select User" class="w-full">
                        <template #option="slotProps">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ slotProps.option.name }}</span>
                                <small class="text-gray-500">{{ slotProps.option.email }}</small>
                            </div>
                        </template>

                        <template #value="slotProps">
                            <span v-if="slotProps.value">
                                {{props.users.find(u => u.id === slotProps.value)?.name || 'Select User'}}
                            </span>
                            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                        </template>
                    </Select>

                    <!-- Submit -->
                    <div class="text-right">
                        <Button label="Assign Asset" icon="pi pi-check" @click="assignAsset" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
</style>
