<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Password from "primevue/password";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const users = ref(props.users);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Form data
const createForm = useForm({
    name: '',
    email: '',
    job_title: '',
    department: '',
    office_location: '',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    id: null,
    name: '',
    email: '',
    job_title: '',
    department: '',
    office_location: '',
    password: '',
    password_confirmation: '',
});

watch(() => props.users, (newUsers) => {
    users.value = newUsers;
});

watch(search, (newSearch) => {
    router.get(
        route("admin.users.index"),
        { search: newSearch },
        { preserveState: true, replace: true }
    );
});

const onPageChange = (event) => {
    router.get(
        route("admin.users.index"),
        { search: search.value, page: event.page + 1 },
        { preserveState: true, replace: true }
    );
};

// Create functions
const openCreateModal = () => {
    createForm.reset();
    createForm.password = 'st@ff!@Rt!'; // Default password
    createForm.password_confirmation = 'st@ff!@Rt!';
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route("admin.users.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "User created successfully",
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Please check the form for errors",
                life: 3000,
            });
        }
    });
};

// Edit functions
const openEditModal = (user) => {
    editForm.id = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.job_title = user.job_title || '';
    editForm.department = user.department || '';
    editForm.office_location = user.office_location || '';
    editForm.password = '';
    editForm.password_confirmation = '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route("admin.users.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "User updated successfully",
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Please check the form for errors",
                life: 3000,
            });
        }
    });
};

const deleteUser = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this user?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.users.destroy", { user: id }), {
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Deleted",
                        detail: "User deleted successfully",
                        life: 3000,
                    });
                },
            });
        },
    });
};

// Computed properties for form validation
const isCreateFormValid = computed(() => {
    return createForm.name && 
           createForm.email && 
           createForm.password && 
           createForm.password_confirmation &&
           createForm.password === createForm.password_confirmation;
});

const isEditFormValid = computed(() => {
    const baseValid = editForm.name && editForm.email;
    
    // If password fields are filled, they must match
    if (editForm.password || editForm.password_confirmation) {
        return baseValid && editForm.password === editForm.password_confirmation;
    }
    
    return baseValid;
});

