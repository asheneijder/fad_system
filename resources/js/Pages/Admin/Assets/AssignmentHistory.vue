<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    asset: Object,
    assignments: Object,
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Asset Management', url: route('admin.assets.index') },
    { label: 'Asset Details', url: route('admin.assets.show', props.asset.id) },
    { label: 'Assignment History' }
];

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

const viewUser = (userId) => {
    router.visit(route('admin.users.show', userId));
};

const viewAsset = () => {
    router.visit(route('admin.assets.show', props.asset.id));
};

const goBack = () => {
    router.visit(route('admin.assets.index'));
};

// Computed properties
const totalAssignments = computed(() => props.assignments.total);
const activeAssignment = computed(() => props.assignments.data.find(a => !a.returned_at));
const averageAssignmentDuration = computed(() => {
    const returnedAssignments = props.assignments.data.filter(a => a.returned_at);
    if (returnedAssignments.length === 0) return 0;
    
    const totalDays = returnedAssignments.reduce((sum, assignment) => {
        const start = new Date(assignment.assigned_at);
        const end = new Date(assignment.returned_at);
        return sum + Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    }, 0);
    
    return Math.round(totalDays / returnedAssignments.length);
});
</script>

<template>
    <Head :title="`Assignment History - ${asset.name}`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:text-blue-800" 
                          @click="router.visit(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Assignment History</h1>
                    <p class="mt-1 text-gray-500">
                        Track all assignments for 
                        <span class="font-semibold text-blue-600">{{ asset.name }}</span>
                        ({{ asset.asset_tag }})
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button label="Back to Assets" icon="pi pi-arrow-left" severity="secondary"
                        @click="goBack" />
                    <Button label="View Asset" icon="pi pi-eye" severity="info"
                        @click="viewAsset" />
                </div>
            </div>

            <!-- Asset Summary -->
            <Card>
                <template #content>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Total Assignments</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ totalAssignments }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Current Status</p>
                            <Badge :value="asset.status" 
                                   :severity="asset.status === 'assigned' ? 'info' : 'success'"
                                   class="mt-1 capitalize" />
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Currently Assigned To</p>
                            <p v-if="asset.user" class="mt-1 text-lg font-semibold text-gray-900">
                                {{ asset.user.name }}
                            </p>
                            <Badge v-else value="Not Assigned" severity="secondary" class="mt-1" />
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Avg. Assignment Duration</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ averageAssignmentDuration }} days
                            </p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Assignment History -->
            <Card class="shadow-lg">
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>Assignment Records</span>
                        <Badge :value="totalAssignments" severity="info" />
                    </div>
                </template>
                <template #content>
                    <DataTable :value="assignments.data" showGridlines stripedRows
                        :rowHover="true" paginator :rows="assignments.per_page" 
                        :totalRecords="assignments.total"
                        :first="(assignments.current_page - 1) * assignments.per_page"
                        @page="(event) => router.visit(route('admin.assets.assignment-history', asset.id, { page: event.page + 1 }), { preserveState: true })"
                        responsiveLayout="scroll" tableStyle="min-width: 50rem" 
                        class="p-datatable-custom">

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                    <i class="text-6xl text-gray-400 pi pi-history"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-700">No Assignment History</h3>
                                <p class="mb-4 text-gray-500">
                                    This asset has not been assigned to any users yet.
                                </p>
                            </div>
                        </template>

                        <!-- Columns -->
                        <Column header="#" style="width: 60px;">
                            <template #body="slotProps">
                                <Badge :value="(assignments.current_page - 1) * assignments.per_page + slotProps.index + 1"
                                    severity="secondary" />
                            </template>
                        </Column>

                        <Column header="Assigned To" sortable>
                            <template #body="slotProps">
                                <div class="cursor-pointer" @click="viewUser(slotProps.data.assigned_to)">
                                    <div class="font-semibold text-gray-900 hover:text-blue-600">
                                        {{ slotProps.data.user?.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ slotProps.data.user?.email }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Assigned By" sortable>
                            <template #body="slotProps">
                                <div v-if="slotProps.data.assignedBy" 
                                     class="cursor-pointer" 
                                     @click="viewUser(slotProps.data.assigned_by)">
                                    <div class="font-medium text-gray-900 hover:text-blue-600">
                                        {{ slotProps.data.assignedBy.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatDate(slotProps.data.assigned_at) }}
                                    </div>
                                </div>
                                <div v-else class="text-gray-500">—</div>
                            </template>
                        </Column>

                        <Column header="Assignment Period" sortable style="width: 200px;">
                            <template #body="slotProps">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        {{ formatDate(slotProps.data.assigned_at) }}
                                    </div>
                                    <div v-if="slotProps.data.returned_at" class="text-gray-600">
                                        to {{ formatDate(slotProps.data.returned_at) }}
                                    </div>
                                    <div v-else class="text-green-600 font-medium">
                                        Currently Assigned
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ getAssignmentDuration(slotProps.data) }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status" sortable style="width: 120px;">
                            <template #body="slotProps">
                                <Badge :value="getAssignmentStatus(slotProps.data)"
                                    :severity="getStatusSeverity(slotProps.data)"
                                    class="capitalize" />
                            </template>
                        </Column>

                        <Column header="Condition" style="width: 150px;">
                            <template #body="slotProps">
                                <div class="text-sm">
                                    <div v-if="slotProps.data.condition_assigned" 
                                         class="text-gray-600 truncate" 
                                         :title="slotProps.data.condition_assigned">
                                        Assigned: {{ slotProps.data.condition_assigned }}
                                    </div>
                                    <div v-if="slotProps.data.condition_returned" 
                                         class="text-gray-600 truncate mt-1" 
                                         :title="slotProps.data.condition_returned">
                                        Returned: {{ slotProps.data.condition_returned }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Notes" style="width: 200px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.notes" 
                                     class="text-sm text-gray-600 truncate" 
                                     :title="slotProps.data.notes">
                                    {{ slotProps.data.notes }}
                                </div>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Statistics Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Current Assignment Details -->
                <Card v-if="activeAssignment">
                    <template #title>Current Assignment</template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Assigned To</span>
                                <span class="font-semibold text-gray-900">
                                    {{ activeAssignment.user?.name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Assigned By</span>
                                <span class="font-semibold text-gray-900">
                                    {{ activeAssignment.assignedBy?.name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Assignment Date</span>
                                <span class="font-semibold text-gray-900">
                                    {{ formatDate(activeAssignment.assigned_at) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Duration</span>
                                <span class="font-semibold text-gray-900">
                                    {{ getAssignmentDuration(activeAssignment) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Condition</span>
                                <span class="font-semibold text-gray-900 text-right">
                                    {{ activeAssignment.condition_assigned }}
                                </span>
                            </div>
                            <div v-if="activeAssignment.notes" class="pt-4 border-t">
                                <p class="text-sm font-medium text-gray-500 mb-2">Notes</p>
                                <p class="text-sm text-gray-700">{{ activeAssignment.notes }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Assignment Statistics -->
                <Card>
                    <template #title>Assignment Statistics</template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Total Assignments</span>
                                <span class="font-semibold text-gray-900">{{ totalAssignments }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Active Assignments</span>
                                <Badge :value="assignments.data.filter(a => !a.returned_at).length" 
                                       severity="info" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Completed Assignments</span>
                                <Badge :value="assignments.data.filter(a => a.returned_at).length" 
                                       severity="success" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Average Duration</span>
                                <span class="font-semibold text-gray-900">
                                    {{ averageAssignmentDuration }} days
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">First Assignment</span>
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ assignments.data.length > 0 ? formatDate(assignments.data[assignments.data.length - 1].assigned_at) : '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Latest Assignment</span>
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ assignments.data.length > 0 ? formatDate(assignments.data[0].assigned_at) : '—' }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <template #title>Quick Actions</template>
                <template #content>
                    <div class="flex flex-wrap gap-4">
                        <Button label="Return to Asset Details" icon="pi pi-arrow-left" severity="secondary"
                            @click="viewAsset" />
                        <Button label="Back to Assets List" icon="pi pi-list" severity="secondary"
                            @click="goBack" />
                        <Button v-if="asset.status === 'assigned'" 
                            label="Return Asset" icon="pi pi-arrow-left" severity="warning"
                            :href="route('admin.assets.show', asset.id)" />
                        <Button v-else-if="asset.status === 'available'"
                            label="Assign Asset" icon="pi pi-user-plus" severity="success"
                            :href="route('admin.assets.show', asset.id)" />
                    </div>
                </template>
            </Card>
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
</style>