<script setup>
import { ref, computed } from "vue";
import { Link, Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Toast from "primevue/toast";
import Breadcrumb from "primevue/breadcrumb";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import Calendar from "primevue/calendar";
import Card from "primevue/card";
import Divider from "primevue/divider";

const toast = useToast();

const props = defineProps({
    categories: Object,
});

const home = { icon: "pi pi-home", url: route("dashboard") };
const items = [
    { label: "Assets", url: route("admin.assets.index") },
    { label: "Create Asset" },
];

const form = useForm({
    asset_name: "",
    asset_tag_no: "",
    serial_no: "",
    category_type_id: null,
    model_type_id: null,
    status: 1,
    location: "",
    purchase_date: "",
    qty: 1,
    purchase_cost: 0,
    current_value: 0,
    image: null,
});

const errors = ref({});

const filteredModels = computed(() => {
    const selectedCategory = props.categories.find(
        (category) => category.id === form.category_type_id
    );
    return selectedCategory ? selectedCategory.model_types : [];
});

function onFileChange(e) {
    form.image = e.target.files[0];
}

const submit = () => {
    form.post(route("admin.assets.store"), {
        forceFormData: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "Asset created successfully",
                life: 3000,
            });
            form.reset();
            errors.value = {};
        },
        onError: (err) => {
            errors.value = err;
        },
    });
};
</script>

<template>
    <Head title="Create Asset" />
    <app-layout>
        <Toast />

        <Card class="shadow-md rounded-xl">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-box text-blue-500"></i>
                    <span class="text-lg font-semibold text-gray-800">
                        Create New Asset
                    </span>
                </div>
            </template>

            <template #content>
                <!-- Basic Info -->
                <h3 class="text-md font-semibold text-gray-700 mb-2">
                    Basic Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Asset Name</label
                        >
                        <InputText
                            v-model="form.asset_name"
                            class="w-full"
                            placeholder="Enter asset name"
                        />
                        <Message
                            v-if="errors.asset_name"
                            severity="error"
                            >{{ errors.asset_name }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Asset Tag Number</label
                        >
                        <InputText
                            v-model="form.asset_tag_no"
                            class="w-full"
                            placeholder="Asset Tag No."
                        />
                        <Message
                            v-if="errors.asset_tag_no"
                            severity="error"
                            >{{ errors.asset_tag_no }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Serial Number</label
                        >
                        <InputText
                            v-model="form.serial_no"
                            class="w-full"
                            placeholder="Serial No."
                        />
                        <Message
                            v-if="errors.serial_no"
                            severity="error"
                            >{{ errors.serial_no }}</Message
                        >
                    </div>
                </div>

                <Divider />

                <!-- Classification -->
                <h3 class="text-md font-semibold text-gray-700 mb-2">
                    Classification
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Category</label
                        >
                        <Dropdown
                            v-model="form.category_type_id"
                            :options="categories"
                            optionLabel="category_name"
                            optionValue="id"
                            class="w-full"
                            placeholder="Select Category"
                        />
                        <Message
                            v-if="errors.category_type_id"
                            severity="error"
                            >{{ errors.category_type_id }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Model</label
                        >
                        <Dropdown
                            v-model="form.model_type_id"
                            :options="filteredModels"
                            optionLabel="model_name"
                            optionValue="id"
                            class="w-full"
                            :disabled="!form.category_type_id"
                            placeholder="Select Model"
                        />
                        <Message
                            v-if="errors.model_type_id"
                            severity="error"
                            >{{ errors.model_type_id }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Status</label
                        >
                        <Dropdown
                            v-model="form.status"
                            :options="[
                                { label: 'Active', value: 1 },
                                { label: 'Inactive', value: 0 },
                            ]"
                            class="w-full"
                            placeholder="Select Status"
                        />
                        <Message
                            v-if="errors.status"
                            severity="error"
                            >{{ errors.status }}</Message
                        >
                    </div>
                </div>

                <Divider />

                <!-- Financials -->
                <h3 class="text-md font-semibold text-gray-700 mb-2">
                    Financial Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Quantity</label
                        >
                        <InputNumber
                            v-model="form.qty"
                            class="w-full"
                            :min="1"
                        />
                        <Message
                            v-if="errors.qty"
                            severity="error"
                            >{{ errors.qty }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Purchase Cost</label
                        >
                        <InputNumber
                            v-model="form.purchase_cost"
                            mode="currency"
                            currency="MYR"
                            locale="en-US"
                            class="w-full"
                        />
                        <Message
                            v-if="errors.purchase_cost"
                            severity="error"
                            >{{ errors.purchase_cost }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Current Value</label
                        >
                        <InputNumber
                            v-model="form.current_value"
                            mode="currency"
                            currency="MYR"
                            locale="en-US"
                            class="w-full"
                        />
                        <Message
                            v-if="errors.current_value"
                            severity="error"
                            >{{ errors.current_value }}</Message
                        >
                    </div>
                </div>

                <Divider />

                <!-- Other Details -->
                <h3 class="text-md font-semibold text-gray-700 mb-2">
                    Other Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Location</label
                        >
                        <InputText v-model="form.location" class="w-full" />
                        <Message
                            v-if="errors.location"
                            severity="error"
                            >{{ errors.location }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Purchase Date</label
                        >
                        <Calendar
                            v-model="form.purchase_date"
                            class="w-full"
                            dateFormat="yy-mm-dd"
                            showIcon
                        />
                        <Message
                            v-if="errors.purchase_date"
                            severity="error"
                            >{{ errors.purchase_date }}</Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1"
                            >Asset Image</label
                        >
                        <input
                            type="file"
                            accept="image/*"
                            @change="onFileChange"
                            class="w-full px-3 py-2 border rounded-md"
                        />
                        <Message
                            v-if="errors.image"
                            severity="error"
                            >{{ errors.image }}</Message
                        >
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <Button
                        label="Create Asset"
                        icon="pi pi-check"
                        class="p-button-success"
                        @click="submit"
                        :loading="form.processing"
                        :disabled="form.processing"
                    />
                </div>
            </template>
        </Card>
    </app-layout>
</template>
