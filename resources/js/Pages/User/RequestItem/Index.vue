<script setup>
import { ref, onMounted, computed } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataView from "primevue/dataview";
import Select from "primevue/select";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import InputText from "primevue/inputtext";
import { router, Head } from "@inertiajs/vue3";

const products = ref([]);
const loading = ref(true);
const searchTerm = ref("");

// Sample data
onMounted(() => {
    setTimeout(() => {
        products.value = [
            {
                id: 1,
                name: "A4 Paper (500 sheets)",
                category: "Paper Supplies",
                image: "a4-paper.jpg",
                price: 15.0,
                status: "Pending",
                inventoryStatus: "INSTOCK",
                count: 1,
            },
            {
                id: 2,
                name: "Ballpoint Pen - Blue",
                category: "Writing Tools",
                image: "pen-blue.jpg",
                price: 1.5,
                status: "Approved",
                inventoryStatus: "LOWSTOCK",
                count: 0,
            },
            {
                id: 3,
                name: "Whiteboard Marker - Red",
                category: "Markers",
                image: "marker-red.jpg",
                price: 3.0,
                status: "Rejected",
                inventoryStatus: "OUTOFSTOCK",
                count: 0,
            },
        ];
        loading.value = false;
    }, 1000);
});

// Sort
const sortKey = ref();
const sortOrder = ref();
const sortField = ref();
const sortOptions = ref([
    { label: "Price High to Low", value: "!price" },
    { label: "Price Low to High", value: "price" },
]);

const onSortChange = (event) => {
    const value = event.value.value;
    sortKey.value = event.value;
    sortOrder.value = value.startsWith("!") ? -1 : 1;
    sortField.value = value.replace("!", "");
};

// Status
const getSeverity = (product) => {
    switch (product.inventoryStatus) {
        case "INSTOCK":
            return "success";
        case "LOWSTOCK":
            return "warn";
        case "OUTOFSTOCK":
            return "danger";
        default:
            return null;
    }
};

// Quantity Controls
const increaseQty = (item) => {
    item.count++;
};
const decreaseQty = (item) => {
    if (item.count > 0) item.count--;
};

// Filtered Products
const filteredProducts = computed(() => {
    if (!searchTerm.value) return products.value;
    return products.value.filter((item) =>
        item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        item.category.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});
</script>

<template>
    <Head title="Request Item" />
    <app-layout>
        <div class="space-y-4 card">
            <!-- Filter & Sort -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <InputText v-model="searchTerm" placeholder="Search item..." class="w-full md:w-1/3" />
                <Select v-model="sortKey" :options="sortOptions" optionLabel="label" placeholder="Sort By Price"
                    @change="onSortChange" class="w-full md:w-64" />
            </div>

            <!-- Skeleton Loader -->
            <div v-if="loading">
                <div v-for="n in 3" :key="n" class="flex gap-4 mb-6">
                    <Skeleton shape="rectangle" class="w-40 h-32" />
                    <div class="flex-1 space-y-3">
                        <Skeleton width="50%" height="1.5rem" />
                        <Skeleton width="70%" height="1rem" />
                        <Skeleton width="30%" height="1rem" />
                        <Skeleton width="20%" height="2rem" />
                    </div>
                </div>
            </div>

            <!-- Product List -->
            <DataView v-else :value="filteredProducts" :sortOrder="sortOrder" :sortField="sortField" layout="list"
                paginator :rows="9">
                <template #list="slotProps">
                    <div class="flex flex-col">
                        <div v-for="(item, index) in slotProps.items" :key="index">
                            <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center"
                                :class="{ 'border-t border-surface-200 dark:border-surface-700': index !== 0 }">
                                <!-- Image -->
                                <div class="relative md:w-40">
                                    <img class="block w-full mx-auto rounded" :src="`/images/stationery/${item.image}`"
                                        :alt="item.name" />
                                    <div class="absolute bg-black/70 rounded-border" style="left: 4px; top: 4px">
                                        <Tag :value="item.inventoryStatus" :severity="getSeverity(item)" />
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex flex-col justify-between flex-1 gap-6 md:flex-row md:items-center">
                                    <div class="flex flex-row items-start justify-between gap-2 md:flex-col">
                                        <div>
                                            <span class="text-sm font-medium text-surface-500 dark:text-surface-400">{{
                                                item.category }}</span>
                                            <div class="mt-2 text-lg font-medium">{{ item.name }}</div>
                                        </div>
                                        <div class="p-1 rounded-full bg-surface-100">
                                            <div
                                                class="flex items-center justify-center gap-2 px-2 py-1 bg-white rounded-full shadow">
                                                <span class="text-sm font-medium text-black">{{ item.status }}</span>
                                                <i class="text-yellow-500 pi pi-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col gap-4 md:items-end">
                                        <span class="text-xl font-semibold">RM {{ item.price.toFixed(2) }}</span>
                                        <div class="flex items-center gap-2">
                                            <Button icon="pi pi-minus" outlined rounded size="small"
                                                @click="decreaseQty(item)" :disabled="item.count === 0" />
                                            <span class="w-6 text-lg font-bold text-center">{{ item.count }}</span>
                                            <Button icon="pi pi-plus" outlined rounded size="small"
                                                @click="increaseQty(item)"
                                                :disabled="item.inventoryStatus === 'OUTOFSTOCK'" />
                                        </div>
                                        <Button icon="pi pi-send" label="Add to Request"
                                            :disabled="item.count === 0" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </DataView>
        </div>
    </app-layout>
</template>
