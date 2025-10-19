<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import { useConfirm } from "primevue/useconfirm";
import usePermissions from '@/Composables/usePermissions'; 

const props = defineProps({
    permission: Object,
    roles: Array,
    availableRoles: Array,
});

const confirm = useConfirm();
const showAssignRolesDialog = ref(false);
const selectedRoles = ref([]); 
const { hasPermission } = usePermissions();

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getRoleBadgeSeverity = (usersCount) => {
    if (usersCount === 0) return 'secondary';
    if (usersCount <= 5) return 'info';
    if (usersCount <= 20) return 'warning';
    return 'success';
};

const removeRoleFromPermission = (role) => {
    confirm.require({
        message: `Are you sure you want to remove the "${role.name}" role from this permission?`,
        header: 'Remove Role',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('admin.permissions.remove-role', {
                permission: props.permission.id,
                role: role.id
            }), {}, {
                preserveScroll: true,
            });
        }
    });
};

const confirmDelete = (permission) => {
    confirm.require({
        message: `Are you sure you want to delete "${permission.name}"? This action cannot be undone.`,
        header: 'Delete Permission',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.permissions.destroy', permission.id), {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit(route('admin.permissions.index'));
                }
            });
        }
    });
};

const openAssignRolesDialog = () => {
    selectedRoles.value = [];
    showAssignRolesDialog.value = true;
};

const assignRoles = () => {
    if (selectedRoles.value.length === 0) return;

    router.post(route('admin.permissions.assign-roles', props.permission.id), {
        role_ids: selectedRoles.value // Now this is just an array of IDs
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignRolesDialog.value = false;
            selectedRoles.value = [];
        }
    });
};
</script>

