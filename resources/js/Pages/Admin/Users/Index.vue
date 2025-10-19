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
import MultiSelect from 'primevue/multiselect';
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Menu from 'primevue/menu';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import usePermissions from '@/Composables/usePermissions'; 

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 10, current_page: 1 })
    },
    roles: {
        type: Array,
        default: () => []
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
const currentPerPage = ref(props.users?.per_page || 10);
const { hasPermission } = usePermissions();

// Form data
const userForm = useForm({
    name: '',
    email: '',
    job_title: '',
    department: '',
    office_location: '',
    password: '',
    password_confirmation: '',
    role_ids: [],
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

// Status options
const statusOptions = ref([
    { label: 'All Users', value: '' },
    { label: 'With Roles', value: 'with_roles' },
    { label: 'Without Roles', value: 'without_roles' },
    { label: 'With Job Title', value: 'with_job_title' },
    { label: 'Without Job Title', value: 'without_job_title' },
]);

// Statistics with computed properties
const statistics = computed(() => {
    const data = users.value?.data || [];
    
    return {
        total: users.value?.total || 0,
        withRoles: data.filter(u => u.roles && u.roles.length > 0).length,
        withoutRoles: data.filter(u => !u.roles || u.roles.length === 0).length,
        withJobTitle: data.filter(u => u.job_title && u.job_title.trim() !== '').length,
        withoutJobTitle: data.filter(u => !u.job_title || u.job_title.trim() === '').length,
        withDepartment: data.filter(u => u.department && u.department.trim() !== '').length,
        recentUsers: data.filter(u => {
            const created = new Date(u.created_at);
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            return created >= thirtyDaysAgo;
        }).length,
    };
});

// Watchers
watch(() => props.users, (newUsers) => {
    users.value = newUsers;
    currentPerPage.value = newUsers?.per_page || 10;
}, { immediate: true });

watch(search, (newSearch, oldSearch) => {
    if (newSearch !== oldSearch) {
        router.get(route("admin.users.index"), {
            search: newSearch,
            status: statusFilter.value,
            page: 1,
            per_page: currentPerPage.value
        }, {
            preserveState: false,
            replace: true,
            preserveScroll: true
        });
    }
});

watch(statusFilter, (newStatus, oldStatus) => {
    if (newStatus !== oldStatus) {
        router.get(route("admin.users.index"), {
            search: search.value,
            status: newStatus,
            page: 1,
            per_page: currentPerPage.value
        }, {
            preserveState: false,
            replace: true,
            preserveScroll: true
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
    const page = event.page + 1;
    const perPage = event.rows;
    currentPerPage.value = perPage;
    
    router.get(route("admin.users.index"), {
        search: search.value,
        status: statusFilter.value,
        page: page,
        per_page: perPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
    });
};

const onRowsPerPageChange = (newPerPage) => {
    currentPerPage.value = newPerPage;
    router.get(route("admin.users.index"), {
        search: search.value,
        status: statusFilter.value,
        page: 1,
        per_page: newPerPage
    }, {
        preserveState: false,
        replace: true,
        preserveScroll: true
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
    userForm.role_ids = user.roles ? user.roles.map(role => role.id) : [];
    
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
                preserveState: false,
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
                preserveState: false,
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

const getUserRoles = (user) => {
    if (!user.roles || user.roles.length === 0) return [];
    return user.roles.map(role => role.name);
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

        <div class="p-3 sm:p-4 md:p-6 space-y-4 md:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-2 sm:mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-xs sm:text-sm">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">User Management</h1>
                    <p class="mt-1 text-xs sm:text-sm text-gray-500">Manage system users and their information</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Bulk Actions" icon="pi pi-cog" severity="secondary" outlined
                        @click="toggleActionMenu" class="flex-1 min-w-fit text-xs sm:text-sm" />
                    <Button v-if="hasPermission('can.create.user')"
                        label="Create User" icon="pi pi-plus" severity="success"
                        @click="openCreateModal" class="flex-1 min-w-fit text-xs sm:text-sm font-semibold" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3 md:gap-4">
                <Card class="border-l-4 border-blue-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Total Users</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-blue-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-blue-600 pi pi-users"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">With Roles</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.withRoles }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-green-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-green-600 pi pi-shield"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">No Roles</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.withoutRoles }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-red-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-red-600 pi pi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-yellow-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">With Job Title</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.withJobTitle }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-yellow-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-yellow-600 pi pi-briefcase"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">With Department</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.withDepartment }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-orange-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-orange-600 pi pi-building"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-purple-500 shadow-md">
                    <template #content>
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">Recent (30d)</p>
                                <p class="mt-1 text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ statistics.recentUsers }}</p>
                            </div>
                            <div class="p-2 sm:p-3 bg-purple-100 rounded-full flex-shrink-0">
                                <i class="text-base sm:text-lg md:text-xl text-purple-600 pi pi-clock"></i>
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
                            <div class="flex flex-wrap gap-1 sm:gap-2 w-full sm:w-auto">
                                <Button v-if="hasPermission('can.create.user')"
                                    label="Create" icon="pi pi-plus" severity="success"
                                    @click="openCreateModal" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                                <Button label="Actions" icon="pi pi-cog" severity="secondary" outlined
                                    @click="toggleActionMenu" class="flex-1 sm:flex-none text-xs sm:text-sm" />
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                                <div class="w-full sm:w-48">
                                    <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" 
                                        optionValue="value" placeholder="Filter by status" 
                                        class="w-full text-xs sm:text-sm" />
                                </div>
                                <div class="w-full sm:w-64 md:w-80">
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-search" />
                                        <InputText v-model="search" placeholder="Search users..." 
                                            class="w-full text-xs sm:text-sm" />
                                    </IconField>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Users Info -->
                        <div v-if="selectedUsers.length > 0" class="p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs sm:text-sm font-medium text-blue-800">
                                    {{ selectedUsers.length }} user(s) selected
                                </span>
                                <Button label="Clear" icon="pi pi-times" severity="secondary" text size="small"
                                    @click="selectedUsers = []" class="text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="mt-4 sm:mt-6 overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                        <DataTable :value="users.data" showGridlines stripedRows
                            :rowHover="true" 
                            paginator 
                            :rows="users.per_page" 
                            :totalRecords="users.total"
                            :first="(users.current_page - 1) * users.per_page" 
                            @page="onPageChange"
                            v-model:selection="selectedUsers" 
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50, 100, 200, 500]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                            responsiveLayout="scroll" 
                            class="p-datatable-custom"
                            :globalFilterFields="['name', 'email', 'job_title', 'department']">

                            <!-- Empty State -->
                            <template #empty>
                                <div class="flex flex-col items-center justify-center py-8 sm:py-12">
                                    <div class="p-3 sm:p-6 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-4xl sm:text-6xl text-gray-400 pi pi-users"></i>
                                    </div>
                                    <h3 class="mb-2 text-base sm:text-lg md:text-xl font-semibold text-gray-700">No Users Found</h3>
                                    <p class="mb-4 text-xs sm:text-sm text-gray-500 text-center px-2">Try adjusting your search or create a new user.</p>
                                    <Button label="Create First User" icon="pi pi-plus" severity="success"
                                        @click="openCreateModal" size="small" class="text-xs sm:text-sm" />
                                </div>
                            </template>

                            <!-- Selection Column -->
                            <Column selectionMode="multiple" headerStyle="width: 2.5rem" />

                            <!-- Columns -->
                            <Column header="#" style="min-width: 50px;">
                                <template #body="slotProps">
                                    <Badge :value="(users.current_page - 1) * users.per_page + slotProps.index + 1"
                                        severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column field="name" header="User Details" sortable style="min-width: 200px;">
                                <template #body="slotProps">
                                    <div class="font-semibold text-gray-900 text-xs sm:text-sm break-words">{{ slotProps.data.name }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ slotProps.data.email }}</div>
                                </template>
                            </Column>

                            <Column header="Roles" style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="role in getUserRoles(slotProps.data)" 
                                               :key="role" 
                                               :value="role" 
                                               severity="info" 
                                               class="text-xs" />
                                        <Badge v-if="getUserRoles(slotProps.data).length === 0" 
                                               value="No Roles" 
                                               severity="secondary" 
                                               class="text-xs" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Job Title" sortable style="min-width: 150px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ slotProps.data.job_title || '—' }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ slotProps.data.department || 'No department' }}
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="office_location" header="Location" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <Badge v-if="slotProps.data.office_location" 
                                        :value="slotProps.data.office_location" 
                                        severity="info" 
                                        class="text-xs" />
                                    <Badge v-else value="—" severity="secondary" class="text-xs" />
                                </template>
                            </Column>

                            <Column header="Joined" sortable style="min-width: 120px;">
                                <template #body="slotProps">
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        {{ formatDate(slotProps.data.created_at) }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Actions -->
                            <Column header="Actions" style="min-width: 140px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 flex-wrap">
                                        <Button icon="pi pi-pencil" outlined rounded severity="warning" size="small"
                                            v-tooltip.top="'Edit'" @click="openEditModal(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button icon="pi pi-key" outlined rounded severity="help" size="small"
                                            v-tooltip.top="'Reset Password'" @click="resetPassword(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />

                                        <Button v-if="hasPermission('can.delete.user')"
                                            icon="pi pi-trash" outlined rounded severity="danger" size="small"
                                            v-tooltip.top="'Delete'" @click="deleteUser(slotProps.data)" 
                                            class="w-7 h-7 sm:w-8 sm:h-8 p-0" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Mobile Pagination Info -->
                    <div class="mt-4 text-xs text-gray-600 text-center sm:hidden">
                        Page {{ users.current_page }} of {{ Math.ceil(users.total / users.per_page) }}
                    </div>
                </template>
            </Card>

            <!-- Create/Edit User Dialog -->
            <Dialog v-model:visible="showUserModal" modal 
                :header="isEditMode ? 'Edit User' : 'Create New User'" 
                :style="{ width: '95vw', maxWidth: '750px' }"
                :breakpoints="{ '1199px': '90vw', '640px': '95vw' }">
                
                <div class="space-y-3 sm:space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Name -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="userForm.name" 
                                placeholder="Enter full name" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': userForm.errors.name }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.name">
                                {{ userForm.errors.name }}
                            </small>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <InputText v-model="userForm.email" 
                                type="email"
                                placeholder="Enter email address" 
                                class="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': userForm.errors.email }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.email">
                                {{ userForm.errors.email }}
                            </small>
                        </div>

                        <!-- Job Title -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Job Title</label>
                            <InputText v-model="userForm.job_title" 
                                placeholder="Enter job title" 
                                class="w-full text-xs sm:text-sm"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Department -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Department</label>
                            <InputText v-model="userForm.department" 
                                placeholder="Enter department" 
                                class="w-full text-xs sm:text-sm"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Office Location -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Office Location</label>
                            <InputText v-model="userForm.office_location" 
                                placeholder="Enter office location" 
                                class="w-full text-xs sm:text-sm"
                                :disabled="userForm.processing" />
                        </div>

                        <!-- Roles Assignment -->
                        <div class="space-y-1 sm:space-y-2 md:col-span-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">Assign Roles</label>
                            <MultiSelect v-model="userForm.role_ids" 
                                :options="roles" 
                                optionLabel="name" 
                                optionValue="id"
                                placeholder="Select roles..."
                                display="chip"
                                class="w-full text-xs sm:text-sm"
                                :maxSelectedLabels="3"
                                :disabled="userForm.processing">
                                <template #value="slotProps">
                                    <div v-if="slotProps.value && slotProps.value.length > 0" class="flex flex-wrap gap-1">
                                        <Badge v-for="roleId in slotProps.value" 
                                               :key="roleId"
                                               :value="roles.find(r => r.id === roleId)?.name"
                                               severity="info"
                                               class="text-xs" />
                                    </div>
                                    <span v-else class="text-gray-400 text-xs sm:text-sm">{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center space-x-2">
                                        <i class="pi pi-shield text-blue-500 text-xs"></i>
                                        <span class="text-xs sm:text-sm">{{ slotProps.option.name }}</span>
                                    </div>
                                </template>
                            </MultiSelect>
                            <small class="text-gray-500 text-xs">
                                Select one or more roles to assign to this user
                            </small>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                {{ isEditMode ? 'New Password' : 'Password' }} 
                                <span v-if="!isEditMode" class="text-red-500">*</span>
                            </label>
                            <Password v-model="userForm.password" 
                                :placeholder="isEditMode ? 'Leave blank to keep current' : 'Enter password'"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                inputClass="w-full text-xs sm:text-sm"
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
                        <div class="space-y-1 sm:space-y-2">
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700">
                                {{ isEditMode ? 'Confirm New Password' : 'Confirm Password' }}
                                <span v-if="!isEditMode" class="text-red-500">*</span>
                            </label>
                            <Password v-model="userForm.password_confirmation" 
                                :placeholder="isEditMode ? 'Confirm new password' : 'Confirm password'"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                inputClass="w-full text-xs sm:text-sm"
                                :class="{ 'p-invalid': userForm.errors.password_confirmation }"
                                :disabled="userForm.processing" />
                            <small class="text-red-500 text-xs" v-if="userForm.errors.password_confirmation">
                                {{ userForm.errors.password_confirmation }}
                            </small>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-3 sm:pt-4 border-t">
                        <Button label="Cancel" 
                            severity="secondary" 
                            outlined 
                            @click="showUserModal = false"
                            :disabled="userForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                        <Button :label="isEditMode ? 'Update' : 'Create'" 
                            icon="pi pi-check" 
                            severity="success" 
                            @click="saveUser"
                            :loading="userForm.processing"
                            :disabled="!isFormValid || userForm.processing"
                            class="w-full sm:w-auto text-xs sm:text-sm" />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add the same responsive styles as your license management */
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
:deep(.p-password-input) {
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

/* Responsive button sizing */
:deep(.p-button.p-button-sm) {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-button.p-button-sm) {
        font-size: 0.875rem;
    }
}

/* Mobile table fixes */
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