// Helper function to format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Users" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <!-- Create Modal -->
        <Dialog v-model:visible="showCreateModal" modal header="Create New User" :style="{ width: '600px' }" 
                :closable="!createForm.processing">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <InputText
                            id="create_name"
                            v-model="createForm.name"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.name }"
                            placeholder="Enter full name"
                            autofocus
                        />
                        <small v-if="createForm.errors.name" class="p-error">{{ createForm.errors.name }}</small>
                    </div>

                    <div class="field">
                        <label for="create_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <InputText
                            id="create_email"
                            v-model="createForm.email"
                            type="email"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.email }"
                            placeholder="Enter email address"
                        />
                        <small v-if="createForm.errors.email" class="p-error">{{ createForm.errors.email }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_job_title" class="block text-sm font-medium text-gray-700 mb-2">Job Title</label>
                        <InputText
                            id="create_job_title"
                            v-model="createForm.job_title"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.job_title }"
                            placeholder="Enter job title"
                        />
                        <small v-if="createForm.errors.job_title" class="p-error">{{ createForm.errors.job_title }}</small>
                    </div>

                    <div class="field">
                        <label for="create_department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <InputText
                            id="create_department"
                            v-model="createForm.department"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.department }"
                            placeholder="Enter department"
                        />
                        <small v-if="createForm.errors.department" class="p-error">{{ createForm.errors.department }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="create_office_location" class="block text-sm font-medium text-gray-700 mb-2">Office Location</label>
                    <InputText
                        id="create_office_location"
                        v-model="createForm.office_location"
                        class="w-full"
                        :class="{ 'p-invalid': createForm.errors.office_location }"
                        placeholder="Enter office location"
                    />
                    <small v-if="createForm.errors.office_location" class="p-error">{{ createForm.errors.office_location }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="create_password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                        <Password
                            id="create_password"
                            v-model="createForm.password"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.password }"
                            placeholder="Enter password"
                            :feedback="false"
                            toggleMask
                        />
                        <small v-if="createForm.errors.password" class="p-error">{{ createForm.errors.password }}</small>
                        <small class="text-gray-500">Default: st@ff!@Rt!</small>
                    </div>

                    <div class="field">
                        <label for="create_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                        <Password
                            id="create_password_confirmation"
                            v-model="createForm.password_confirmation"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.password_confirmation }"
                            placeholder="Confirm password"
                            :feedback="false"
                            toggleMask
                        />
                        <small v-if="createForm.errors.password_confirmation" class="p-error">{{ createForm.errors.password_confirmation }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" @click="closeCreateModal" 
                        class="p-button-text" :disabled="createForm.processing" />
                <Button label="Create" icon="pi pi-check" @click="submitCreate" 
                        :disabled="!isCreateFormValid || createForm.processing" 
                        :loading="createForm.processing" />
            </template>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog v-model:visible="showEditModal" modal header="Edit User" :style="{ width: '600px' }"
                :closable="!editForm.processing">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <InputText
                            id="edit_name"
                            v-model="editForm.name"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.name }"
                            placeholder="Enter full name"
                        />
                        <small v-if="editForm.errors.name" class="p-error">{{ editForm.errors.name }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <InputText
                            id="edit_email"
                            v-model="editForm.email"
                            type="email"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.email }"
                            placeholder="Enter email address"
                        />
                        <small v-if="editForm.errors.email" class="p-error">{{ editForm.errors.email }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_job_title" class="block text-sm font-medium text-gray-700 mb-2">Job Title</label>
                        <InputText
                            id="edit_job_title"
                            v-model="editForm.job_title"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.job_title }"
                            placeholder="Enter job title"
                        />
                        <small v-if="editForm.errors.job_title" class="p-error">{{ editForm.errors.job_title }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <InputText
                            id="edit_department"
                            v-model="editForm.department"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.department }"
                            placeholder="Enter department"
                        />
                        <small v-if="editForm.errors.department" class="p-error">{{ editForm.errors.department }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="edit_office_location" class="block text-sm font-medium text-gray-700 mb-2">Office Location</label>
                    <InputText
                        id="edit_office_location"
                        v-model="editForm.office_location"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.office_location }"
                        placeholder="Enter office location"
                    />
                    <small v-if="editForm.errors.office_location" class="p-error">{{ editForm.errors.office_location }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <Password
                            id="edit_password"
                            v-model="editForm.password"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.password }"
                            placeholder="Leave blank to keep current password"
                            :feedback="false"
                            toggleMask
                        />
                        <small v-if="editForm.errors.password" class="p-error">{{ editForm.errors.password }}</small>
                        <small class="text-gray-500">Leave blank to keep current password</small>
                    </div>

                    <div class="field">
                        <label for="edit_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <Password
                            id="edit_password_confirmation"
                            v-model="editForm.password_confirmation"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.password_confirmation }"
                            placeholder="Confirm new password"
                            :feedback="false"
                            toggleMask
                        />
                        <small v-if="editForm.errors.password_confirmation" class="p-error">{{ editForm.errors.password_confirmation }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" @click="closeEditModal" 
                        class="p-button-text" :disabled="editForm.processing" />
                <Button label="Update" icon="pi pi-check" @click="submitEdit" 
                        :disabled="!isEditFormValid || editForm.processing" 
                        :loading="editForm.processing" />
            </template>
        </Dialog>

        <div class="p-6 card">
            <div
                class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center"
            >
                <Button
                    label="Create New User"
                    icon="pi pi-plus"
                    class="p-button-success p-button-sm"
                    @click="openCreateModal"
                />
                <InputText
                    v-model="search"
                    placeholder="Search users..."
                    class="w-full p-inputtext-sm sm:w-64"
                />
            </div>

            <DataTable
                :value="users.data"
                showGridlines
                :rowHover="true"
                :loading="users.loading"
                paginator
                :rows="users.per_page"
                :totalRecords="users.total"
                :first="(users.current_page - 1) * users.per_page"
                lazy
                @page="onPageChange"
                tableStyle="min-width: 50rem"
                responsiveLayout="scroll"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <svg
                            class="w-12 h-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                            ></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">
                            No users found
                        </p>
                        <p class="text-sm text-gray-400">
                            Try adjusting your filters or adding new users.
                        </p>
                    </div>
                </template>

                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{
                            (users.current_page - 1) * users.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>
                <Column field="name" header="Name" style="min-width: 150px" />
                <Column field="email" header="Email" style="min-width: 200px" />
                <Column field="job_title" header="Job Title" style="min-width: 150px">
                    <template #body="slotProps">
                        <span>{{ slotProps.data.job_title || '—' }}</span>
                    </template>
                </Column>
                <Column field="department" header="Department" style="min-width: 120px">
                    <template #body="slotProps">
                        <span>{{ slotProps.data.department || '—' }}</span>
                    </template>
                </Column>
                <Column field="office_location" header="Office Location" style="min-width: 150px">
                    <template #body="slotProps">
                        <span>{{ slotProps.data.office_location || '—' }}</span>
                    </template>
                </Column>
                <Column field="created_at" header="Joined" style="min-width: 120px">
                    <template #body="slotProps">
                        <span class="text-sm text-gray-600">
                            {{ formatDate(slotProps.data.created_at) }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="openEditModal(slotProps.data)"
                            v-tooltip="'Edit User'"
                        />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="deleteUser(slotProps.data.id)"
                            v-tooltip="'Delete User'"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}

.field {
    margin-bottom: 1rem;
}

.space-y-4 > * + * {
    margin-top: 1rem;
}
</style>