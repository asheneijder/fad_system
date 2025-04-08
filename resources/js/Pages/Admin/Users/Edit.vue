<script setup>
import { ref } from "vue";
import { router, Head } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Toast from "primevue/toast";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const props = defineProps({
    user: Object,
});

const form = ref({
    name: props.user.name,
    email: props.user.email,
    job_title: props.user.job_title,
    department: props.user.department,
    office_location: props.user.office_location,
});

const errors = ref({});

const submit = () => {
    router.put(route("admin.users.update", props.user.id), form.value, {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "User updated successfully",
                life: 3000,
            });
            errors.value = {};
        },
        onError: (err) => {
            errors.value = err;
        },
    });
};
</script>

<template>

    <Head :title="`Edit User - ${form.name}`" />
    <app-layout>
        <Toast />
        <div class="max-w-lg p-5 mx-auto card">
            <h2 class="mb-4 text-lg font-bold">Edit User</h2>

            <div class="mb-3">
                <label class="block text-sm font-medium">Name</label>
                <InputText 
                    v-model="form.name" 
                    class="w-full" />
                <Message 
                    v-if="errors.name" 
                    severity="error" 
                    size="small" 
                    variant="simple">{{ errors.name }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Email</label>
                <InputText 
                    v-model="form.email" 
                    type="email" 
                    class="w-full" />
                <Message 
                    v-if="errors.email" 
                    severity="error" 
                    size="small" 
                    variant="simple">{{ errors.email }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Job Title</label>
                <InputText 
                    v-model="form.job_title" 
                    class="w-full" />
                <Message 
                    v-if="errors.job_title" 
                    severity="error" 
                    size="small" 
                    variant="simple">{{ errors.job_title }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Department</label>
                <InputText 
                    v-model="form.department" 
                    class="w-full" />
                <Message 
                    v-if="errors.department" 
                    severity="error" 
                    size="small" 
                    variant="simple">{{ errors.department }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Office Location</label>
                <InputText 
                    v-model="form.office_location" 
                    class="w-full" />
                <Message 
                    v-if="errors.office_location" 
                    severity="error" 
                    size="small" 
                    variant="simple">{{errors.office_location }}
                </Message>
            </div>

            <div class="flex justify-end mt-4">
                <Button 
                    label="Update User" 
                    icon="pi pi-check" 
                    @click="submit"
                />
            </div>
        </div>
    </app-layout>
</template>
