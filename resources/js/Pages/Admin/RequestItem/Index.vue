<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import TabMenu from 'primevue/tabmenu';
import { ref, computed } from "vue";
import { router, Head } from "@inertiajs/vue3";

const search = ref("");
const activeTab = ref(0);

// Dummy data
const items = ref([
    { id: 1, name: "Laptop", category: "Electronics", status: "Pending" },
    { id: 2, name: "Office Chair", category: "Furniture", status: "Done" },
    { id: 3, name: "Sedan", category: "Vehicles", status: "Pending" },
    { id: 4, name: "Smartphone", category: "Electronics", status: "Done" },
]);

// Tabs
const tabs = ref([
    { label: "Pending", value: "Pending" },
    { label: "Done", value: "Done" }
]);

// Filtered items based on active tab and search input
const filteredItems = computed(() => {
    return items.value.filter(
        (item) => item.status === tabs.value[activeTab.value].value &&
        item.name.toLowerCase().includes(search.value.toLowerCase())
    );
});

const createAsset = () => {
    router.get(route("admin.request.create"));
};
</script>

<template>
    <!-- <Head title="Request Item" /> -->
    <app-layout>
        <div class="card">
            <div class="flex justify-between mb-4">
                <Button label="Request New Item" icon="pi pi-plus" class="p-button-sm p-button-success"
                    @click="createAsset" />
                <InputText v-model="search" placeholder="Search request items..." class="p-inputtext-sm" />
            </div>
            <!-- Tabs for Filtering -->
            <TabMenu v-model:activeIndex="activeTab" :model="tabs" class="mb-4"/>


            <!-- Data Table -->
            <DataTable :value="filteredItems" class="p-datatable-sm">
                <Column field="id" header="ID"></Column>
                <Column field="name" header="Item Name"></Column>
                <Column field="category" header="Category"></Column>
                <Column field="status" header="Status"></Column>
            </DataTable>

            <!-- Empty State -->
            <template v-if="filteredItems.length === 0">
                <div class="empty-state">
                    <p>No request items found.</p>
                </div>
            </template>
        </div>
    </app-layout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
.empty-state {
    text-align: center;
    padding: 20px;
    color: #888;
    font-style: italic;
}
</style>
