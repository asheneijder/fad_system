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
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

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
const currentPerPage = ref(props.movements?.per_page || 10);

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
    currentPerPage.value = newMovements?.per_page || 10;
}, { immediate: true });

watch([search, typeFilter], ([newSearch, newType]) => {
    router.get(route("admin.stationary-items.movements", props.stationaryItem.id), {
        search: newSearch,
        type: newType,
        page: 1,
        per_page: currentPerPage.value
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
});

// Methods
const onPageChange = (event) => {
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.stationary-items.movements", props.stationaryItem.id), {
        search: search.value,
        type: typeFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
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
                preserveState: false,
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

        <div class="p-3 sm:p-4 md:p-6 space-y-4 md:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-2 sm:mb-4">
                <template #item="{ item }">
                    <a v-if="item.url" :href="item.url" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
                        {{ item.label }}
                    </a>
                    <span v-else class="font-semibold text-gray-700 text-xs sm:text-sm">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div class="flex items-start gap-2 sm:gap-3">
                    <Button icon="pi pi-arrow-left" 
                            severity="secondary" 
                            outlined 
                            @click="router.visit(route('admin.stationary-items.index'))"
                            size="small" />
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Movement History</h1>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 truncate">
                            Stock movements for: <strong>{{ stationaryItem.name }}</strong>
                            <span v-if="stationaryItem.sku" class="ml-1 text-blue-600">({{ stationaryItem.sku }})</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined
                        @click="router.visit(route('admin.stationary-items.index'))" class="text-xs sm:text-sm" />
                    <Button label="Record" icon="pi pi-plus" severity="success"
                        @click="openMovementDialog" class="text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Item Summary -->
            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 shadow-sm">
                <template #content>
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4">
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 truncate">Current Stock</p>
                            <p class="mt-1 text-base sm:text-lg md:text-2xl font-bold text-gray-900">
                                {{ stationaryItem.current_stock }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">{{ stationaryItem.unit }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 truncate">Minimum Stock</p>
                            <p class="mt-1 text-base sm:text-lg md:text-2xl font-bold text-gray-900">{{ stationaryItem.min_stock }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 truncate">Cost Price</p>
                            <p class="mt-1 text-base sm:text-lg md:text-2xl font-bold text-gray-900 truncate">
                                {{ formatCurrency(stationaryItem.cost_price) }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 truncate">Stock Value</p>
                            <p class="mt-1 text-base sm:text-lg md:text-2xl font-bold text-gray-900 truncate">
                                {{ formatCurrency(stationaryItem.current_stock * stationaryItem.cost_price) }}
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Movement Statistics -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Movements</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-history"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Stock In</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-green-600">+{{ statistics.stockIn }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-arrow-down-left"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Stock Out</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-red-600">-{{ statistics.stockOut }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-red-600 pi pi-arrow-up-right"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Adjustments</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.adjustments }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-yellow-600 pi pi-sliders-h"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Net Change</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold" 
                                   :class="statistics.netChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ statistics.netChange >= 0 ? '+' : '' }}{{ statistics.netChange }}
                                </p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-chart-line"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Main Content Card -->
            <Card class="shadow-lg">
                <template #content>
                    <!-- Toolbar -->
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-3">
                            <Button label="Record Movement" icon="pi pi-plus" severity="success"
                                @click="openMovementDialog" class="text-xs sm:text-sm" />

                            <div class="flex flex-col gap-2 w-full sm:w-auto sm:flex-row">
                                <div class="w-full sm:w-48">
                                    <Select v-model="typeFilter" :options="movementTypes" optionLabel="label" 
                                        optionValue="value" placeholder="Filter by type" class="w-full text-xs sm:text-sm" />
                                </div>
                                <IconField iconPosition="left" class="w-full sm:w-64 md:w-80">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search movements..." 
                                        class="w-full text-xs sm:text-sm" />
                                </IconField>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="movements.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="movements.per_page" 
                            :totalRecords="movements.total"
                            :first="(movements.current_page - 1) * movements.per_page" 
                            @page="onPageChange"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['reference', 'notes']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-history"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Movement Records Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center">No stock movements recorded for this item yet.</p>
                                    <Button label="Record First Movement" icon="pi pi-plus" severity="success"
                                        @click="openMovementDialog" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(movements.current_page - 1) * movements.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column header="Type" sortable style="min-width: 110px;">
                                <template #body="slotProps">
                                    <Badge :value="getMovementTypeLabel(slotProps.data.type)"
                                        :severity="getMovementTypeSeverity(slotProps.data.type)"
                                        class="capitalize text-xs sm:text-sm" />
                                </template>
                            </Column>

                            <Column header="Qty Change" sortable style="min-width: 100px;">
                                <template #body="slotProps">
                                    <div class="flex items-center gap-1 sm:gap-2">
                                        <Badge :value="getQuantityChange(slotProps.data)"
                                            :severity="getQuantitySeverity(slotProps.data)" class="text-xs" />
                                        <span class="text-xs text-gray-500">{{ stationaryItem.unit }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Stock Levels" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="text-gray-600 font-medium">
                                            {{ slotProps.data.previous_stock }} → {{ slotProps.data.new_stock }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ stationaryItem.unit }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Reference" sortable style="min-width: 110px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600 truncate">
                                        {{ slotProps.data.reference || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Date" sortable style="min-width: 130px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-900 whitespace-nowrap">
                                        {{ formatDate(slotProps.data.movement_date) }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Notes" style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600 truncate">
                                        {{ slotProps.data.notes || '—' }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 70px;">
                                <template #body="slotProps">
                                    <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                        v-tooltip.top="'Delete'" 
                                        @click="deleteMovement(slotProps.data)" 
                                        class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ movements.current_page }} of {{ Math.ceil(movements.total / movements.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Record Movement Dialog -->
            <Dialog v-model:visible="showMovementDialog" modal header="Record Movement" 
                :style="{ width: '95vw', maxWidth: '500px' }"
                :breakpoints="{ '1199px': '75vw', '640px': '95vw' }">
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Type</label>
                        <Select v-model="movementForm.type" :options="[
                            { label: 'Stock In', value: 'in' },
                            { label: 'Stock Out', value: 'out' },
                            { label: 'Adjustment', value: 'adjustment' },
                            { label: 'Return', value: 'return' }
                        ]" optionLabel="label" optionValue="value" class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Quantity</label>
                        <InputNumber v-model="movementForm.quantity" 
                            :min="0" 
                            class="w-full text-xs sm:text-sm"
                            :class="{ 'p-invalid': movementForm.errors.quantity }" />
                        <small class="text-red-500 text-xs" v-if="movementForm.errors.quantity">
                            {{ movementForm.errors.quantity }}
                        </small>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Reference</label>
                        <InputText v-model="movementForm.reference" 
                            placeholder="Enter reference (PO, invoice, etc.)" 
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Movement Date</label>
                        <Calendar v-model="movementForm.movement_date" 
                            dateFormat="yy-mm-dd" 
                            showIcon
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700">Notes</label>
                        <Textarea v-model="movementForm.notes" rows="3" placeholder="Add notes about this movement..."
                            class="w-full text-xs sm:text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" severity="secondary" outlined 
                            @click="showMovementDialog = false"
                            :disabled="movementForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button label="Record" icon="pi pi-check" severity="success" 
                            @click="recordMovement" :loading="movementForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    color: #495057;
    border-color: #dee2e6;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 0.875rem;
    }
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

:deep(.p-inputtext),
:deep(.p-button),
:deep(.p-dropdown),
:deep(.p-calendar),
:deep(.p-inputnumber-input) {
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
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    :deep(.p-dialog .p-dialog-content) {
        padding: 1.5rem;
    }
}

:deep(.p-paginator) {
    flex-wrap: wrap;
    gap: 0.5rem;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 0 0 0.375rem 0.375rem;
}

@media (min-width: 640px) {
    :deep(.p-paginator) {
        padding: 1rem;
    }
}

:deep(.p-paginator .p-paginator-pages) {
    flex-wrap: wrap;
}

:deep(.p-paginator-current) {
    font-size: 0.75rem;
    align-self: center;
}

@media (min-width: 640px) {
    :deep(.p-paginator-current) {
        font-size: 0.875rem;
    }
}

:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        font-size: 0.875rem;
    }
}

:deep(.p-badge) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-badge) {
        font-size: 0.875rem;
    }
}

/* Fix search icon alignment */
:deep(.p-input-icon-left > i:first-of-type) {
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    position: absolute;
}

:deep(.p-input-icon-left > .p-inputtext) {
    padding-left: 2.5rem;
}

:deep(.p-input-icon-left) {
    position: relative;
    display: block;
}

@media (max-width: 640px) {
    :deep(.p-datatable .p-datatable-wrapper) {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    :deep(.p-button.p-button-icon-only) {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    :deep(.p-paginator .p-paginator-pages) {
        width: 100%;
    }
}
</style>