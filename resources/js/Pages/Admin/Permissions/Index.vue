<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, reactive } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";
import usePermissions from '@/composables/usePermissions'; 

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const confirm = useConfirm();
const search = ref(props.filters.search);
const dataTable = ref();
const selectedPermissions = ref([]);
const createModalVisible = ref(false);
const editModalVisible = ref(false);
const selectedPermission = ref(null);
const { hasPermission } = usePermissions();

const form = reactive({
    name: '',
    guard_name: 'web',
});

const editForm = reactive({
    name: '',
});

// Watch for search changes and update URL
watch(search, (newSearch) => {
    const filters = {};
    if (newSearch) filters.search = newSearch;
    
    router.get(route('admin.permissions.index'), filters, {
        preserveState: true,
        replace: true
    });
});

const openCreateModal = () => {
    form.name = '';
    form.guard_name = 'web';
    createModalVisible.value = true;
};

const openEditModal = (permission) => {
    selectedPermission.value = permission;
    editForm.name = permission.name;
    editModalVisible.value = true;
};

const closeCreateModal = () => {
    createModalVisible.value = false;
    form.name = '';
    form.guard_name = 'web';
};

const closeEditModal = () => {
    editModalVisible.value = false;
    selectedPermission.value = null;
    editForm.name = '';
};

const submitCreate = () => {
    router.post(route('admin.permissions.store'), form, {
        onSuccess: () => {
            closeCreateModal();
        },
        preserveScroll: true,
    });
};

const submitEdit = () => {
    if (!selectedPermission.value) return;
    
    router.put(route('admin.permissions.update', selectedPermission.value.id), editForm, {
        onSuccess: () => {
            closeEditModal();
        },
        preserveScroll: true,
    });
};

const confirmDelete = (permission) => {
    confirm.require({
        message: `Are you sure you want to delete "${permission.name}"?`,
        header: 'Delete Permission',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.permissions.destroy', permission.id), {
                preserveScroll: true,
            });
        }
    });
};

const bulkDelete = () => {
    if (selectedPermissions.value.length === 0) return;

    confirm.require({
        message: `Are you sure you want to delete ${selectedPermissions.value.length} selected permission(s)?`,
        header: 'Bulk Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('admin.permissions.bulk-destroy'), {
                ids: selectedPermissions.value.map(permission => permission.id)
            }, {
                preserveScroll: true,
            });
        }
    });
};

const getRoleBadgeSeverity = (count) => {
    if (count === 0) return 'secondary';
    if (count === 1) return 'info';
    if (count <= 3) return 'warning';
    return 'success';
};

const clearFilters = () => {
    search.value = '';
};
</script>

