<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from "primevue/usetoast";
import { ref, computed } from "vue";
import Toast from 'primevue/toast';

const confirm = useConfirm();
const toast = useToast();

const months = ref([
    { label: "January", value: 1 },
    { label: "February", value: 2 },
    { label: "March", value: 3 },
    { label: "April", value: 4 },
    { label: "May", value: 5 },
    { label: "June", value: 6 },
    { label: "July", value: 7 },
    { label: "August", value: 8 },
    { label: "September", value: 9 },
    { label: "October", value: 10 },
    { label: "November", value: 11 },
    { label: "December", value: 12 }
]);

const years = ref([
    { label: "2023", value: 2023 },
    { label: "2024", value: 2024 },
    { label: "2025", value: 2025 }
]);

const selectedMonth = ref(null);
const selectedYear = ref(null);
const search = ref("");

// Dummy data for testing
const stationeryStock = ref({
    data: [
        { id: 1, name: "A4 Paper", description: "High-quality paper", category: "Paper", unit: "sheets", bf: 500, in: 100, out: 50, quantity: 550, unit_cost: 0.10, stock_value: 55.00, month: 1, year: 2024 },
        { id: 2, name: "Stapler", description: "Staple for binding", category: "Office Supplies", unit: "pieces", bf: 20, in: 10, out: 5, quantity: 25, unit_cost: 5.00, stock_value: 125.00, month: 2, year: 2024 },
        { id: 3, name: "Pens (Blue)", description: "Writing pens", category: "Writing", unit: "pieces", bf: 100, in: 50, out: 20, quantity: 130, unit_cost: 1.50, stock_value: 195.00, month: 3, year: 2024 },
        { id: 4, name: "Notebooks", description: "Note-taking tools", category: "Paper", unit: "pieces", bf: 50, in: 30, out: 10, quantity: 70, unit_cost: 3.00, stock_value: 210.00, month: 1, year: 2023 },
        { id: 5, name: "Whiteboard Markers", description: "Markers for whiteboards", category: "Markers", unit: "pieces", bf: 30, in: 20, out: 5, quantity: 45, unit_cost: 2.50, stock_value: 112.50, month: 2, year: 2023 },
    ]
});

// Filtered data based on search, month, and year
const filteredStock = computed(() => {
    return stationeryStock.value.data.filter(item => {
        const matchesSearch = search.value === "" || item.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesMonth = selectedMonth.value === null || item.month === selectedMonth.value;
        const matchesYear = selectedYear.value === null || item.year === selectedYear.value;
        return matchesSearch && matchesMonth && matchesYear;
    });
});

const editItem = (id) => {
    toast.add({ severity: "info", summary: "Edit", detail: `Editing item ID: ${id}`, life: 2000 });
};

const deleteItem = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this item?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            stationeryStock.value.data = stationeryStock.value.data.filter(item => item.id !== id);
            toast.add({ severity: "success", summary: "Deleted", detail: "Item deleted successfully", life: 3000 });
        }
    });
};

const createItem = () => {
    toast.add({ severity: "success", summary: "Create", detail: "Opening create item form...", life: 2000 });
};
</script>

<template>
    <app-layout>
        <Toast />
        <ConfirmDialog />
        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <Button 
                    label="Add New Item" 
                    icon="pi pi-plus" 
                    class="p-button-sm p-button-success" 
                    @click="createItem" 
                />

                <div class="flex flex-wrap gap-2">
                    <Dropdown 
                        v-model="selectedMonth" 
                        :options="months" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Select Month" 
                        class="p-dropdown-sm"
                    />
                    <Dropdown 
                        v-model="selectedYear" 
                        :options="years" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Select Year" 
                        class="p-dropdown-sm"
                    />
                    <InputText 
                        v-model="search" 
                        placeholder="Search items..." 
                        class="p-inputtext-sm" 
                    />
                </div>
            </div>

            <DataTable 
                :value="filteredStock" 
                showGridlines 
                :rowHover="true" 
                tableStyle="min-width: 50rem" 
                responsiveLayout="scroll"
            >
                <Column field="name" header="Item Name"></Column>
                <Column field="description" header="Item Description"></Column>
                <Column field="category" header="Category"></Column>
                <Column field="unit" header="Unit"></Column>
                <Column field="bf" header="Brought Forward (BF)"></Column>
                <Column field="in" header="IN"></Column>
                <Column field="out" header="OUT"></Column>
                <Column field="quantity" header="Quantity"></Column>
                <Column field="unit_cost" header="Unit Cost"></Column>
                <Column field="stock_value" header="Stock Value (RM)"></Column>

                <Column header="Actions" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-pencil" 
                            outlined rounded class="mr-2"
                            @click="editItem(slotProps.data.id)" 
                        />
                        <Button 
                            icon="pi pi-trash" 
                            outlined rounded severity="danger"
                            @click="deleteItem(slotProps.data.id)" 
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </app-layout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 200px;
}
.p-dropdown-sm {
    width: 150px;
}
</style>
