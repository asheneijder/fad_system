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

const form = ref({
    name: "",
    email: "",
    job_title: "",
    department: "",
    office_location: "",
});

const errors = ref({});

const submit = () => {
    router.post(route("admin.users.store"), form.value, {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "User created successfully",
                life: 3000,
            });
            form.value = { 
                name: "", 
                email: "", 
                job_title: "", 
                department: "", 
                office_location: "" 
            };
            errors.value = {};
        },
        onError: (err) => {
            errors.value = err;
        },
    });
};
</script>

<template>

    <Head title="Create User" />
    <app-layout>
        <Toast />
        <div class="max-w-lg p-5 mx-auto card">
            <h2 class="mb-4 text-lg font-bold">Create New User</h2>

            <div class="mb-3">
                <label class="block text-sm font-medium">Name</label>
                <InputText 
                    v-model="form.name" 
                    type="text"
                    class="w-full"
                    autocomplete="off"
                    placeholder="Please enter your name" 
                />
                <Message 
                    v-if="errors.name" 
                    severity="error" 
                    size="small" 
                    variant="simple"
                >{{ errors.name }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Email</label>
                <InputText 
                    v-model="form.email" 
                    type="email" 
                    class="w-full"
                    autocomplete="off" 
                    placeholder="Please enter your email address" 
                />
                <Message 
                    v-if="errors.email" 
                    severity="error" 
                    size="small" 
                    variant="simple"
                >{{ errors.email }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Job Title</label>
                <InputText 
                    v-model="form.job_title" 
                    class="w-full" 
                    autocomplete="off"
                    placeholder="Please enter your job title" 
                />
                <Message 
                    v-if="errors.job_title" 
                    severity="error" 
                    size="small" 
                    variant="simple"
                >{{ errors.job_title }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Department</label>
                <InputText 
                    v-model="form.department" 
                    class="w-full" 
                    autocomplete="off"
                    placeholder="Please enter your department" 
                />
                <Message 
                    v-if="errors.department" 
                    severity="error" 
                    size="small" 
                    variant="simple"
                >{{ errors.department }}
                </Message>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Office Location</label>
                <InputText 
                    v-model="form.office_location" 
                    class="w-full" 
                    autocomplete="off"
                    placeholder="Please enter your office location" 
                />
                <Message 
                    v-if="errors.office_location" 
                    severity="error" 
                    size="small" 
                    variant="simple"
                >{{ errors.office_location }}
                </Message>
            </div>

            <div class="flex justify-end mt-4">
                <Button 
                    label="Create User" 
                    icon="pi pi-check" 
                    @click="submit"
                />
            </div>
        </div>
    </app-layout>
</template>
