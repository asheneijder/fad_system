<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Card from "primevue/card";
import Breadcrumb from 'primevue/breadcrumb';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import Checkbox from 'primevue/checkbox';
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    users: Object
});

const toast = useToast();
const globalFilter = ref('');
const showUserDetails = ref(false);
const showBulkReminderDialog = ref(false);
const selectedUser = ref(null);
const selectedUsers = ref([]);
const sendingReminder = ref(false);

const breadcrumbItems = ref([
    { label: 'User Acknowledgment Report' }
]);

const filteredUsers = computed(() => {
    if (!globalFilter.value) return props.users.data;
    
    const filter = globalFilter.value.toLowerCase();
    return props.users.data.filter(user => 
        user.name.toLowerCase().includes(filter) ||
        user.email.toLowerCase().includes(filter) ||
        user.department?.toLowerCase().includes(filter)
    );
});

const usersNeedingReminder = computed(() => {
    return filteredUsers.value.filter(user => getAcknowledgmentRate(user) < 100 && user.pending_assignments > 0);
});

const getAcknowledgmentRate = (user) => {
    if (user.total_assignments === 0) return 0;
    return Math.round((user.acknowledged_assignments / user.total_assignments) * 100);
};

const getAcknowledgmentSeverity = (rate) => {
    if (rate >= 80) return 'success';
    if (rate >= 50) return 'warning';
    return 'danger';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const viewUserDetails = (user) => {
    selectedUser.value = user;
    showUserDetails.value = true;
};

const sendReminder = async (user) => {
    sendingReminder.value = true;
    
    try {
        await router.post(route('admin.users.send-acknowledgment-reminder', user.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Reminder Sent',
                    detail: `Acknowledgment reminder sent to ${user.name}`,
                    life: 3000
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: errors.message || 'Failed to send reminder',
                    life: 3000
                });
            }
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to send reminder',
            life: 3000
        });
    } finally {
        sendingReminder.value = false;
    }
};

