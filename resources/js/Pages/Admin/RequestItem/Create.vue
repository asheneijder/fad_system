<script setup>
import { ref } from "vue";
import { router, Head } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Checkbox from "primevue/checkbox";
import Card from 'primevue/card';

const toast = useToast();

const props = defineProps({
    categories: Array,
    items: Array, // List of items to choose from
});

const form = ref({
    category_id: null,
    items: [],
    reason: "",
    acknowledge: false,
    request_date: new Date().toISOString().split("T")[0], // Automatically set today's date
});

const errors = ref({});

// Function to add a new item selection
const addItem = () => {
    form.value.items.push({
        item_id: null,
        quantity: 1,
    });
};

// Function to remove an item from selection
const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

// Submit the request
const submit = () => {
    if (!form.value.acknowledge) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "You must acknowledge before submitting.",
            life: 3000,
        });
        return;
    }

    router.post(route("user.request.store"), form.value, {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "Request submitted successfully",
                life: 3000,
            });
            form.value = {
                category_id: null,
                items: [],
                reason: "",
                acknowledge: false,
                request_date: new Date().toISOString().split("T")[0], // Reset to today's date
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

    <Head title="Create Request Item" />
    <AppLayout>
        <Toast />
        <div class="max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-lg">
            <h2 class="mb-4 text-2xl font-semibold text-gray-800">Request New Item</h2>

            <Card class="mb-6 border border-gray-200 shadow-sm">
                <template #content>
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium text-gray-600">Name:</span>
                            <span class="text-gray-800">John Doe</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium text-gray-600">Job Title:</span>
                            <span class="text-gray-800">Software Engineer</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Department/Unit:</span>
                            <span class="text-gray-800">IT Department</span>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Request Date -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Request Date</label>
                <InputText v-model="form.request_date" class="w-full mt-1 border border-gray-300 rounded-md shadow-sm"
                    disabled />
            </div>

            <!-- Category Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Choose Category</label>
                <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id"
                    class="w-full mt-1" placeholder="Select Category" />
            </div>

            <!-- Dynamic Item Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Select Stationary</label>
                <div v-for="(item, index) in form.items" :key="index" class="flex items-center gap-3 mt-2">
                    <Select v-model="item.item_id" :options="items" optionLabel="name" optionValue="id" filter
                        class="flex-grow" placeholder="Select Item" />

                    <InputNumber v-model="item.quantity" showButtons buttonLayout="horizontal" :min="1" :max="99">
                        <template #incrementbuttonicon>
                            <span class="pi pi-plus" />
                        </template>
                        <template #decrementbuttonicon>
                            <span class="pi pi-minus" />
                        </template>
                    </InputNumber>

                    <Button icon="pi pi-trash" class="p-2 p-button-danger" @click="removeItem(index)" />
                </div>
                <Button label="Add Item" icon="pi pi-plus" class="mt-3 p-button-outlined p-button-secondary"
                    @click="addItem" />
            </div>

            <!-- Reason -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Reason for Request</label>
                <Textarea v-model="form.reason" class="w-full mt-1 border border-gray-300 rounded-md shadow-sm" rows="3"
                    placeholder="Explain why you need this item" />
            </div>

            <Card class="p-4 mt-4 border border-gray-300 shadow-sm">
                <template #title>Acknowledgment Agreement</template>
                <template #content>
                    <div class="space-y-3 text-sm text-gray-700">
                        <p><strong>By submitting this request, you agree to the following:</strong></p>
                        <ol class="space-y-2 list-decimal list-inside">
                            <li>The information provided is accurate and necessary.</li>
                            <li>Any false or misleading information may result in the request being denied or require
                                further verification.</li>
                            <li>The request is subject to approval based on company policies and stock availability.
                            </li>
                        </ol>

                        <div class="p-3 mt-4 bg-gray-100 border border-gray-300 rounded-md">
                            <p class="font-medium text-gray-800">📌 <strong>Company Tatacara (Guidelines):</strong></p>
                            <ul class="space-y-1 text-sm text-gray-600 list-disc list-inside">
                                <li>Requests for assets such as laptops are reviewed by the IT department before
                                    approval.</li>
                                <li>Accessories (e.g., pens, rulers) are subject to stock availability and usage policy.
                                </li>
                                <li>All assigned items must be used responsibly and returned if no longer needed.</li>
                            </ul>
                        </div>

                        <div class="flex items-center mt-4">
                            <Checkbox v-model="form.acknowledge" class="mr-2" />
                            <label class="text-sm font-medium text-gray-700">I have read and agree to the terms
                                above.</label>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Submit Button -->
            <div class="flex justify-end mt-6">
                <Button label="Submit Request" icon="pi pi-check"
                    class="px-4 py-2 text-white bg-blue-600 rounded-md shadow-lg hover:bg-blue-700" @click="submit" />
            </div>
        </div>
    </AppLayout>
</template>
