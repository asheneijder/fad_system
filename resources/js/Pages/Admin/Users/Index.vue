<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Password from "primevue/password";
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Menu from 'primevue/menu';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' })
    },
    statistics: {
        type: Object,
        default: () => ({})
    }
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'User Management' }];

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const users = ref(props.users);
const showUserModal = ref(false);
const isEditMode = ref(false);
const selectedUserId = ref(null);
const selectedUsers = ref([]);
const actionMenu = ref();
const loading = ref(false);

// Form data
const userForm = useForm({
    name: '',
    email: '',
    job_title: '',
    department: '',
    office_location: '',
    password: '',
    password_confirmation: '',
});

// Action menu items
const actionItems = ref([
    {
        label: 'Bulk Actions',
        items: [
            {
                label: 'Reset Passwords',
                icon: 'pi pi-key',
                command: () => bulkResetPassword()
            },
            {
                label: 'Export Selected',
                icon: 'pi pi-download',
                command: () => exportSelected()
            },
            {
                label: 'Delete Selected',
                icon: 'pi pi-trash',
                command: () => bulkDelete()
            }
        ]
    }
]);

// Status options - removed since we don't have status field
const statusOptions = ref([
    { label: 'All Users', value: '' },
    { label: 'With Job Title', value: 'with_job_title' },
    { label: 'Without Job Title', value: 'without_job_title' },
]);

// Watchers
watch(() => props.users, (newUsers) => {
    users.value = newUsers;
}, { immediate: true });

watch([search, statusFilter], ([newSearch, newStatus], [oldSearch, oldStatus]) => {
    if (newSearch !== oldSearch || newStatus !== oldStatus) {
        loading.value = true;
        router.get(route("admin.users.index"), {
            search: newSearch,
            status: newStatus
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            }
        });
    }
});

watch(showUserModal, (val) => {
    if (!val) {
        resetForm();
    }
});

// Methods
const onPageChange = (event) => {
    loading.value = true;
    const page = event.page + 1;
    router.get(route("admin.users.index"), {
        search: search.value,
        status: statusFilter.value,
        page: page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        }
    });
};

const resetForm = () => {
    userForm.reset();
    userForm.clearErrors();
    isEditMode.value = false;
    selectedUserId.value = null;
};

const openCreateModal = () => {
    resetForm();
    isEditMode.value = false;
    userForm.password = 'st@ff!@Rt!';
    userForm.password_confirmation = 'st@ff!@Rt!';
    showUserModal.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    selectedUserId.value = user.id;
    
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.job_title = user.job_title || '';
    userForm.department = user.department || '';
    userForm.office_location = user.office_location || '';
    userForm.password = '';
    userForm.password_confirmation = '';
    
    showUserModal.value = true;
};

const saveUser = () => {
    if (isEditMode.value) {
        userForm.put(route('admin.users.update', selectedUserId.value), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'User updated successfully',
                    life: 3000
                });
                showUserModal.value = false;
                resetForm();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Please check the form for errors',
                    life: 3000
                });
            }
        });
    } else {
        userForm.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'User created successfully',
                    life: 3000
                });
                showUserModal.value = false;
                resetForm();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Please check the form for errors',
                    life: 3000
                });
            }
        });
    }
};

const deleteUser = (user) => {
    confirm.require({
        message: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.users.destroy", user.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "User deleted successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to delete user",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const resetPassword = (user) => {
    confirm.require({
        message: `Are you sure you want to reset ${user.name}'s password to default?`,
        header: "Reset Password Confirmation",
        icon: "pi pi-key",
        accept: () => {
            router.post(route('admin.users.reset-password', user.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Password Reset",
                        detail: "User password reset to default successfully",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to reset password",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const bulkResetPassword = () => {
    if (selectedUsers.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select users first',
            life: 3000
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to reset passwords for ${selectedUsers.value.length} selected user(s)?`,
        header: "Bulk Reset Password Confirmation",
        icon: "pi pi-key",
        accept: () => {
            const form = useForm({
                user_ids: selectedUsers.value.map(user => user.id)
            });

            form.post(route('admin.users.bulk-reset-password'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Passwords Reset",
                        detail: `${selectedUsers.value.length} user password(s) reset successfully`,
                        life: 3000,
                    });
                    selectedUsers.value = [];
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to reset passwords",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const bulkDelete = () => {
    if (selectedUsers.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select users first',
            life: 3000
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete ${selectedUsers.value.length} selected user(s)? This action cannot be undone.`,
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            const form = useForm({
                user_ids: selectedUsers.value.map(user => user.id)
            });

            form.post(route('admin.users.bulk-delete'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: `${selectedUsers.value.length} user(s) deleted successfully`,
                        life: 3000,
                    });
                    selectedUsers.value = [];
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to delete users",
                        life: 3000,
                    });
                }
            });
        },
    });
};