const exportReport = () => {
    const csvContent = generateCSV();
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `user-acknowledgment-report-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
};

const generateCSV = () => {
    const headers = ['User Name', 'Email', 'Department', 'Total Assets', 'Acknowledged', 'Pending', 'Returned', 'Acknowledgment Rate', 'Status'];
    const rows = filteredUsers.value.map(user => [
        user.name,
        user.email,
        user.department || 'N/A',
        user.total_assignments,
        user.acknowledged_assignments,
        user.pending_assignments,
        user.returned_assignments,
        `${getAcknowledgmentRate(user)}%`,
        getAcknowledgmentRate(user) >= 80 ? 'Good' : getAcknowledgmentRate(user) >= 50 ? 'Fair' : 'Poor'
    ]);
    
    return [headers, ...rows].map(row => row.join(',')).join('\n');
};

const toggleUserSelection = (user) => {
    const index = selectedUsers.value.indexOf(user.id);
    if (index > -1) {
        selectedUsers.value.splice(index, 1);
    } else {
        selectedUsers.value.push(user.id);
    }
};

const selectAllUsers = () => {
    if (selectedUsers.value.length === usersNeedingReminder.value.length) {
        selectedUsers.value = [];
    } else {
        selectedUsers.value = usersNeedingReminder.value.map(user => user.id);
    }
};
</script>

<template>
    <Head title="User Acknowledgment Report" />
    <AppLayout>
        <Toast />
        
        <!-- User Details Dialog -->
        <Dialog v-model:visible="showUserDetails" modal header="User Assignment Details" 
                :style="{ width: '70rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div v-if="selectedUser" class="space-y-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">User</label>
                        <p class="font-semibold text-gray-900">{{ selectedUser.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Email</label>
                        <p class="font-semibold text-gray-900">{{ selectedUser.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Department</label>
                        <p class="font-semibold text-gray-900">{{ selectedUser.department || 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Acknowledgment Rate</label>
                        <p class="font-semibold text-lg" :class="{
                            'text-green-600': getAcknowledgmentRate(selectedUser) >= 80,
                            'text-orange-600': getAcknowledgmentRate(selectedUser) >= 50 && getAcknowledgmentRate(selectedUser) < 80,
                            'text-red-600': getAcknowledgmentRate(selectedUser) < 50
                        }">
                            {{ getAcknowledgmentRate(selectedUser) }}%
                        </p>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Assignment Summary</h4>
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="p-3 bg-blue-50 rounded">
                            <div class="text-2xl font-bold text-blue-600">{{ selectedUser.total_assignments }}</div>
                            <div class="text-sm text-blue-700">Total</div>
                        </div>
                        <div class="p-3 bg-green-50 rounded">
                            <div class="text-2xl font-bold text-green-600">{{ selectedUser.acknowledged_assignments }}</div>
                            <div class="text-sm text-green-700">Acknowledged</div>
                        </div>
                        <div class="p-3 bg-orange-50 rounded">
                            <div class="text-2xl font-bold text-orange-600">{{ selectedUser.pending_assignments }}</div>
                            <div class="text-sm text-orange-700">Pending</div>
                        </div>
                        <div class="p-3 bg-cyan-50 rounded">
                            <div class="text-2xl font-bold text-cyan-600">{{ selectedUser.returned_assignments }}</div>
                            <div class="text-sm text-cyan-700">Returned</div>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-end gap-2">
                        <Button label="Send Reminder" icon="pi pi-envelope" severity="warning"
                                :loading="sendingReminder"
                                @click="sendReminder(selectedUser)" 
                                v-if="selectedUser.pending_assignments > 0" />
                        <Button label="Close" severity="secondary" @click="showUserDetails = false" />
                    </div>
                </div>
            </div>
        </Dialog>

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :model="breadcrumbItems" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:underline text-sm sm:text-base" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                <div class="w-full sm:w-auto">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">User Acknowledgment Report</h1>
                    <p class="text-gray-600 mt-2">Monitor user acknowledgment status for assigned assets</p>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button label="Export CSV" icon="pi pi-download" severity="secondary" outlined
                        @click="exportReport"
                        class="flex-1 sm:flex-initial" size="small" />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4">
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600">{{ users.total }}</div>
                            <div class="text-xs sm:text-sm text-blue-700">Total Users</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-green-50 border-green-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-green-600">
                                {{ users.data.filter(user => getAcknowledgmentRate(user) >= 80).length }}
                            </div>
                            <div class="text-xs sm:text-sm text-green-700">Good Compliance</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-orange-50 border-orange-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600">
                                {{ users.data.filter(user => getAcknowledgmentRate(user) < 80 && getAcknowledgmentRate(user) >= 50).length }}
                            </div>
                            <div class="text-xs sm:text-sm text-orange-700">Needs Attention</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-red-50 border-red-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-red-600">
                                {{ users.data.filter(user => getAcknowledgmentRate(user) < 50).length }}
                            </div>
                            <div class="text-xs sm:text-sm text-red-700">Low Compliance</div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="bg-yellow-50 border-yellow-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-yellow-600">
                                {{ usersNeedingReminder.length }}
                            </div>
                            <div class="text-xs sm:text-sm text-yellow-700">Users Needing Reminders</div>
                        </div>
                    </template>
                </Card>
                
                <Card class="bg-orange-50 border-orange-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600">
                                {{ usersNeedingReminder.reduce((sum, user) => sum + user.pending_assignments, 0) }}
                            </div>
                            <div class="text-xs sm:text-sm text-orange-700">Total Pending Acknowledgments</div>
                        </div>
                    </template>
                </Card>
                
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600">
                                {{ Math.round(usersNeedingReminder.reduce((sum, user) => sum + getAcknowledgmentRate(user), 0) / (usersNeedingReminder.length || 1)) }}%
                            </div>
                            <div class="text-xs sm:text-sm text-blue-700">Avg Rate (Needing Attention)</div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- User Acknowledgment Table -->
            <Card class="shadow-lg">
                <template #content>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800">User Acknowledgment Status</h3>
                            <p class="text-gray-600 mt-1 text-sm">Track acknowledgment compliance across all users</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-search text-gray-400"></i>
                            <InputText v-model="globalFilter" placeholder="Search users..." class="w-full lg:w-80" />
                        </div>
                    </div>

                    <DataTable :value="filteredUsers"
                        :paginator="true"
                        :rows="10"
                        :rowsPerPageOptions="[5, 10, 20, 50]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <Column field="name" header="User" :sortable="true" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-900">{{ data.name }}</div>
                                        <div class="text-xs text-600">{{ data.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="department" header="Department" :sortable="true" style="min-width: 150px">
                            <template #body="{ data }">
                                {{ data.department || '—' }}
                            </template>
                        </Column>

                        <Column header="Total Assets" :sortable="true" style="min-width: 120px">
                            <template #body="{ data }">
                                <Badge :value="data.total_assignments" severity="info" />
                            </template>
                        </Column>

                        <Column header="Acknowledged" :sortable="true" style="min-width: 130px">
                            <template #body="{ data }">
                                <Badge :value="data.acknowledged_assignments" severity="success" />
                            </template>
                        </Column>

                        <Column header="Pending" :sortable="true" style="min-width: 120px">
                            <template #body="{ data }">
                                <Badge :value="data.pending_assignments" 
                                       severity="warning" 
                                       v-if="data.pending_assignments > 0" />
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>

                        <Column header="Acknowledgment Rate" :sortable="true" style="min-width: 180px">
                            <template #body="{ data }">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span>{{ getAcknowledgmentRate(data) }}%</span>
                                    </div>
                                    <ProgressBar :value="getAcknowledgmentRate(data)" 
                                                :showValue="false"
                                                :class="`p-progressbar-${getAcknowledgmentSeverity(getAcknowledgmentRate(data))}`" />
                                    <Tag :value="getAcknowledgmentRate(data) >= 80 ? 'Good' : getAcknowledgmentRate(data) >= 50 ? 'Fair' : 'Poor'"
                                         :severity="getAcknowledgmentSeverity(getAcknowledgmentRate(data))"
                                         class="text-xs" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 150px">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" size="small"
                                        @click="viewUserDetails(data)"
                                        severity="info" 
                                        v-tooltip="'View Details'" />
                                    <Button icon="pi pi-envelope" size="small"
                                        @click="sendReminder(data)"
                                        :loading="sendingReminder"
                                        severity="warning"
                                        v-tooltip="'Send Reminder'"
                                        v-if="data.pending_assignments > 0" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar-success .p-progressbar-value) {
    background-color: #22c55e;
}

:deep(.p-progressbar-warning .p-progressbar-value) {
    background-color: #f59e0b;
}

:deep(.p-progressbar-danger .p-progressbar-value) {
    background-color: #ef4444;
}
</style>