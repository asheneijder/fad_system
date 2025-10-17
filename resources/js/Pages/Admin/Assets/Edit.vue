<script setup>
import { ref, computed, onMounted } from "vue";
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
import Divider from 'primevue/divider';
import Breadcrumb from 'primevue/breadcrumb';

const toast = useToast();
const isFormVisible = ref(false);
const imagePreview = ref(null);

const props = defineProps({
    categories: Object,
    asset: Object,
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Assets', url: route('admin.assets.index') },
    { label: 'Edit Asset' },
];

const form = useForm({
    asset_name: props.asset.asset_name,
    asset_tag_no: props.asset.asset_tag_no,
    serial_no: props.asset.serial_no,
    category_type_id: props.asset.category_type_id,
    model_type_id: props.asset.model_type_id,
    status: props.asset.status,
    location: props.asset.location,
    purchase_date: props.asset.purchase_date ? new Date(props.asset.purchase_date) : null,
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
    const file = e.target.files[0];
    form.image = file;
    
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
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
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Please check the form for errors",
                life: 3000,
            });
        },
    });
};

onMounted(() => {
    setTimeout(() => {
        isFormVisible.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Edit Asset" />
    <AppLayout>
        <Toast />
        <div class="transition-all duration-500 transform" :class="{'opacity-0 translate-y-4': !isFormVisible, 'opacity-100 translate-y-0': isFormVisible}">
            <!-- Header Section with Gradient -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-t-lg p-6 shadow-lg">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="pi pi-pencil mr-2"></i>
                    Edit Asset
                </h2>
                <p class="text-indigo-100 mt-2">Update the details for asset #{{ props.asset.asset_tag_no }}</p>
            </div>
            
            <!-- Main Form Container -->
            <div class="bg-white rounded-b-lg shadow-lg p-6 border border-gray-200">
                <!-- Form Sections -->
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <!-- Left Column - Basic Information -->
                    <div class="space-y-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <i class="pi pi-info-circle mr-2 text-indigo-500"></i>
                                Basic Information
                            </h3>
                            <Divider class="my-3" />
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset Name</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-tag"></i>
                                <InputText v-model="form.asset_name" class="w-full p-inputtext-sm" 
                                    placeholder="Enter asset name" />
                            </span>
                            <Message v-if="errors.asset_name" severity="error" class="mt-1">{{ errors.asset_name }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag Number</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-id-card"></i>
                                <InputText v-model="form.asset_tag_no" class="w-full p-inputtext-sm" 
                                    placeholder="Asset tag number" />
                            </span>
                            <Message v-if="errors.asset_tag_no" severity="error" class="mt-1">{{ errors.asset_tag_no }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-hashtag"></i>
                                <InputText v-model="form.serial_no" class="w-full p-inputtext-sm" 
                                    placeholder="Serial number" />
                            </span>
                            <Message v-if="errors.serial_no" severity="error" class="mt-1">{{ errors.serial_no }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <Select v-model="form.category_type_id" :options="categories" optionLabel="category_name" optionValue="id"
                                class="w-full p-inputtext-sm" placeholder="Select Category">
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <i class="pi pi-folder mr-2 text-indigo-500"></i>
                                        <div>{{ slotProps.value.category_name || props.categories.find(c => c.id === form.category_type_id)?.category_name }}</div>
                                    </div>
                                    <span v-else>
                                        <i class="pi pi-folder mr-2"></i>
                                        Select Category
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <i class="pi pi-folder mr-2 text-indigo-500"></i>
                                        <div>{{ slotProps.option.category_name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <Message v-if="errors.category_type_id" severity="error" class="mt-1">{{ errors.category_type_id }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                            <Select v-model="form.model_type_id" :options="filteredModels" optionLabel="model_name"
                                optionValue="id" class="w-full p-inputtext-sm" :disabled="!form.category_type_id" 
                                placeholder="Select Model">
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <i class="pi pi-box mr-2 text-indigo-500"></i>
                                        <div>{{ slotProps.value.model_name || filteredModels.find(m => m.id === form.model_type_id)?.model_name }}</div>
                                    </div>
                                    <span v-else>
                                        <i class="pi pi-box mr-2"></i>
                                        {{ form.category_type_id ? 'Select Model' : 'Select Category First' }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <i class="pi pi-box mr-2 text-indigo-500"></i>
                                        <div>{{ slotProps.option.model_name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <Message v-if="errors.model_type_id" severity="error" class="mt-1">{{ errors.model_type_id }}</Message>
                        </div>
                    </div>

                    <!-- Right Column - Additional Details -->
                    <div class="space-y-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <i class="pi pi-list mr-2 text-indigo-500"></i>
                                Additional Details
                            </h3>
                            <Divider class="my-3" />
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <Select v-model="form.status" :options="[
                                { label: 'Active', value: 1 },
                                { label: 'Inactive', value: 0 }
                            ]" class="w-full p-inputtext-sm" placeholder="Select Status">
                                <template #value="slotProps">
                                    <div class="flex items-center">
                                        <i :class="[slotProps.value === 1 ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500', 'mr-2']"></i>
                                        <span>{{ slotProps.value === 1 ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <i :class="[slotProps.option.value === 1 ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-red-500', 'mr-2']"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </Select>
                            <Message v-if="errors.status" severity="error" class="mt-1">{{ errors.status }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-sort-numeric-up"></i>
                                <InputNumber v-model="form.qty" class="w-full p-inputtext-sm" 
                                    :min="0" :showButtons="true" buttonLayout="horizontal"
                                    decrementButtonClass="p-button-secondary" incrementButtonClass="p-button-secondary" 
                                    incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus" />
                            </span>
                            <Message v-if="errors.qty" severity="error" class="mt-1">{{ errors.qty }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-map-marker"></i>
                                <InputText v-model="form.location" class="w-full p-inputtext-sm" placeholder="Asset location" />
                            </span>
                            <Message v-if="errors.location" severity="error" class="mt-1">{{ errors.location }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost (MYR)</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-wallet"></i>
                                <InputNumber v-model="form.purchase_cost" mode="currency" currency="MYR" locale="en-US" 
                                    class="w-full p-inputtext-sm" />
                            </span>
                            <Message v-if="errors.purchase_cost" severity="error" class="mt-1">{{ errors.purchase_cost }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Value (MYR)</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-dollar"></i>
                                <InputNumber v-model="form.current_value" mode="currency" currency="MYR" locale="en-US" 
                                    class="w-full p-inputtext-sm" />
                            </span>
                            <Message v-if="errors.current_value" severity="error" class="mt-1">{{ errors.current_value }}</Message>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row - Date and Image -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                        <i class="pi pi-image mr-2 text-indigo-500"></i>
                        Asset Details & Image
                    </h3>
                    <Divider class="my-3" />
                    
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mt-4">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-calendar"></i>
                                <DatePicker v-model="form.purchase_date" class="w-full p-inputtext-sm" 
                                    showIcon dateFormat="dd/mm/yy" placeholder="Select purchase date" />
                            </span>
                            <Message v-if="errors.purchase_date" severity="error" class="mt-1">{{ errors.purchase_date }}</Message>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset Image</label>
                            <div class="flex items-start space-x-4">
                                <div class="flex-1">
                                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-indigo-500 transition-colors">
                                        <input type="file" accept="image/*" @change="onFileChange" 
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                        <div class="text-center">
                                            <i class="pi pi-cloud-upload text-3xl text-gray-400 mb-2"></i>
                                            <p class="text-sm text-gray-500">Drag & drop a new image or click to browse</p>
                                            <p class="text-xs text-gray-400 mt-1">PNG, JPG or JPEG (max. 5MB)</p>
                                        </div>
                                    </div>
                                    <Message v-if="errors.image" severity="error" class="mt-1">{{ errors.image }}</Message>
                                </div>
                                
                                <!-- Image Preview -->
                                <div v-if="imagePreview || (props.asset.media && props.asset.media.length)" 
                                    class="w-32 h-32 relative rounded-lg overflow-hidden border border-gray-200 shadow-md">
                                    <img :src="imagePreview || (props.asset.media && props.asset.media.length ? props.asset.media[0].original_url : '')" 
                                        class="w-full h-full object-cover" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-60 text-white text-xs p-1 text-center">
                                        {{ imagePreview ? 'New Image' : 'Current Image' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-8">
                    <Button type="button" icon="pi pi-arrow-left" label="Cancel" 
                        class="p-button-outlined p-button-secondary mr-2" 
                        :disabled="form.processing"
                        @click="$inertia.visit(route('admin.assets.index'))" />
                    <Button type="button" icon="pi pi-check" label="Update Asset" 
                        class="p-button-primary" 
                        :loading="form.processing" :disabled="form.processing"
                        @click="submit">
                        <template #loading>
                            <i class="pi pi-spin pi-spinner mr-2"></i>
                            Updating...
                        </template>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.form-group {
    @apply transition-all duration-300 transform;
}

.form-group:hover {
    @apply -translate-y-1;
}

.p-inputtext:focus {
    @apply shadow-md;
    box-shadow: 0 0 0 2px #f3f8ff, 0 0 0 4px #6366f1;
}

/* Custom animation for error messages */
.p-message-error {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes shake {
    10%, 90% { transform: translate3d(-1px, 0, 0); }
    20%, 80% { transform: translate3d(2px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
    40%, 60% { transform: translate3d(3px, 0, 0); }
}
</style>
