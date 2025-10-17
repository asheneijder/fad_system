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
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    permission: Object,
    roles: Array,
});

const confirm = useConfirm();

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
                    <Card v-if="roles && roles.length > 0">
                        <template #title>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="pi pi-users text-green-500"></i>
                                    <span>Assigned Roles ({{ roles.length }})</span>
                                </div>
                                <Badge :value="roles.length" severity="info" />
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="roles" 
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
                        </template>
                    </Card>

                    <!-- Empty State for Roles -->
                    <Card v-else>
                        <template #content>
                            <div class="text-center py-8">
                                <i class="pi pi-users text-4xl text-gray-300 mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-900">No Roles Assigned</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    This permission is not assigned to any roles. Users won't have access to this permission.
                                </p>
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
            <Card>
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