<script setup>
import { ref, computed } from "vue";
import { Link, Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Toast from "primevue/toast";
import Breadcrumb from 'primevue/breadcrumb';
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';

const toast = useToast();

const props = defineProps({
    categories: Object,
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Assets', url: route('admin.assets.index') },
    { label: 'Create Asset' },
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
    const selectedCategory = props.categories.find(category => category.id === form.category_type_id);
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

    <Head title="Create License" />
    <app-layout>
        <Toast />
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="mb-1 text-xl font-semibold text-gray-800">Create New License</h2>
                    <Breadcrumb :home="home" :model="items">
                        <template #item="{ item }">
                            <Link v-if="item.url" :href="item.url"
                                class="inline-flex items-center space-x-2 text-sm text-green-600 hover:underline">
                            <i :class="item.icon" v-if="item.icon" />
                            <span>{{ item.label }}</span>
                            </Link>
                            <span v-else class="inline-flex items-center space-x-2 text-sm text-gray-500">
                                <span>{{ item.label }}</span>
                            </span>
                        </template>
                    </Breadcrumb>
                </div>
            </div>

            <!-- Form fields -->
            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">License Name</label>
                    <InputText v-model="form.asset_name" class="w-full" placeholder="Please Enter Asset Name" />
                    <Message v-if="errors.asset_name" severity="error">{{ errors.asset_name }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Asset Tag Number</label>
                    <InputText v-model="form.asset_tag_no" class="w-full" placeholder="Asset Tag Number" />
                    <Message v-if="errors.asset_tag_no" severity="error">{{ errors.asset_tag_no }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Serial Number</label>
                    <InputText v-model="form.serial_no" class="w-full" placeholder="Serial Number" />
                    <Message v-if="errors.serial_no" severity="error">{{ errors.serial_no }}</Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Category</label>
                    <Select v-model="form.category_type_id" :options="categories" optionLabel="category_name"
                        optionValue="id" class="w-full" placeholder="Select Category" />
                    <Message v-if="errors.category_type_id" severity="error">{{ errors.category_type_id }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Model</label>
                    <Select v-model="form.model_type_id" :options="filteredModels" optionLabel="model_name"
                        optionValue="id" class="w-full" :disabled="!form.category_type_id" placeholder="Select Model" />
                    <Message v-if="errors.model_type_id" severity="error">{{ errors.model_type_id }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <InputText v-model="form.status" class="w-full" />
                    <Message v-if="errors.status" severity="error">{{ errors.status }}</Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Quantity</label>
                    <InputText v-model="form.qty" type="number" class="w-full" />
                    <Message v-if="errors.qty" severity="error">{{ errors.qty }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Location</label>
                    <InputText v-model="form.location" class="w-full" />
                    <Message v-if="errors.location" severity="error">{{ errors.location }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Purchase Cost</label>
                    <InputNumber v-model="form.purchase_cost" mode="currency" currency="MYR" locale="en-US"
                        class="w-full" />
                    <Message v-if="errors.purchase_cost" severity="error">{{ errors.purchase_cost }}</Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3"> 
                <div>
                    <label class="block text-sm font-medium">Purchase Date</label>
                    <DatePicker v-model="form.purchase_date" class="w-full" />
                    <Message v-if="errors.purchase_date" severity="error">{{ errors.purchase_date }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Asset Image</label>
                    <input type="file" accept="image/*" @change="onFileChange"
                        class="w-full px-2 py-1 border rounded" />
                    <Message v-if="errors.image" severity="error">{{ errors.image }}</Message>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <Button label="Create Asset" icon="pi pi-check" @click="submit" :loading="form.processing"
                    :disabled="form.processing" />
            </div>
        </div>
    </app-layout>
</template>
