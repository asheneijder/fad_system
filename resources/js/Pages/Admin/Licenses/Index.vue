<script setup>
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { Head } from "@inertiajs/vue3";

import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

import Rating from 'primevue/rating';

import Textarea from 'primevue/textarea';

import Select from 'primevue/select';

import InputNumber from 'primevue/inputnumber';

import Dialog from 'primevue/dialog';

import Tag from 'primevue/tag';

const toast = useToast();
const dt = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const product = ref({});
const selectedProducts = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});
const submitted = ref(false);
const statuses = ref([
    { label: 'INSTOCK', value: 'instock' },
    { label: 'LOWSTOCK', value: 'lowstock' },
    { label: 'OUTOFSTOCK', value: 'outofstock' }
]);

// Dummy product data
const products = ref([
    {
        id: '1',
        code: 'P001',
        name: 'Product A',
        image: 'product-a.jpg', // Replace with your image path or leave as placeholder
        price: 199.99,
        category: 'Electronics',
        rating: 4.5,
        inventoryStatus: 'INSTOCK',
        quantity: 10,
        description: 'Description for Product A'
    },
    {
        id: '2',
        code: 'P002',
        name: 'Product B',
        image: 'product-b.jpg',
        price: 299.99,
        category: 'Home Appliances',
        rating: 3.5,
        inventoryStatus: 'LOWSTOCK',
        quantity: 5,
        description: 'Description for Product B'
    },
    {
        id: '3',
        code: 'P003',
        name: 'Product C',
        image: 'product-c.jpg',
        price: 99.99,
        category: 'Accessories',
        rating: 5,
        inventoryStatus: 'OUTOFSTOCK',
        quantity: 0,
        description: 'Description for Product C'
    }
]);

const formatCurrency = (value) => {
    if (value) return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};

const openNew = () => {
    product.value = {};
    submitted.value = false;
    productDialog.value = true;
};

const hideDialog = () => {
    productDialog.value = false;
    submitted.value = false;
};

const saveProduct = () => {
    submitted.value = true;

    if (product?.value.name?.trim()) {
        if (product.value.id) {
            product.value.inventoryStatus = product.value.inventoryStatus.value
                ? product.value.inventoryStatus.value
                : product.value.inventoryStatus;
            products.value[findIndexById(product.value.id)] = product.value;
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Updated', life: 3000 });
        } else {
            product.value.id = createId();
            product.value.code = createId();
            product.value.image = 'product-placeholder.svg';
            product.value.inventoryStatus = product.value.inventoryStatus
                ? product.value.inventoryStatus.value
                : 'INSTOCK';
            products.value.push(product.value);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Created', life: 3000 });
        }

        productDialog.value = false;
        product.value = {};
    }
};

const editProduct = (prod) => {
    product.value = { ...prod };
    productDialog.value = true;
};

const confirmDeleteProduct = (prod) => {
    product.value = prod;
    deleteProductDialog.value = true;
};

const deleteProduct = () => {
    products.value = products.value.filter((val) => val.id !== product.value.id);
    deleteProductDialog.value = false;
    product.value = {};
    toast.add({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
};

const findIndexById = (id) => {
    let index = -1;
    for (let i = 0; i < products.value.length; i++) {
        if (products.value[i].id === id) {
            index = i;
            break;
        }
    }
    return index;
};

const createId = () => {
    let id = '';
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (let i = 0; i < 5; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return id;
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const confirmDeleteSelected = () => {
    deleteProductsDialog.value = true;
};

const deleteSelectedProducts = () => {
    products.value = products.value.filter((val) => !selectedProducts.value.includes(val));
    deleteProductsDialog.value = false;
    selectedProducts.value = null;
    toast.add({ severity: 'success', summary: 'Successful', detail: 'Products Deleted', life: 3000 });
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'INSTOCK':
            return 'success';
        case 'LOWSTOCK':
            return 'warn';
        case 'OUTOFSTOCK':
            return 'danger';
        default:
            return null;
    }
};
</script>

<template>

    <Head title="Asset" />
    <app-layout>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
                    <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected"
                        :disabled="!selectedProducts || !selectedProducts.length" />
                </template>
                <template #end>
                    <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                </template>
            </Toolbar>

            <DataTable ref="dt" v-model:selection="selectedProducts" :value="products" dataKey="id" paginator :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products">
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="m-0">Manage Products</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Search..." />
                        </IconField>
                    </div>
                </template>

                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="code" header="Code" sortable style="min-width: 12rem"></Column>
                <Column field="name" header="Name" sortable style="min-width: 16rem"></Column>
                <Column header="Image">
                    <template #body="slotProps">
                        <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`"
                            :alt="slotProps.data.image" class="rounded" style="width: 64px" />
                    </template>
                </Column>
                <Column field="price" header="Price" sortable style="min-width: 8rem">
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.price) }}
                    </template>
                </Column>
                <Column field="category" header="Category" sortable style="min-width: 10rem"></Column>
                <Column field="rating" header="Reviews" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        <Rating :modelValue="slotProps.data.rating" readonly />
                    </template>
                </Column>
                <Column field="inventoryStatus" header="Status" sortable style="min-width: 12rem">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.inventoryStatus"
                            :severity="getStatusLabel(slotProps.data.inventoryStatus)" />
                    </template>
                </Column>
                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2"
                            @click="editProduct(slotProps.data)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger"
                            @click="confirmDeleteProduct(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="productDialog" :style="{ width: '450px' }" header="Product Details" :modal="true">
            <div class="flex flex-col gap-6">
                <img v-if="product.image" :src="`https://primefaces.org/cdn/primevue/images/product/${product.image}`"
                    :alt="product.image" class="block pb-4 m-auto" />
                <div>
                    <label for="name" class="block mb-3 font-bold">Name</label>
                    <InputText id="name" v-model.trim="product.name" required autofocus
                        :invalid="submitted && !product.name" fluid />
                    <small v-if="submitted && !product.name" class="text-red-500">Name is required.</small>
                </div>
                <div>
                    <label for="description" class="block mb-3 font-bold">Description</label>
                    <Textarea id="description" v-model="product.description" required rows="3" fluid />
                </div>
                <div>
                    <label for="inventoryStatus" class="block mb-3 font-bold">Inventory Status</label>
                    <Select id="inventoryStatus" v-model="product.inventoryStatus" :options="statuses"
                        optionLabel="label" placeholder="Select a Status" fluid></Select>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <label for="price" class="block mb-3 font-bold">Price</label>
                        <InputNumber id="price" v-model="product.price" mode="currency" currency="USD" locale="en-US"
                            fluid />
                    </div>
                    <div class="col-span-6">
                        <label for="quantity" class="block mb-3 font-bold">Quantity</label>
                        <InputNumber id="quantity" v-model="product.quantity" integeronly fluid />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" @click="saveProduct" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteProductDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product">
                    Are you sure you want to delete <b>{{ product.name }}</b>?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteProduct" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteProductsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product">
                    Are you sure you want to delete the selected products?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedProducts" />
            </template>
        </Dialog>
    </app-layout>
</template>

<style scoped lang="scss">
/* Add your custom styles here */
</style>
