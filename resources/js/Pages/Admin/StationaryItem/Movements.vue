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
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    stationaryItem: {
        type: Object,
        default: () => ({})
    },
    movements: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '' })
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = ref([
    { label: 'Stationary Items', url: route('admin.stationary-items.index') },
    { label: 'Movement History' }
]);

const search = ref(props.filters?.search || "");
const typeFilter = ref(props.filters?.type || "");
const movements = ref(props.movements);
const showMovementDialog = ref(false);
const selectedMovement = ref(null);

// Movement form
const movementForm = useForm({
    type: 'in',
    quantity: 0,
    reference: '',
    notes: '',
    movement_date: new Date(),
});

// Movement types
const movementTypes = ref([
    { label: 'All Types', value: '' },
    { label: 'Stock In', value: 'in' },
    { label: 'Stock Out', value: 'out' },
    { label: 'Adjustment', value: 'adjustment' },
    { label: 'Return', value: 'return' }
]);

// Statistics
const statistics = computed(() => {
    const data = movements.value?.data || [];
    
    return {
        total: movements.value?.total || 0,
        stockIn: data.filter(m => m.type === 'in').reduce((sum, m) => sum + m.quantity, 0),
        stockOut: data.filter(m => m.type === 'out').reduce((sum, m) => sum + m.quantity, 0),
        adjustments: data.filter(m => m.type === 'adjustment').length,
        returns: data.filter(m => m.type === 'return').length,
        netChange: data.filter(m => m.type === 'in' || m.type === 'return').reduce((sum, m) => sum + m.quantity, 0) -
                  data.filter(m => m.type === 'out').reduce((sum, m) => sum + m.quantity, 0)
    };
});

// Watchers
watch(() => props.movements, (newMovements) => {
    movements.value = newMovements;
}, { immediate: true });