<template>
    <Head :title="`Permission: ${permission.name}`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <Button icon="pi pi-arrow-left" 
                                severity="secondary" 
                                outlined 
                                @click="router.visit(route('admin.permissions.index'))" />
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Permission Details</h1>
                            <p class="mt-1 text-gray-500">View and manage permission information</p>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Button label="Back to List" 
                            icon="pi pi-list" 
                            severity="primary" 
                            outlined 
                            @click="router.visit(route('admin.permissions.index'))" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Permission Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Permission Information Card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center space-x-2">
                                <i class="pi pi-key text-blue-500"></i>
                                <span>Permission Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Permission Name</label>
                                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ permission.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Guard Name</label>
                                        <p class="mt-1 text-lg font-semibold text-gray-900">
                                            <Badge :value="permission.guard_name" severity="info" />
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Created Date</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(permission.created_at) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(permission.updated_at) }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Role Assignment</label>
                                    <div class="mt-2 flex items-center space-x-2">
                                        <Badge :value="permission.roles_count" 
                                              :severity="permission.roles_count > 0 ? 'success' : 'secondary'" />
                                        <span class="text-sm text-gray-600">
                                            {{ permission.roles_count === 0 ? 'Not assigned to any roles' : 
                                               permission.roles_count === 1 ? 'Assigned to 1 role' : 
                                               `Assigned to ${permission.roles_count} roles` }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Assigned Roles Card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="pi pi-users text-green-500"></i>
                                    <span>Assigned Roles ({{ roles.length }})</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Badge :value="roles.length" severity="info" />
                                    <Button label="Assign Roles" 
                                            icon="pi pi-plus" 
                                            severity="success" 
                                            size="small"
                                            @click="openAssignRolesDialog" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <DataTable v-if="roles && roles.length > 0" 
                                     :value="roles" 
                                     dataKey="id"
                                     :rows="10"
                                     :paginator="true"
                                     paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                     currentPageReportTemplate="Showing {first} to {last} of {totalRecords} roles">
                                
                                <Column field="name" header="Role Name" sortable>
                                    <template #body="{ data }">
                                        <div class="flex items-center space-x-2">
                                            <i class="pi pi-shield text-blue-500"></i>
                                            <span class="font-medium text-gray-900">{{ data.name }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="users_count" header="Users" sortable>
                                    <template #body="{ data }">
                                        <Badge :value="data.users_count || 0" 
                                              :severity="getRoleBadgeSeverity(data.users_count || 0)" />
                                    </template>
                                </Column>

                                <Column field="guard_name" header="Guard">
                                    <template #body="{ data }">
                                        <Tag :value="data.guard_name" severity="info" />
                                    </template>
                                </Column>

                                <Column header="Actions" headerStyle="width: 100px;">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-times" 
                                                severity="danger" 
                                                outlined 
                                                size="small"
                                                v-tooltip="'Remove from permission'"
                                                @click="removeRoleFromPermission(data)" />
                                    </template>
                                </Column>
                            </DataTable>

                            <!-- Empty State for Roles -->
                            <div v-else class="text-center py-8">
                                <i class="pi pi-users text-4xl text-gray-300 mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-900">No Roles Assigned</h3>
                                <p class="mt-1 text-sm text-gray-500 mb-4">
                                    This permission is not assigned to any roles. Users won't have access to this permission.
                                </p>
                                <Button label="Assign Roles" 
                                        icon="pi pi-plus" 
                                        severity="success"
                                        @click="openAssignRolesDialog" />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right Column - Actions & Info -->
                <div class="space-y-6">
                    <!-- Quick Actions Card -->
                    <Card>
                        <template #title>
                            <span>Quick Actions</span>
                        </template>
                        <template #content>
                            <div class="space-y-2">
                                <Button label="Assign Roles" 
                                        icon="pi pi-user-plus" 
                                        severity="success" 
                                        class="w-full justify-start"
                                        @click="openAssignRolesDialog" />
                                <Button label="Edit Permission" 
                                        icon="pi pi-pencil" 
                                        severity="secondary" 
                                        class="w-full justify-start"
                                        @click="router.visit(route('admin.permissions.index'))" />
                            </div>
                        </template>
                    </Card>

                    <!-- Permission Usage Card -->
                    <Card>
                        <template #title>
                            <span>Usage Statistics</span>
                        </template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total Roles</span>
                                    <Badge :value="permission.roles_count" 
                                          :severity="permission.roles_count > 0 ? 'success' : 'secondary'" />
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Estimated Users</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ roles.reduce((total, role) => total + (role.users_count || 0), 0) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Available Roles</span>
                                    <Badge :value="availableRoles.length" severity="info" />
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Created</span>
                                    <span class="text-sm text-gray-900">
                                        {{ new Date(permission.created_at).toLocaleDateString() }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Last Updated</span>
                                    <span class="text-sm text-gray-900">
                                        {{ new Date(permission.updated_at).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Danger Zone -->
            <Card v-if="hasPermission('can.delete.permission')">
                <template #title>
                    <span class="text-red-600">Danger Zone</span>
                </template>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Delete Permission</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                Once deleted, this permission cannot be recovered. Make sure no roles are using this permission.
                            </p>
                        </div>
                        <Button label="Delete Permission" 
                                icon="pi pi-trash" 
                                severity="danger" 
                                :disabled="permission.roles_count > 0"
                                @click="confirmDelete(permission)" />
                    </div>
                    <div v-if="permission.roles_count > 0" class="mt-2 p-3 bg-orange-50 rounded-lg">
                        <p class="text-sm text-orange-700">
                            <i class="pi pi-exclamation-triangle mr-1"></i>
                            Cannot delete permission. It is currently assigned to {{ permission.roles_count }} role(s).
                            Remove it from all roles first.
                        </p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Assign Roles Dialog -->
        <Dialog v-model:visible="showAssignRolesDialog" 
                modal 
                header="Assign Roles to Permission" 
                :style="{ width: '500px' }">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Roles to Assign
                    </label>
                    <MultiSelect v-model="selectedRoles" 
                                :options="availableRoles" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Select roles..."
                                display="chip"
                                class="w-full"
                                :maxSelectedLabels="3">
                        <template #option="slotProps">
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center space-x-2">
                                    <i class="pi pi-shield text-blue-500"></i>
                                    <span>{{ slotProps.option.name }}</span>
                                </div>
                                <Badge :value="slotProps.option.users_count || 0" 
                                      severity="info" 
                                      size="small" />
                            </div>
                        </template>
                    </MultiSelect>
                </div>
                
                <div v-if="availableRoles.length === 0" class="p-4 bg-yellow-50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <i class="pi pi-info-circle text-yellow-600"></i>
                        <p class="text-sm text-yellow-700">
                            All available roles already have this permission assigned.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <Button label="Cancel" 
                            severity="secondary" 
                            @click="showAssignRolesDialog = false" />
                    <Button label="Assign Roles" 
                            icon="pi pi-check" 
                            severity="success" 
                            :disabled="selectedRoles.length === 0"
                            @click="assignRoles" />
                </div>
            </div>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<style scoped>
:deep(.p-card) {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

:deep(.p-card-body) {
    padding: 1.5rem;
}

:deep(.p-card-title) {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

:deep(.p-datatable) {
    font-size: 0.875rem;
}

:deep(.p-column-title) {
    font-weight: 600;
}
</style>