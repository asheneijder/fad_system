<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Toast from 'primevue/toast';

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const page = ref(props.filters?.page || 1);

watch(search, (newSearch) => {
    router.get(route("admin.users.index"), { search: newSearch }, { preserveState: true, replace: true });
});

watch(page, (newPage) => {
    router.get(route("admin.users.index"), { search: search.value, page: newPage }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.users.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};

const editUser = (id) => {
    router.get(route("admin.users.edit", id));
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
        }
    });
};

const createUser = () => {
    router.get(route("admin.users.create"));
};
</script>

<template>

    <Head title="Users" />
    <app-layout>
        <Toast />
        <ConfirmDialog />
        <div class="card">
            <div class="flex justify-between mb-4">
                <Button 
                    label="Create New User" 
                    icon="pi pi-plus" 
                    class="p-button-sm p-button-success" 
                    @click="createUser" 
                />
                <InputText 
                    v-model="search" 
                    placeholder="Search users..." 
                    class="p-inputtext-sm" 
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
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                        <p class="mt-2 text-lg font-medium text-gray-500">No users found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters or adding new users.</p>
                    </div>
                </template>

                <Column header="#" style="width: 50px;">
                    <template #body="slotProps">
                        {{ (users.current_page - 1) * users.per_page + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column field="name" header="Name"></Column>
                <Column field="email" header="Email"></Column>
                <Column field="job_title" header="Job Title"></Column>
                <Column field="department" header="Department"></Column>
                <Column field="office_location" header="Office Location"></Column>
                <Column field="created_at" header="Joined"></Column>

                <Column header="Actions" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-pencil" 
                            outlined rounded class="mr-2"
                            @click="editUser(slotProps.data.id)" 
                        />
                        <Button 
                            icon="pi pi-trash" 
                            outlined rounded severity="danger"
                            @click="deleteUser(slotProps.data.id)" 
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </app-layout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
</style>
