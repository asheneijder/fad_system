<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Calendar from "primevue/calendar";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    licenses: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const licenses = ref(props.licenses);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Status options
const statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];

// Form data
const createForm = useForm({
    license_name: '',
    product_key: '',
    expiration_date: null,
    min_qty: 1,
    available_qty: 0,
    status: true,
    description: '',
});

const editForm = useForm({
    id: null,
    license_name: '',
    product_key: '',
    expiration_date: null,
    min_qty: 1,
    available_qty: 0,
    status: true,
    description: '',
});

watch(() => props.licenses, (newLicenses) => {
    licenses.value = newLicenses;
});

watch(search, (newSearch) => {
    router.get(route("admin.licenses.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.licenses.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

// Create functions
const openCreateModal = () => {
    createForm.reset();
    createForm.status = true;
    createForm.min_qty = 1;
    createForm.available_qty = 0;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route("admin.licenses.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "License created successfully",
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
const openEditModal = (license) => {
    editForm.id = license.id;
    editForm.license_name = license.license_name;
    editForm.product_key = license.product_key;
    editForm.expiration_date = license.expiration_date ? new Date(license.expiration_date) : null;
    editForm.min_qty = license.min_qty || 1;
    editForm.available_qty = license.available_qty || 0;
    editForm.status = license.status;
    editForm.description = license.description || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route("admin.licenses.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "License updated successfully",
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
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(() => {
            toast.add({
                severity: 'info',
                summary: 'Copied',
                detail: 'Product key copied to clipboard',
                life: 2000,
            });
        }).catch(() => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to copy to clipboard',
                life: 2000,
            });
        });
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Unsupported',
            detail: 'Clipboard not supported in this browser',
            life: 2000,
        });
    }
};

// Generate random product key
const generateProductKey = (form) => {
    const segments = [];
    for (let i = 0; i < 4; i++) {
        const segment = Math.random().toString(36).substr(2, 4).toUpperCase();
        segments.push(segment);
    }
    const productKey = segments.join('-');
    
    if (form === 'create') {
        createForm.product_key = productKey;
    } else {
        editForm.product_key = productKey;
    }
    
    toast.add({
        severity: 'success',
        summary: 'Generated',
        detail: 'Product key generated successfully',
        life: 2000,
    });
};

// Computed properties for form validation
const isCreateFormValid = computed(() => {
    return createForm.license_name && 
           createForm.product_key && 
           createForm.expiration_date &&
           createForm.min_qty >= 0 &&
           createForm.available_qty >= 0;
});

const isEditFormValid = computed(() => {
    return editForm.license_name && 
           editForm.product_key && 
           editForm.expiration_date &&
           editForm.min_qty >= 0 &&
           editForm.available_qty >= 0;
});

// Helper functions
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const isExpired = (dateString) => {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
};

const isExpiringSoon = (dateString) => {
    if (!dateString) return false;
    const expiryDate = new Date(dateString);
    const today = new Date();
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(today.getDate() + 30);
    
    return expiryDate > today && expiryDate <= thirtyDaysFromNow;
};

const getExpiryStatus = (dateString) => {
    if (isExpired(dateString)) {
        return { class: 'text-red-600 font-semibold', text: 'Expired' };
    } else if (isExpiringSoon(dateString)) {
        return { class: 'text-orange-600 font-semibold', text: 'Expires Soon' };
    }
    return { class: 'text-green-600', text: formatDate(dateString) };
};

const getStockStatus = (available, minimum) => {
    if (available <= 0) return 'text-red-600 font-semibold';
    if (available <= minimum) return 'text-orange-600 font-semibold';
    return 'text-green-600';
};
</script>

<template>
    <Head title="Licenses" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Create Modal -->
        <Dialog v-model:visible="showCreateModal" modal header="Create New License" :style="{ width: '600px' }" 
                :closable="!createForm.processing">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_license_name" class="block text-sm font-medium text-gray-700 mb-2">License Name *</label>
                        <InputText
                            id="create_license_name"
                            v-model="createForm.license_name"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.license_name }"
                            placeholder="Enter license name"
                            autofocus
                        />
                        <small v-if="createForm.errors.license_name" class="p-error">{{ createForm.errors.license_name }}</small>
                    </div>

                    <div class="field">
                        <label for="create_expiration_date" class="block text-sm font-medium text-gray-700 mb-2">Expiration Date *</label>
                        <Calendar
                            id="create_expiration_date"
                            v-model="createForm.expiration_date"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.expiration_date }"
                            placeholder="Select expiration date"
                            :minDate="new Date()"
                            dateFormat="dd/mm/yy"
                            showIcon
                        />
                        <small v-if="createForm.errors.expiration_date" class="p-error">{{ createForm.errors.expiration_date }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="create_product_key" class="block text-sm font-medium text-gray-700 mb-2">Product Key *</label>
                    <div class="flex gap-2">
                        <InputText
                            id="create_product_key"
                            v-model="createForm.product_key"
                            class="flex-1"
                            :class="{ 'p-invalid': createForm.errors.product_key }"
                            placeholder="Enter or generate product key"
                        />
                        <Button 
                            label="Generate" 
                            icon="pi pi-refresh" 
                            @click="generateProductKey('create')"
                            class="p-button-outlined"
                            type="button"
                        />
                    </div>
                    <small v-if="createForm.errors.product_key" class="p-error">{{ createForm.errors.product_key }}</small>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="field">
                        <label for="create_min_qty" class="block text-sm font-medium text-gray-700 mb-2">Minimum Quantity</label>
                        <InputNumber
                            id="create_min_qty"
                            v-model="createForm.min_qty"
                            :min="0"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.min_qty }"
                        />
                        <small v-if="createForm.errors.min_qty" class="p-error">{{ createForm.errors.min_qty }}</small>
                    </div>

                    <div class="field">
                        <label for="create_available_qty" class="block text-sm font-medium text-gray-700 mb-2">Available Quantity</label>
                        <InputNumber
                            id="create_available_qty"
                            v-model="createForm.available_qty"
                            :min="0"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.available_qty }"
                        />
                        <small v-if="createForm.errors.available_qty" class="p-error">{{ createForm.errors.available_qty }}</small>
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

                <div class="field">
                    <label for="create_description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Textarea
                        id="create_description"
                        v-model="createForm.description"
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.description }"
                        placeholder="Enter license description (optional)"
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
        <Dialog v-model:visible="showEditModal" modal header="Edit License" :style="{ width: '600px' }"
                :closable="!editForm.processing">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_license_name" class="block text-sm font-medium text-gray-700 mb-2">License Name *</label>
                        <InputText
                            id="edit_license_name"
                            v-model="editForm.license_name"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.license_name }"
                            placeholder="Enter license name"
                        />
                        <small v-if="editForm.errors.license_name" class="p-error">{{ editForm.errors.license_name }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_expiration_date" class="block text-sm font-medium text-gray-700 mb-2">Expiration Date *</label>
                        <Calendar
                            id="edit_expiration_date"
                            v-model="editForm.expiration_date"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.expiration_date }"
                            placeholder="Select expiration date"
                            dateFormat="dd/mm/yy"
                            showIcon
                        />
                        <small v-if="editForm.errors.expiration_date" class="p-error">{{ editForm.errors.expiration_date }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="edit_product_key" class="block text-sm font-medium text-gray-700 mb-2">Product Key *</label>
                    <div class="flex gap-2">
                        <InputText
                            id="edit_product_key"
                            v-model="editForm.product_key"
                            class="flex-1"
                            :class="{ 'p-invalid': editForm.errors.product_key }"
                            placeholder="Enter or generate product key"
                        />
                        <Button 
                            label="Generate" 
                            icon="pi pi-refresh" 
                            @click="generateProductKey('edit')"
                            class="p-button-outlined"
                            type="button"
                        />
                    </div>
                    <small v-if="editForm.errors.product_key" class="p-error">{{ editForm.errors.product_key }}</small>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="field">
                        <label for="edit_min_qty" class="block text-sm font-medium text-gray-700 mb-2">Minimum Quantity</label>
                        <InputNumber
                            id="edit_min_qty"
                            v-model="editForm.min_qty"
                            :min="0"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.min_qty }"
                        />
                        <small v-if="editForm.errors.min_qty" class="p-error">{{ editForm.errors.min_qty }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_available_qty" class="block text-sm font-medium text-gray-700 mb-2">Available Quantity</label>
                        <InputNumber
                            id="edit_available_qty"
                            v-model="editForm.available_qty"
                            :min="0"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.available_qty }"
                        />
                        <small v-if="editForm.errors.available_qty" class="p-error">{{ editForm.errors.available_qty }}</small>
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

                <div class="field">
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <Textarea
                        id="edit_description"
                        v-model="editForm.description"
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.description }"
                        placeholder="Enter license description (optional)"
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
                <Button label="Create New License" icon="pi pi-plus" class="p-button-success p-button-sm"
                    @click="openCreateModal" />
                <InputText v-model="search" placeholder="Search licenses..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable :value="licenses.data" showGridlines :rowHover="true" paginator lazy :rows="licenses.per_page"
                :totalRecords="licenses.total" :first="(licenses.current_page - 1) * licenses.per_page"
                @page="onPageChange" responsiveLayout="scroll" tableStyle="min-width: 70rem">

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No licenses found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or add a new license.</p>
                    </div>
                </template>

                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{ (licenses.current_page - 1) * licenses.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="license_name" header="Name" style="min-width: 150px" />
                <Column header="Product Key" style="min-width: 200px">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-1 font-mono text-sm bg-blue-100 text-blue-800 border border-blue-300 rounded truncate max-w-[200px]">
                                {{ slotProps.data.product_key }}
                            </span>
                            <Button icon="pi pi-copy" class="p-button-text p-button-sm"
                                @click="copyToClipboard(slotProps.data.product_key)" 
                                v-tooltip="'Copy to clipboard'" />
                        </div>
                    </template>
                </Column>
                <Column header="Expiration Date" style="min-width: 140px">
                    <template #body="slotProps">
                        <span :class="getExpiryStatus(slotProps.data.expiration_date).class">
                            {{ getExpiryStatus(slotProps.data.expiration_date).text }}
                        </span>
                    </template>
                </Column>
                <Column field="min_qty" header="Min Qty" style="width: 80px" />
                <Column header="Available" style="width: 100px">
                    <template #body="slotProps">
                        <span :class="getStockStatus(slotProps.data.available_qty, slotProps.data.min_qty)">
                            {{ slotProps.data.available_qty || 0 }}
                        </span>
                    </template>
                </Column>
                <Column field="status" header="Status" style="width: 100px">
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
                            @click="viewLicense(slotProps.data.id)" 
                            v-tooltip="'View Details'" />
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="openEditModal(slotProps.data)" 
                            v-tooltip="'Edit License'" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="deleteLicense(slotProps.data.id)" 
                            v-tooltip="'Delete License'" />
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