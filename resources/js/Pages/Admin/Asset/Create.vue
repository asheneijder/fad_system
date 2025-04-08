<script setup>
import { ref, computed } from "vue";
import { router, Head } from "@inertiajs/vue3";
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
});

const form = ref({
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
});

const errors = ref({});

const filteredModels = computed(() => {
    const selectedCategory = props.categories.find(category => category.id === form.value.category_type_id);

    return selectedCategory ? selectedCategory.model_types : [];
});

const submit = () => {
    router.post(route("admin.asset.store"), form.value, {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "Asset created successfully",
                life: 3000,
            });
            form.value = {
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

    <Head title="Create Asset" />
    <app-layout>
        <Toast />
        <div class="card">
            <h2 class="mb-4 text-lg font-bold">Create New Asset</h2>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Asset Name</label>
                    <InputText 
                        v-model="form.asset_name" 
                        class="w-full"
                        autocomplete="off"
                        placeholder="Please Enter Asset Name" 
                    />
                    <Message 
                        v-if="errors.asset_name" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.asset_name }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Asset Tag Number</label>
                    <InputText 
                        v-model="form.asset_tag_no" 
                        class="w-full" 
                        autocomplete="off" 
                        placeholder="Please Enter Asset Tag Number" 
                    />
                    <Message 
                        v-if="errors.asset_tag_no" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.asset_tag_no }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Asset Serial Number</label>
                    <InputText 
                        v-model="form.serial_no" 
                        class="w-full" 
                        autocomplete="off" 
                        placeholder="Please Enter Asset Serial Number" 
                    />
                    <Message 
                        v-if="errors.serial_no" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.serial_no }}
                    </Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Asset Category</label>
                    <Select 
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
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.category_type_id }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Asset Model</label>
                    <Select 
                        v-model="form.model_type_id" 
                        :options="filteredModels" 
                        optionLabel="model_name" 
                        optionValue="id"
                        class="w-full" 
                        placeholder="Select Model" 
                        :disabled="!form.category_type_id"
                    />
                    <Message 
                        v-if="errors.model_type_id" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.model_type_id }}
                    </Message>
                </div>


                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <InputText 
                        v-model="form.status" 
                        class="w-full" 
                    />
                    <Message 
                        v-if="errors.status" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.status }}
                    </Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Quantity</label>
                    <InputText 
                        v-model="form.qty" 
                        type="number" 
                        class="w-full" 
                    />
                    <Message 
                        v-if="errors.qty" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.qty }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Location</label>
                    <InputText 
                        v-model="form.location" 
                        class="w-full" 
                    />
                    <Message 
                        v-if="errors.location" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.location }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Purchase Cost</label>
                    <InputNumber 
                        v-model="form.purchase_cost" 
                        mode="currency" currency="MYR" 
                        locale="en-US"
                        class="w-full" 
                    />
                    <Message 
                        v-if="errors.purchase_cost" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.purchase_cost }}
                    </Message>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium">Current Value</label>
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
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.current_value }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium">Purchase Date</label>
                    <DatePicker 
                        v-model="form.purchase_date" 
                        class="w-full" 
                    />
                    <Message 
                        v-if="errors.purchase_date" 
                        severity="error" 
                        size="small" 
                        variant="simple"
                    >
                        {{ errors.purchase_date }}
                    </Message>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <Button 
                    label="Create Asset" 
                    icon="pi pi-check" 
                    @click="submit" 
                    :loading="form.processing"
                    :disabled="form.processing"
                />
            </div>
        </div>
    </app-layout>
</template>