const exportSelected = () => {
    if (selectedUsers.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select users to export',
            life: 3000
        });
        return;
    }

    const userIds = selectedUsers.value.map(user => user.id);
    
    // Create a temporary form to submit the export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.users.export');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    userIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const toggleActionMenu = (event) => {
    actionMenu.value.toggle(event);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const hasJobTitle = (user) => {
    return user.job_title && user.job_title.trim() !== '';
};

// Computed properties for form validation
const isFormValid = computed(() => {
    const baseValid = userForm.name && userForm.email;
    
    if (isEditMode.value) {
        // For edit, password is optional but if provided must match
        if (userForm.password || userForm.password_confirmation) {
            return baseValid && userForm.password === userForm.password_confirmation;
        }
        return baseValid;
    } else {
        // For create, password is required
        return baseValid && 
               userForm.password && 
               userForm.password_confirmation &&
               userForm.password === userForm.password_confirmation;
    }
});
</script>

<template>
    <Head title="User Management" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />
        <Menu ref="actionMenu" :model="actionItems" :popup="true" />

        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                    <p class="mt-1 text-gray-500">Manage system users and their information</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" />
                    <Button label="Create User" icon="pi pi-plus" severity="success"
                        @click="openCreateModal" class="font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Users</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.total || users.total }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="text-xl text-blue-600 pi pi-users"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Users</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.active || statistics.total || '0' }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="text-xl text-green-600 pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">This Month</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.this_month || '0' }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="text-xl text-orange-600 pi pi-calendar"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">With Job Title</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ statistics.with_job_title || '0' }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="text-xl text-purple-600 pi pi-briefcase"></i>
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
                            <Button label="Create User" icon="pi pi-plus" severity="success"
                                @click="openCreateModal" class="font-semibold" />
                            <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                                @click="toggleActionMenu" />
                        </div>

                        <div class="flex flex-col lg:flex-row gap-4">
                            <div class="w-full lg:w-48">
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                    optionValue="value" placeholder="Filter by Job Title" class="w-full" />
                            </div>
                            <div class="w-full lg:w-80">
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Search users..." class="w-full" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Users Info -->
                    <div v-if="selectedUsers.length > 0" class="p-3 mt-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800">
                                {{ selectedUsers.length }} user(s) selected
                            </span>
                            <Button label="Clear" icon="pi pi-times" severity="secondary" text
                                @click="selectedUsers = []" />
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-6">
                        <DataTable :value="users.data" showGridlines stripedRows
                            :rowHover="true" paginator :rows="users.per_page" :totalRecords="users.total"
                            :first="(users.current_page - 1) * users.per_page" @page="onPageChange"
                            v-model:selection="selectedUsers" dataKey="id" :loading="loading"
                            responsiveLayout="scroll" tableStyle="min-width: 50rem" class="p-datatable-custom">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-6xl text-gray-400 pi pi-users"></i>
                                    </div>
                                    <h3 class="mb-2 text-xl font-semibold text-gray-700">No Users Found</h3>
                                    <p class="mb-4 text-gray-500">Try adjusting your search or create a new user.</p>
                                    <Button label="Create First User" icon="pi pi-plus" severity="success"
                                        @click="openCreateModal" />
                                </div>
                            </template>

                            <!-- Loading State -->
                            <template #loading>
                                <div class="flex items-center justify-center py-8">
                                    <i class="pi pi-spin pi-spinner text-2xl text-blue-500"></i>
                                    <span class="ml-2">Loading users...</span>
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 3rem" />

                            <!-- Columns -->
                            <Column header="#" style="width: 60px;">
                                <template #body="slotProps">
                                    <Badge :value="(users.current_page - 1) * users.per_page + slotProps.index + 1"
                                        severity="secondary" />
                                </template>
                            </Column>

                            <Column field="name" header="User Details" sortable>
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">{{ slotProps.data.email }}</div>
                                </template>
                            </Column>

                            <Column header="Job Title" sortable style="width: 150px;">
                                <template #body="slotProps">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.job_title || '—' }}
                                        </div>
                                        <div class="text-gray-500">{{ slotProps.data.department || '—' }}</div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="office_location" header="Location" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge v-if="slotProps.data.office_location" :value="slotProps.data.office_location" severity="info" />
                                    <Badge v-else value="—" severity="secondary" />
                                </template>
                            </Column>

                            <Column header="Job Title Status" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <Badge v-if="hasJobTitle(slotProps.data)" value="Has Job Title" severity="success" />
                                    <Badge v-else value="No Job Title" severity="warning" />
                                </template>
                            </Column>

                            <Column header="Joined" sortable style="width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 180px">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit User'" @click="openEditModal(slotProps.data)" />

                                        <Button icon="pi pi-key" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Reset Password'" @click="resetPassword(slotProps.data)" />

                                        <Button icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete User'" @click="deleteUser(slotProps.data)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Create/Edit User Dialog -->
            <Dialog v-model:visible="showUserModal" modal 
                :header="isEditMode ? 'Edit User' : 'Create New User'" 
                :style="{ width: '700px' }"
                :breakpoints="{ '1199px': '75vw', '575px': '95vw' }"
                :closable="!userForm.processing">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="userForm.name" 
                                placeholder="Enter full name" 
                                class="w-full"
                                :class="{ 'p-invalid': userForm.errors.name }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.name">
                                {{ userForm.errors.name }}
                            </small>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="userForm.email" 
                                type="email"
                                placeholder="Enter email address" 
                                class="w-full"
                                :class="{ 'p-invalid': userForm.errors.email }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.email">
                                {{ userForm.errors.email }}
                            </small>
                        </div>

                        <!-- Job Title -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Job Title</label>
                            <InputText v-model="userForm.job_title" 
                                placeholder="Enter job title" 
                                class="w-full"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Department -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">Department</label>
                            <InputText v-model="userForm.department" 
                                placeholder="Enter department" 
                                class="w-full"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Office Location -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700">Office Location</label>
                            <InputText v-model="userForm.office_location" 
                                placeholder="Enter office location" 
                                class="w-full"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                {{ isEditMode ? 'New Password' : 'Password' }} 
                                <span v-if="!isEditMode" class="text-red-500">*</span>
                            </label>
                            <Password v-model="userForm.password" 
                                :placeholder="isEditMode ? 'Leave blank to keep current' : 'Enter password'"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                :class="{ 'p-invalid': userForm.errors.password }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.password">
                                {{ userForm.errors.password }}
                            </small>
                            <small v-if="!isEditMode" class="text-gray-500 text-xs">
                                Default: st@ff!@Rt!
                            </small>
                            <small v-else class="text-gray-500 text-xs">
                                Leave blank to keep current password
                            </small>
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                {{ isEditMode ? 'Confirm New Password' : 'Confirm Password' }}
                                <span v-if="!isEditMode" class="text-red-500">*</span>
                            </label>
                            <Password v-model="userForm.password_confirmation" 
                                :placeholder="isEditMode ? 'Confirm new password' : 'Confirm password'"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                :class="{ 'p-invalid': userForm.errors.password_confirmation }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.password_confirmation">
                                {{ userForm.errors.password_confirmation }}
                            </small>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showUserModal = false"
                            :disabled="userForm.processing" />
                        <Button :label="isEditMode ? 'Update User' : 'Create User'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveUser"
                            :loading="userForm.processing"
                            :disabled="!isFormValid || userForm.processing" />
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

:deep(.p-password-input) {
    width: 100%;
}
</style>