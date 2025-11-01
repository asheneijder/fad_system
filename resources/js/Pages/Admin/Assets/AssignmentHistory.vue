<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    asset: Object,
    assignments: Object,
});

const confirm = useConfirm();
const toast = useToast(); // Add this line

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Asset Management', url: route('admin.assets.index') },
    { label: 'Assignment History' }
];

const showAssignmentDialog = ref(false);
const selectedAssignment = ref(null);

// Computed properties
const activeAssignment = computed(() => props.assignments.data.find(a => !a.returned_at));
const returnedAssignments = computed(() => props.assignments.data.filter(a => a.returned_at));

// Add acknowledgment statistics
const acknowledgedAssignments = computed(() =>
    props.assignments.data.filter(a => a.acknowledged_at).length
);

const pendingAcknowledgment = computed(() =>
    props.assignments.data.filter(a => !a.returned_at && !a.acknowledged_at).length
);

const cancelAssignment = (assignment) => {
    confirm.require({
        group: 'headless',
        header: 'Cancel Assignment',
        message: `Are you sure you want to permanently cancel and delete this assignment to ${assignment.user?.name}? This action cannot be undone.`,
        accept: () => {
            // Use Inertia to delete the assignment
            router.delete(route('admin.assignments.cancel-acknowledgment', assignment.id), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Assignment Deleted',
                        detail: 'The assignment has been permanently removed.',
                        life: 3000
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: 'error',
                        summary: 'Deletion Failed',
                        detail: 'There was an error deleting the assignment.',
                        life: 3000
                    });
                }
            });
        },
        reject: () => {
            // Optional: handle rejection
        }
    });
};

const averageAssignmentDuration = computed(() => {
    if (returnedAssignments.value.length === 0) return 0;

    const totalDays = returnedAssignments.value.reduce((sum, assignment) => {
        const start = new Date(assignment.assigned_at);
        const end = new Date(assignment.returned_at);
        return sum + Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    }, 0);

    return Math.round(totalDays / returnedAssignments.value.length);
});

const assignmentStats = computed(() => [
    {
        label: 'Total Assignments',
        value: props.assignments.total,
        icon: 'pi pi-history',
        bgColor: 'bg-blue-100',
        textColor: 'text-blue-600',
        valueColor: 'text-blue-600'
    },
    {
        label: 'Active Assignments',
        value: props.assignments.data.filter(a => !a.returned_at).length,
        icon: 'pi pi-user',
        bgColor: 'bg-green-100',
        textColor: 'text-green-600',
        valueColor: 'text-green-600'
    },
    {
        label: 'Acknowledged',
        value: acknowledgedAssignments.value,
        icon: 'pi pi-check-circle',
        bgColor: 'bg-emerald-100',
        textColor: 'text-emerald-600',
        valueColor: 'text-emerald-600'
    },
    {
        label: 'Pending Acknowledgment',
        value: pendingAcknowledgment.value,
        icon: 'pi pi-clock',
        bgColor: 'bg-orange-100',
        textColor: 'text-orange-600',
        valueColor: 'text-orange-600'
    }
]);

// Methods
const formatDate = (date) => {
    if (!date) return '—';
    try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return '—';
    }
};

const getAssignmentDuration = (assignment) => {
    if (!assignment.assigned_at) return '—';

    const startDate = new Date(assignment.assigned_at);
    const endDate = assignment.returned_at ? new Date(assignment.returned_at) : new Date();

    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
        return diffHours <= 1 ? 'Less than 1 hour' : `${diffHours} hours`;
    } else if (diffDays === 1) {
        return '1 day';
    } else {
        return `${diffDays} days`;
    }
};

const getAssignmentStatus = (assignment) => {
    return assignment.returned_at ? 'Returned' : 'Active';
};

const getStatusSeverity = (assignment) => {
    return assignment.returned_at ? 'success' : 'info';
};

// Add acknowledgment status method
const getAcknowledgmentStatus = (assignment) => {
    if (assignment.returned_at) {
        return assignment.acknowledged_at ? 'Acknowledged' : 'Not Acknowledged';
    }
    return assignment.acknowledged_at ? 'Acknowledged' : 'Pending';
};

const getAcknowledgmentSeverity = (assignment) => {
    if (assignment.returned_at) {
        return assignment.acknowledged_at ? 'success' : 'warning';
    }
    return assignment.acknowledged_at ? 'success' : 'warning';
};

const getAcknowledgmentIcon = (assignment) => {
    if (assignment.acknowledged_at) return 'pi pi-check';
    return 'pi pi-clock';
};

