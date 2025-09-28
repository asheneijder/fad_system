<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    stationaryItems: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const items = ref(props.stationaryItems);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Status options
const statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];

// Unit options (you can expand this list)
const unitOptions = [
    { label: 'Pieces (pcs)', value: 'pcs' },
    { label: 'Box', value: 'box' },
    { label: 'Pack', value: 'pack' },
    { label: 'Ream', value: 'ream' },
    { label: 'Dozen', value: 'dozen' },
    { label: 'Meter (m)', value: 'm' },
    { label: 'Kilogram (kg)', value: 'kg' },
    { label: 'Liter (L)', value: 'L' },
    { label: 'Set', value: 'set' },
    { label: 'Roll', value: 'roll' }
];

// Form data
const createForm = useForm({
    description: '',
    unit: '',
    unit_cost: 0,
    status: true,
    initial_stock: 0, // For creating opening balance
});

const editForm = useForm({
    id: null,
    description: '',
    unit: '',
    unit_cost: 0,
    status: true,
});

watch(
    () => props.stationaryItems,
    (newData) => {
        items.value = newData;
    }
);

watch(search, (newSearch) => {
    router.get(
        route("admin.stationary-items.index"),
        { search: newSearch },
        { preserveState: true, replace: true }
    );
});

const onPageChange = (event) => {
    router.get(
        route("admin.stationary-items.index"),
        {
            search: search.value,
            page: event.page + 1,
        },
        { preserveState: true, replace: true }
    );
};

// Create functions
const openCreateModal = () => {
    createForm.reset();
    createForm.status = true; // Default to active
    createForm.unit_cost = 0;
    createForm.initial_stock = 0;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route("admin.stationary-items.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Stationary item created successfully",
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
const openEditModal = (item) => {
    editForm.id = item.id;
    editForm.description = item.description;
    editForm.unit = item.unit;
    editForm.unit_cost = item.unit_cost;
    editForm.status = item.status;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route("admin.stationary-items.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Stationary item updated successfully",
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

const viewItem = (id) => router.get(route("admin.stationary-items.show", id));

const deleteItem = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this item? This will also delete all related stock movements.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(
                route("admin.stationary-items.destroy", { item: id }),
                {
                    preserveState: true,
                    replace: true,
                    onSuccess: () => {
                        toast.add({
                            severity: "success",
                            summary: "Deleted",
                            detail: "Item deleted successfully",
                            life: 3000,
                        });
                    },
                }
            );
        },
    });
};

// Computed properties for form validation
const isCreateFormValid = computed(() => {
    return createForm.description && 
           createForm.unit && 
           createForm.unit_cost >= 0 && 
           createForm.initial_stock >= 0;
});

const isEditFormValid = computed(() => {
    return editForm.description && 
           editForm.unit && 
           editForm.unit_cost >= 0;
});

// Helper function to format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ms-MY', {
        style: 'currency',
        currency: 'MYR',
        minimumFractionDigits: 2
    }).format(amount || 0);
};

// Helper function to get stock status color
const getStockStatusClass = (stock) => {
    if (stock <= 0) return 'text-red-600 font-semibold';
    if (stock <= 10) return 'text-orange-600 font-semibold';
    return 'text-green-600';
};
</script>