<template>
    <Head title="Permissions" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">System Permissions</h1>
                    <p class="mt-1 text-gray-500">Manage all system permissions and access controls</p>
                </div>
                <Button v-if="hasPermission('can.create.permission')"
                        label="Create Permission" icon="pi pi-plus" @click="openCreateModal" />
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Permissions</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900">{{ permissions.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="pi pi-key text-blue-500 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Permissions</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ permissions.data.filter(p => p.roles_count > 0).length }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="pi pi-check-circle text-green-500 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Unassigned</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ permissions.data.filter(p => p.roles_count === 0).length }}
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="pi pi-times-circle text-orange-500 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <template #title>
                    <span>Search & Filter</span>
                </template>
                <template #content>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <InputText v-model="search" 
                                      placeholder="Search permissions by name..." 
                                      class="w-full sm:w-80" />
                        </div>
                        <div class="flex space-x-2">
                            <Button v-if="search" 
                                    label="Clear" 
                                    icon="pi pi-times" 
                                    severity="secondary" 
                                    outlined 
                                    @click="clearFilters" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Main Content -->
            <Card>
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>All Permissions</span>
                        <div class="flex space-x-2">
                            <Button v-if="selectedPermissions.length > 0 && hasPermission('can.delete.permission')" 
                                    label="Delete Selected" 
                                    icon="pi pi-trash" 
                                    severity="danger" 
                                    outlined
                                    @click="bulkDelete" />
                        </div>
                    </div>
                </template>
                <template #content>
                    <DataTable ref="dataTable"
                            :value="permissions.data"
                            :paginator="true"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            dataKey="id"
                            selectionMode="multiple"
                            v-model:selection="selectedPermissions"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} permissions">
                        
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        
                        <Column field="name" header="Permission Name" sortable>
                            <template #body="{ data }">
                                <div class="flex items-center space-x-3">
                                    <i class="pi pi-key text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ data.name }}</div>
                                        <div class="text-sm text-gray-500">Guard: {{ data.guard_name }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="roles_count" header="Role Assignment" sortable>
                            <template #body="{ data }">
                                <div class="flex items-center space-x-2">
                                    <Badge :value="data.roles_count" 
                                          :severity="getRoleBadgeSeverity(data.roles_count)" />
                                    <span class="text-sm text-gray-600">
                                        {{ data.roles_count === 0 ? 'No roles' : 'role(s)' }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="created_at" header="Created Date" sortable>
                            <template #body="{ data }">
                                <div class="text-sm text-gray-900">
                                    {{ new Date(data.created_at).toLocaleDateString() }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ new Date(data.created_at).toLocaleTimeString() }}
                                </div>
                            </template>
                        </Column>
                        
                        <Column header="Actions" headerStyle="width: 14rem;">
                            <template #body="{ data }">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" 
                                            severity="info" 
                                            outlined 
                                            size="small"
                                            v-tooltip="'View details'"
                                            @click="router.visit(route('admin.permissions.show', data.id))" />
                                    <Button v-if="hasPermission('can.edit.permission')"
                                            icon="pi pi-pencil" 
                                            severity="secondary" 
                                            outlined 
                                            size="small"
                                            v-tooltip="'Edit permission'"
                                            @click="openEditModal(data)" />
                                    <Button v-if="hasPermission('can.delete.permission')"
                                            icon="pi pi-trash" 
                                            severity="danger" 
                                            outlined 
                                            size="small"
                                            v-tooltip="data.roles_count > 0 ? 'Remove from roles first' : 'Delete permission'"
                                            @click="confirmDelete(data)" 
                                            :disabled="data.roles_count > 0" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Info Card -->
            <Card>
                <template #content>
                    <div class="flex items-start space-x-4">
                        <i class="pi pi-info-circle text-blue-500 text-xl mt-1"></i>
                        <div>
                            <h4 class="font-medium text-gray-900">About System Permissions</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                Permissions define what actions users can perform in the system. They are assigned to roles, 
                                and users inherit permissions through their assigned roles. This ensures centralized 
                                and maintainable access control.
                            </p>
                            <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li>Permissions are granular actions (e.g., users.create, posts.delete)</li>
                                <li>Assign permissions to roles, not directly to users</li>
                                <li>Use descriptive names with dot notation for organization</li>
                                <li>Cannot delete permissions that are assigned to roles</li>
                            </ul>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Create Permission Modal -->
        <Dialog v-model:visible="createModalVisible" 
                modal 
                header="Create New Permission" 
                :style="{ width: '500px' }"
                :closable="false">
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Permission Name *
                    </label>
                    <InputText id="name"
                            v-model="form.name"
                            placeholder="e.g., users.create, posts.delete"
                            class="w-full"
                            required />
                    <p class="mt-1 text-sm text-gray-500">
                        Use descriptive names with dot notation for better organization
                    </p>
                </div>

                <div>
                    <label for="guard_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Guard Name
                    </label>
                    <InputText id="guard_name"
                            v-model="form.guard_name"
                            placeholder="web"
                            class="w-full" />
                    <p class="mt-1 text-sm text-gray-500">
                        Typically "web" for web applications
                    </p>
                </div>
            </div>
            
            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="closeCreateModal" />
                    <Button label="Create Permission" 
                            icon="pi pi-check" 
                            :disabled="!form.name"
                            @click="submitCreate" />
                </div>
            </template>
        </Dialog>

        <!-- Edit Permission Modal -->
        <Dialog v-model:visible="editModalVisible" 
                modal 
                :header="`Edit Permission: ${selectedPermission?.name}`" 
                :style="{ width: '500px' }"
                :closable="false">
            <div class="space-y-4">
                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-2">
                        Permission Name *
                    </label>
                    <InputText id="edit-name"
                            v-model="editForm.name"
                            placeholder="e.g., users.create, posts.delete"
                            class="w-full"
                            required />
                    <p class="mt-1 text-sm text-gray-500">
                        Update the permission name
                    </p>
                </div>

                <div v-if="selectedPermission" class="p-3 bg-blue-50 rounded-lg">
                    <div class="text-sm text-blue-700">
                        <div><strong>Current Guard:</strong> {{ selectedPermission.guard_name }}</div>
                        <div><strong>Assigned to:</strong> {{ selectedPermission.roles_count }} role(s)</div>
                    </div>
                </div>
            </div>
            
            <template #footer>
                <div class="flex justify-end space-x-2">
                    <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="closeEditModal" />
                    <Button label="Update Permission" 
                            icon="pi pi-check" 
                            :disabled="!editForm.name || editForm.name === selectedPermission?.name"
                            @click="submitEdit" />
                </div>
            </template>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<style scoped>
:deep(.p-datatable) {
    font-size: 0.875rem;
}

:deep(.p-column-title) {
    font-weight: 600;
}

:deep(.p-badge) {
    min-width: 2rem;
}

:deep(.p-dialog) {
    border-radius: 0.5rem;
}

:deep(.p-dialog-header) {
    border-bottom: 1px solid #e5e7eb;
    padding: 1.25rem;
}

:deep(.p-dialog-content) {
    padding: 1.25rem;
}

:deep(.p-dialog-footer) {
    border-top: 1px solid #e5e7eb;
    padding: 1.25rem;
}
</style>