const getAssetStatusSeverity = (status) => {
    const map = {
        available: 'success',
        assigned: 'info',
        maintenance: 'warning',
        retired: 'danger'
    };
    return map[status] || 'secondary';
};

const getRowNumber = (index) => {
    return (props.assignments.current_page - 1) * props.assignments.per_page + index + 1;
};

const onPageChange = (event) => {
    const page = event.page + 1;
    router.visit(route('admin.assets.assignment-history', props.asset.id, { page }), {
        preserveState: true,
        preserveScroll: true
    });
};

const viewUser = (userId) => {
    router.visit(route('admin.users.show', userId));
};

const viewAsset = () => {
    router.visit(route('admin.assets.show', props.asset.id));
};

const goBack = () => {
    router.visit(route('admin.assets.index'));
};

const viewAssignmentDetails = (assignment) => {
    selectedAssignment.value = assignment;
    showAssignmentDialog.value = true;
};

const exportAssignments = () => {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = route('admin.assets.assignment-history.export', props.asset.id);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const visit = (url) => {
    router.visit(url);
};
</script>

<template>

    <Head :title="`Assignment History`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:text-blue-800"
                        @click="visit(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Assignment History</h1>
                    <p class="mt-1 text-gray-500">
                        Track all assignments for
                        <span class="font-semibold text-blue-600">{{ asset.name }}</span>
                        ({{ asset.asset_tag_no }})
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Back to Assets" icon="pi pi-arrow-left" severity="secondary" @click="goBack"
                        class="flex-1 lg:flex-none" />
                </div>
            </div>

            <!-- Asset Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="bg-gradient-to-r from-blue-50 to-blue-100 border-0">
                    <template #content>
                        <div class="text-center">
                            <div class="p-3 bg-blue-500 rounded-full inline-flex mb-3">
                                <i class="pi pi-history text-white text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">Total Assignments</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ assignments.total }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-green-50 to-green-100 border-0">
                    <template #content>
                        <div class="text-center">
                            <div class="p-3 bg-green-500 rounded-full inline-flex mb-3">
                                <i class="pi pi-user text-white text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">Current Status</p>
                            <Badge :value="asset.status" :severity="getAssetStatusSeverity(asset.status)"
                                class="mt-1 capitalize text-sm" />
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-emerald-50 to-emerald-100 border-0">
                    <template #content>
                        <div class="text-center">
                            <div class="p-3 bg-emerald-500 rounded-full inline-flex mb-3">
                                <i class="pi pi-check-circle text-white text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">Acknowledged</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ acknowledgedAssignments }}</p>
                        </div>
                    </template>
                </Card>

                <Card class="bg-gradient-to-r from-orange-50 to-orange-100 border-0">
                    <template #content>
                        <div class="text-center">
                            <div class="p-3 bg-orange-500 rounded-full inline-flex mb-3">
                                <i class="pi pi-clock text-white text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">Pending Acknowledgment</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ pendingAcknowledgment }}</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Assignment History Table -->
            <Card class="shadow-lg">
                <template #title>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-xl font-semibold">Assignment Records</span>
                            <Badge :value="assignments.total" severity="info" />
                        </div>
                        <div class="flex gap-2">
                            <Button label="Export CSV" icon="pi pi-download" severity="help" outlined
                                @click="exportAssignments" class="text-sm"
                                v-tooltip="'Export all assignment records to CSV'" />
                        </div>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="assignments.data" showGridlines stripedRows :rowHover="true" paginator
                        :rows="assignments.per_page" :totalRecords="assignments.total"
                        :first="(assignments.current_page - 1) * assignments.per_page" @page="onPageChange"
                        responsiveLayout="scroll"
                        :class="['p-datatable-custom', { 'min-h-[400px]': assignments.data.length > 0 }]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                        :rowsPerPageOptions="[5, 10, 20, 50]">

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="p-4 mb-4 bg-gray-100 rounded-full">
                                    <i class="text-4xl text-gray-400 pi pi-history"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-700">No Assignment History</h3>
                                <p class="mb-6 text-gray-500 max-w-md">
                                    This asset has not been assigned to any users yet.
                                </p>
                                <Button label="Assign This Asset" icon="pi pi-user-plus" severity="success"
                                    @click="viewAsset" />
                            </div>
                        </template>

                        <!-- Columns -->
                        <Column header="#" style="width: 60px;">
                            <template #body="slotProps">
                                <Badge :value="getRowNumber(slotProps.index)" severity="secondary"
                                    class="min-w-[2rem] justify-center" />
                            </template>
                        </Column>

                        <Column header="User" style="min-width: 180px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="pi pi-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-gray-900 truncate hover:text-blue-600 cursor-pointer"
                                            @click="viewUser(slotProps.data.assigned_to)">
                                            {{ slotProps.data.user?.name || 'Unknown User' }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ slotProps.data.user?.email || '—' }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Assignment Details" style="min-width: 250px;">
                            <template #body="slotProps">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-calendar text-gray-400 text-sm"></i>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ formatDate(slotProps.data.assigned_at) }}
                                        </span>
                                    </div>
                                    <div v-if="slotProps.data.assignedBy" class="flex items-center gap-2">
                                        <i class="pi pi-user-edit text-gray-400 text-sm"></i>
                                        <span class="text-sm text-gray-600">
                                            Assigned by
                                            <span class="font-medium hover:text-blue-600 cursor-pointer"
                                                @click="viewUser(slotProps.data.assigned_by)">
                                                {{ slotProps.data.assignedBy.name }}
                                            </span>
                                        </span>
                                    </div>
                                    <div v-if="slotProps.data.condition_assigned" class="flex items-center gap-2">
                                        <i class="pi pi-tag text-gray-400 text-sm"></i>
                                        <span class="text-xs text-gray-500 truncate">
                                            {{ slotProps.data.condition_assigned }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Return Details" style="min-width: 200px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.returned_at" class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-calendar-times text-green-500 text-sm"></i>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ formatDate(slotProps.data.returned_at) }}
                                        </span>
                                    </div>
                                    <div v-if="slotProps.data.condition_returned" class="flex items-center gap-2">
                                        <i class="pi pi-tags text-green-500 text-sm"></i>
                                        <span class="text-xs text-gray-500 truncate">
                                            {{ slotProps.data.condition_returned }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-green-600 font-medium">
                                        {{ getAssignmentDuration(slotProps.data) }}
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <i class="pi pi-clock text-orange-500 text-sm"></i>
                                    <Badge value="Active" severity="info" class="text-xs" />
                                </div>
                            </template>
                        </Column>

                        <!-- Add Acknowledgment Status Column -->
                        <Column header="Acknowledgment" style="width: 160px;">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="[getAcknowledgmentIcon(slotProps.data), 'text-sm',
                                    slotProps.data.acknowledged_at ? 'text-green-500' : 'text-orange-500']"></i>

                                    <Chip :label="getAcknowledgmentStatus(slotProps.data)"
                                        :class="getAcknowledgmentSeverity(slotProps.data)"
                                        class="capitalize text-xs px-2 py-1" />
                                </div>
                            </template>
                        </Column>


                        <Column header="Status" style="width: 120px;">
                            <template #body="slotProps">
                                <Badge :value="getAssignmentStatus(slotProps.data)"
                                    :severity="getStatusSeverity(slotProps.data)" class="capitalize text-xs" />
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 140px;">
                            <template #body="slotProps">
                                <div class="flex justify-center gap-1">
                                    <Button icon="pi pi-eye" outlined rounded severity="info" size="small"
                                        v-tooltip.top="'View Details'" @click="viewAssignmentDetails(slotProps.data)"
                                        class="w-8 h-8" />
                                    
                                    <!-- Cancel/Delete button - only show when acknowledged_at is null (pending) -->
                                   <Button v-if="!slotProps.data.acknowledged_at" 
                                        icon="pi pi-times" outlined rounded severity="danger" size="small"
                                        v-tooltip.top="'Cancel Assignment'" @click="cancelAssignment(slotProps.data)"
                                        class="w-8 h-8" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                    <ConfirmDialog group="headless">
                        <template #container="{ message, acceptCallback, rejectCallback }">
                            <div class="flex flex-col align-items-center p-5 surface-overlay border-round">
                                <div class="border-circle bg-primary inline-flex justify-content-center align-items-center h-6rem w-6rem -mt-8">
                                    <i class="pi pi-question text-5xl"></i>
                                </div>
                                <span class="font-bold text-2xl block mb-2 mt-4">{{ message.header }}</span>
                                <p class="mb-0">{{ message.message }}</p>
                                <div class="flex align-items-center gap-2 mt-4">
                                    <Button label="Cancel" outlined @click="rejectCallback" class="w-8rem"></Button>
                                    <Button label="Yes" @click="acceptCallback" class="w-8rem" severity="danger"></Button>
                                </div>
                            </div>
                        </template>
                    </ConfirmDialog>
                </template>
            </Card>

            <!-- Statistics & Current Assignment -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Current Assignment Card -->
                <Card v-if="activeAssignment" class="border-l-4 border-l-blue-500">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user text-blue-500"></i>
                            <span>Current Assignment</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ activeAssignment.user?.name }}</div>
                                        <div class="text-sm text-gray-500">{{ activeAssignment.user?.email }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <Badge value="Active" severity="info" />
                                    <Badge :value="getAcknowledgmentStatus(activeAssignment)"
                                        :severity="getAcknowledgmentSeverity(activeAssignment)" class="text-xs" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="font-medium text-gray-500">Assigned By</p>
                                    <p class="font-semibold text-gray-900">{{ activeAssignment.assignedBy?.name }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500">Assignment Date</p>
                                    <p class="font-semibold text-gray-900">{{ formatDate(activeAssignment.assigned_at)
                                        }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500">Duration</p>
                                    <p class="font-semibold text-gray-900">{{ getAssignmentDuration(activeAssignment) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500">Acknowledged</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ activeAssignment.acknowledged_at ?
                                            formatDate(activeAssignment.acknowledged_at) : 'Not Yet' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="activeAssignment.condition_assigned" class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500 mb-1">Condition</p>
                                <p class="text-sm text-gray-700">{{ activeAssignment.condition_assigned }}</p>
                            </div>

                            <div v-if="activeAssignment.notes" class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm font-medium text-gray-500 mb-1">Notes</p>
                                <p class="text-sm text-gray-700">{{ activeAssignment.notes }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Assignment Statistics -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-purple-500"></i>
                            <span>Assignment Statistics</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-for="stat in assignmentStats" :key="stat.label"
                                class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div :class="['p-2 rounded-full', stat.bgColor]">
                                        <i :class="[stat.icon, stat.textColor]"></i>
                                    </div>
                                    <span class="font-medium text-gray-700">{{ stat.label }}</span>
                                </div>
                                <span :class="['font-bold', stat.valueColor]">{{ stat.value }}</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Assignment Detail Dialog -->
        <Dialog v-model:visible="showAssignmentDialog" modal header="Assignment Details"
            :style="{ width: '95vw', maxWidth: '600px' }" :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
            <div v-if="selectedAssignment" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Assigned To</label>
                        <p class="mt-1 font-semibold text-gray-900">{{ selectedAssignment.user?.name }}</p>
                        <p class="text-sm text-gray-500">{{ selectedAssignment.user?.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Assigned By</label>
                        <p class="mt-1 font-semibold text-gray-900">{{ selectedAssignment.assignedBy?.name }}</p>
                        <p class="text-sm text-gray-500">{{ selectedAssignment.assignedBy?.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Assignment Date</label>
                        <p class="mt-1 font-semibold text-gray-900">{{ formatDate(selectedAssignment.assigned_at) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Return Date</label>
                        <p class="mt-1 font-semibold text-gray-900">
                            {{ selectedAssignment.returned_at ? formatDate(selectedAssignment.returned_at) : '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <Badge :value="getAssignmentStatus(selectedAssignment)"
                            :severity="getStatusSeverity(selectedAssignment)" class="mt-1 capitalize" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Acknowledgment</label>
                        <div class="mt-1 flex items-center gap-2">
                            <i :class="[getAcknowledgmentIcon(selectedAssignment),
                            selectedAssignment.acknowledged_at ? 'text-green-500' : 'text-orange-500']"></i>
                            <Badge :value="getAcknowledgmentStatus(selectedAssignment)"
                                :severity="getAcknowledgmentSeverity(selectedAssignment)" class="capitalize" />
                        </div>
                        <p v-if="selectedAssignment.acknowledged_at" class="text-xs text-gray-500 mt-1">
                            Acknowledged on {{ formatDate(selectedAssignment.acknowledged_at) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Condition When Assigned</label>
                        <p class="mt-1 text-gray-900">{{ selectedAssignment.condition_assigned || '—' }}</p>
                    </div>
                    <div v-if="selectedAssignment.condition_returned">
                        <label class="block text-sm font-medium text-gray-500">Condition When Returned</label>
                        <p class="mt-1 text-gray-900">{{ selectedAssignment.condition_returned }}</p>
                    </div>
                    <div v-if="selectedAssignment.notes">
                        <label class="block text-sm font-medium text-gray-500">Notes</label>
                        <p class="mt-1 text-gray-900 whitespace-pre-wrap">{{ selectedAssignment.notes }}</p>
                    </div>
                </div>
            </div>
        </Dialog>
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
    font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}

:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    :deep(.p-datatable .p-datatable-wrapper) {
        overflow-x: auto;
    }
}
</style>