watch([search, typeFilter], ([newSearch, newType], [oldSearch, oldType]) => {
    if (newSearch !== oldSearch || newType !== oldType) {
        router.get(route("admin.stationary-items.movements", props.stationaryItem.id), {
            search: newSearch,
            type: newType
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    router.get(route("admin.stationary-items.movements", props.stationaryItem.id), {
        search: search.value,
        type: typeFilter.value,
        page: page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const openMovementDialog = () => {
    movementForm.reset();
    movementForm.type = 'in';
    movementForm.quantity = 0;
    movementForm.reference = '';
    movementForm.notes = '';
    movementForm.movement_date = new Date();
    showMovementDialog.value = true;
};

const recordMovement = () => {
    movementForm.post(route('admin.stationary-items.record-movement', props.stationaryItem.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Movement recorded successfully',
                life: 3000
            });
            showMovementDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.quantity?.[0] || 'Failed to record movement',
                life: 3000
            });
        }
    });
};

const deleteMovement = (movement) => {
    confirm.require({
        message: "Are you sure you want to delete this movement record? This will also revert the stock change.",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.stationary-movements.destroy", movement.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "Movement record deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete movement",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const getMovementTypeSeverity = (type) => {
    const severityMap = {
        'in': 'success',
        'out': 'danger',
        'adjustment': 'warning',
        'return': 'info'
    };
    return severityMap[type] || 'secondary';
};

const getMovementTypeLabel = (type) => {
    const labelMap = {
        'in': 'Stock In',
        'out': 'Stock Out',
        'adjustment': 'Adjustment',
        'return': 'Return'
    };
    return labelMap[type] || type;
};

const getQuantityChange = (movement) => {
    switch (movement.type) {
        case 'in':
        case 'return':
            return `+${movement.quantity}`;
        case 'out':
            return `-${movement.quantity}`;
        case 'adjustment':
            return `${movement.new_stock - movement.previous_stock >= 0 ? '+' : ''}${movement.new_stock - movement.previous_stock}`;
        default:
            return movement.quantity;
    }
};

const getQuantitySeverity = (movement) => {
    switch (movement.type) {
        case 'in':
        case 'return':
            return 'success';
        case 'out':
            return 'danger';
        case 'adjustment':
            return movement.new_stock >= movement.previous_stock ? 'success' : 'danger';
        default:
            return 'info';
    }
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (amount) => {
    return 'RM ' + new Intl.NumberFormat('ms-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
};
</script>

<template>
    <Head :title="`Movement History - ${stationaryItem.name}`" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <a v-if="item.url" :href="item.url" class="text-blue-600 hover:text-blue-800">
                        {{ item.label }}
                    </a>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <Button icon="pi pi-arrow-left" 
                                severity="secondary" 
                                outlined 
                                @click="router.visit(route('admin.stationary-items.index'))" />
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Movement History</h1>
                            <p class="mt-1 text-gray-500">
                                Stock movements for: <strong>{{ stationaryItem.name }}</strong>
                                <span v-if="stationaryItem.sku" class="ml-2 text-blue-600">({{ stationaryItem.sku }})</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Back to Items" icon="pi pi-box" severity="secondary" outlined
                        @click="router.visit(route('admin.stationary-items.index'))" />
                    <Button label="Record Movement" icon="pi pi-plus" severity="success"
                        @click="openMovementDialog" class="font-semibold" />
                </div>
            </div>

            <!-- Item Summary -->
            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200">
                <template #content>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Current Stock</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ stationaryItem.current_stock }} {{ stationaryItem.unit }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Minimum Stock</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ stationaryItem.min_stock }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Cost Price</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ formatCurrency(stationaryItem.cost_price) }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Stock Value</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ formatCurrency(stationaryItem.current_stock * stationaryItem.cost_price) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Movement Statistics -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Movements</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-history"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Stock In</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">+{{ statistics.stockIn }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-arrow-down-left"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Stock Out</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">-{{ statistics.stockOut }}</p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="text-xl text-red-600 pi pi-arrow-up-right"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Adjustments</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.adjustments }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="text-xl text-yellow-600 pi pi-sliders-h"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Net Change</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900" 
                                   :class="statistics.netChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ statistics.netChange >= 0 ? '+' : '' }}{{ statistics.netChange }}
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-chart-line"></i>
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
                            <Button label="Record Movement" icon="pi pi-plus" severity="success"
                                @click="openMovementDialog" class="font-semibold" />
                        </div>

                        <div class="flex flex-col lg:flex-row gap-4">
                            <div class="w-full lg:w-48">
                                <Select v-model="typeFilter" :options="movementTypes" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by type" class="w-full" />
                            </div>
                            <div class="w-full lg:w-80">
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search movements..." class="w-full" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="movements.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="movements.per_page" :totalRecords="movements.total"
                            :first="(movements.current_page - 1) * movements.per_page" @page="onPageChange"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-history"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Movement Records Found</h3>
                                    <p class="mb-4 text-gray-500">No stock movements recorded for this item yet.</p>
                                    <Button label="Record First Movement" icon="pi pi-plus" severity="success"
                                        @click="openMovementDialog" />
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(movements.current_page - 1) * movements.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column header="Movement Type" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge :value="getMovementTypeLabel(slotProps.data.type)"
                                        :severity="getMovementTypeSeverity(slotProps.data.type)"
                                        class="capitalize" />
                                </template>
                            </Column>

                            <Column header="Quantity Change" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="flex items-center space-x-2">
                                        <Badge :value="getQuantityChange(slotProps.data)"
                                            :severity="getQuantitySeverity(slotProps.data)" />
                                        <span class="text-sm text-gray-500">{{ stationaryItem.unit }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Stock Levels" sortable style="width: 140px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="text-gray-600">
                                            {{ slotProps.data.previous_stock }} → {{ slotProps.data.new_stock }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ stationaryItem.unit }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="reference" header="Reference" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ slotProps.data.reference || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="movement_date" header="Date" sortable style="width: 150px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-900">
                                        {{ formatDate(slotProps.data.movement_date) }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="notes" header="Notes" style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ slotProps.data.notes || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="width: 100px;">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete Movement'" 
                                            @click="deleteMovement(slotProps.data)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Record Movement Dialog -->
            <Dialog v-model:visible="showMovementDialog" modal header="Record Movement" :style="{ width: '500px' }"
                :breakpoints="{ '1199px': '50vw', '575px': '90vw' }">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="movementForm.type" :options="[
                            { label: 'Stock In', value: 'in' },
                            { label: 'Stock Out', value: 'out' },
                            { label: 'Adjustment', value: 'adjustment' },
                            { label: 'Return', value: 'return' }
                        ]" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="movementForm.quantity" 
                            :min="0" 
                            class="w-full"
                            :class="{ 'p-invalid': movementForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="movementForm.errors.quantity">
                            {{ movementForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Reference</label>
                        <InputText v-model="movementForm.reference" 
                            placeholder="Enter reference (PO, invoice, etc.)" 
                            class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="movementForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="movementForm.notes" rows="3" placeholder="Add notes about this movement..."
                            class="w-full" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showMovementDialog = false"
                            :disabled="movementForm.processing" />
                        <Button label="Record Movement" icon="pi pi-check" severity="success" 
                            @click="recordMovement" :loading="movementForm.processing" />
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

:deep(.p-calendar) {
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