<template>
    <Head title="Stationary Items" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Create Modal -->
        <Dialog v-model:visible="showCreateModal" modal header="Create New Stationary Item" :style="{ width: '500px' }" 
                :closable="!createForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="create_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <Textarea
                        id="create_description"
                        v-model="createForm.description"
                        rows="2"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.description }"
                        placeholder="Enter item description"
                        autofocus
                    />
                    <small v-if="createForm.errors.description" class="p-error">{{ createForm.errors.description }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_unit" class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                        <Dropdown
                            id="create_unit"
                            v-model="createForm.unit"
                            :options="unitOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select unit"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.unit }"
                        />
                        <small v-if="createForm.errors.unit" class="p-error">{{ createForm.errors.unit }}</small>
                    </div>

                    <div class="field">
                        <label for="create_unit_cost" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost (RM) *</label>
                        <InputNumber
                            id="create_unit_cost"
                            v-model="createForm.unit_cost"
                            mode="currency"
                            currency="MYR"
                            locale="ms-MY"
                            :minFractionDigits="2"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.unit_cost }"
                        />
                        <small v-if="createForm.errors.unit_cost" class="p-error">{{ createForm.errors.unit_cost }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_initial_stock" class="block text-sm font-medium text-gray-700 mb-2">Initial Stock</label>
                        <InputNumber
                            id="create_initial_stock"
                            v-model="createForm.initial_stock"
                            :min="0"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.initial_stock }"
                        />
                        <small v-if="createForm.errors.initial_stock" class="p-error">{{ createForm.errors.initial_stock }}</small>
                        <small class="text-gray-500">Starting stock quantity for this item</small>
                    </div>

                    <div class="field">
                        <label for="create_status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <Dropdown
                            id="create_status"
                            v-model="createForm.status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select status"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.status }"
                        />
                        <small v-if="createForm.errors.status" class="p-error">{{ createForm.errors.status }}</small>
                    </div>
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
        <Dialog v-model:visible="showEditModal" modal header="Edit Stationary Item" :style="{ width: '500px' }"
                :closable="!editForm.processing">
            <div class="space-y-4">
                <div class="field">
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <Textarea
                        id="edit_description"
                        v-model="editForm.description"
                        rows="2"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.description }"
                        placeholder="Enter item description"
                    />
                    <small v-if="editForm.errors.description" class="p-error">{{ editForm.errors.description }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_unit" class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                        <Dropdown
                            id="edit_unit"
                            v-model="editForm.unit"
                            :options="unitOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select unit"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.unit }"
                        />
                        <small v-if="editForm.errors.unit" class="p-error">{{ editForm.errors.unit }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_unit_cost" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost (RM) *</label>
                        <InputNumber
                            id="edit_unit_cost"
                            v-model="editForm.unit_cost"
                            mode="currency"
                            currency="MYR"
                            locale="ms-MY"
                            :minFractionDigits="2"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.unit_cost }"
                        />
                        <small v-if="editForm.errors.unit_cost" class="p-error">{{ editForm.errors.unit_cost }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <Dropdown
                        id="edit_status"
                        v-model="editForm.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select status"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.status }"
                    />
                    <small v-if="editForm.errors.status" class="p-error">{{ editForm.errors.status }}</small>
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
            <div
                class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center"
            >
                <Button
                    label="Create New Item"
                    icon="pi pi-plus"
                    class="p-button-success p-button-sm"
                    @click="openCreateModal"
                />
                <InputText
                    v-model="search"
                    placeholder="Search items..."
                    class="w-full p-inputtext-sm sm:w-64"
                />
            </div>

            <DataTable
                :value="items.data"
                showGridlines
                :rowHover="true"
                paginator
                lazy
                :rows="items.per_page"
                :totalRecords="items.total"
                :first="(items.current_page - 1) * items.per_page"
                @page="onPageChange"
                responsiveLayout="scroll"
                tableStyle="min-width: 70rem"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">
                            No items found
                        </p>
                        <p class="text-sm text-gray-400">
                            Try adjusting your filters or add a new item.
                        </p>
                    </div>
                </template>

                <!-- Table Columns -->
                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{
                            (items.current_page - 1) * items.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>

                <Column field="description" header="Description" style="min-width: 200px" />
                <Column field="unit" header="Unit" style="width: 80px" />
                <Column field="unit_cost" header="Unit Cost" style="width: 120px">
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.unit_cost) }}
                    </template>
                </Column>
                <Column header="Stock In" style="width: 100px">
                    <template #body="slotProps">
                        <span class="text-green-600 font-semibold">
                            {{ slotProps.data.stationary_item_movements[0]?.in || 0 }}
                        </span>
                    </template>
                </Column>
                <Column header="Stock Out" style="width: 100px">
                    <template #body="slotProps">
                        <span class="text-red-600 font-semibold">
                            {{ slotProps.data.stationary_item_movements[0]?.out || 0 }}
                        </span>
                    </template>
                </Column>
                <Column header="Current Stock" style="width: 120px">
                    <template #body="slotProps">
                        <span :class="getStockStatusClass(slotProps.data.stationary_item_movements[0]?.closing_balance || 0)">
                            {{ slotProps.data.stationary_item_movements[0]?.closing_balance || 0 }}
                        </span>
                    </template>
                </Column>
                <Column header="Stock Value" style="width: 130px">
                    <template #body="slotProps">
                        <span class="font-semibold text-blue-600">
                            {{ formatCurrency((slotProps.data.stationary_item_movements[0]?.closing_balance || 0) * (slotProps.data.unit_cost || 0)) }}
                        </span>
                    </template>
                </Column>
                <Column header="Status" style="width: 100px">
                    <template #body="slotProps">
                        <span
                            :class="{
                                'bg-green-100 text-green-800 border border-green-300':
                                    slotProps.data.status,
                                'bg-red-100 text-red-800 border border-red-300':
                                    !slotProps.data.status,
                            }"
                            class="px-3 py-1 text-sm font-semibold rounded-full"
                        >
                            {{ slotProps.data.status ? "Active" : "Inactive" }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 14rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-eye"
                            outlined
                            rounded
                            severity="info"
                            class="mr-2"
                            @click="viewItem(slotProps.data.id)"
                            v-tooltip="'View Details'"
                        />
                        <Button
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="openEditModal(slotProps.data)"
                            v-tooltip="'Edit Item'"
                        />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="deleteItem(slotProps.data.id)"
                            v-tooltip="'Delete Item'"
                        />
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