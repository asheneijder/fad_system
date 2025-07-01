<script setup>
import { ref, computed } from "vue";
import { router, Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Toast from "primevue/toast";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';

const toast = useToast();

const props = defineProps({
    categories: Object,
    asset: Object,
});

const form = useForm({
    asset_name: props.asset.asset_name,
    asset_tag_no: props.asset.asset_tag_no,
    serial_no: props.asset.serial_no,
    category_type_id: props.asset.category_type_id,
    model_type_id: props.asset.model_type_id,
    status: props.asset.status,
    location: props.asset.location,
    purchase_date: props.asset.purchase_date ? new Date(props.asset.purchase_date) : null, // ✅ FIXED HERE
    qty: props.asset.qty,
    purchase_cost: props.asset.purchase_cost,
    current_value: props.asset.current_value,
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
    
    form.put(route("admin.assets.update", props.asset.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "Asset updated successfully",
                life: 3000,
            });
        },
        onError: (err) => {
            errors.value = err;
        },
    });
};
</script>

<template>

    <Head title="Edit Asset" />
    <app-layout>
        <Toast />
        <div class="card">
            <h2 class="mb-4 text-lg font-bold">Edit Asset</h2>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Asset Name</label>
                    <InputText v-model="form.asset_name" class="w-full" />
                    <Message v-if="errors.asset_name" severity="error">{{ errors.asset_name }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Asset Tag Number</label>
                    <InputText v-model="form.asset_tag_no" class="w-full" />
                    <Message v-if="errors.asset_tag_no" severity="error">{{ errors.asset_tag_no }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Serial Number</label>
                    <InputText v-model="form.serial_no" class="w-full" />
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
                        optionValue="id" class="w-full" placeholder="Select Model" :disabled="!form.category_type_id" />
                    <Message v-if="errors.model_type_id" severity="error">{{ errors.model_type_id }}</Message>
                </div>
                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <Select v-model="form.status" :options="[
                        { label: 'Active', value: 1 },
                        { label: 'Inactive', value: 0 }
                    ]" class="w-full" placeholder="Select Status" />
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
                    <label class="block text-sm font-medium">Current Value</label>
                    <InputNumber v-model="form.current_value" mode="currency" currency="MYR" locale="en-US"
                        class="w-full" />
                    <Message v-if="errors.current_value" severity="error">{{ errors.current_value }}</Message>
                </div>
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

            <!-- Show current image -->
            <div v-if="props.asset.media && props.asset.media.length" class="mt-4">
                <label class="block mb-1 text-sm font-medium">Current Asset Image</label>
                <img :src="props.asset.media[0].original_url" class="w-40 h-auto border rounded shadow" />
            </div>

            <div class="flex justify-end mt-6">
                <Button label="Update Asset" icon="pi pi-check" @click="submit" :loading="form.processing"
                    :disabled="form.processing" />
            </div>
        </div>
    </app-layout>
</template>
