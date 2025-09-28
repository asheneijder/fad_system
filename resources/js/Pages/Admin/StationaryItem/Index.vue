<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    stationaryItems: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const items = ref(props.stationaryItems);

watch(
    () => props.stationaryItems,
    (newData) => {
        items.value = newData;
    }
);

watch(search, (newSearch) => {
    router.get(
        route("admin.stationary-items.index"),
        { search: newSearch },
        { preserveState: true, replace: true }
    );
});

const onPageChange = (event) => {
    router.get(
        route("admin.stationary-items.index"),
        {
            search: search.value,
            page: event.page + 1,
        },
        { preserveState: true, replace: true }
    );
};

const createItem = () => router.get(route("admin.stationary-items.create"));
const editItem = (id) => router.get(route("admin.stationary-items.edit", id));
const viewItem = (id) => router.get(route("admin.stationary-items.show", id));
const deleteItem = (id) => {
    confirm.require({
        message: "Are you sure you want to delete this item?",
        header: "Delete Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(
                route("admin.stationary-items.destroy", { item: id }),
                {
                    preserveState: true,
                    replace: true,
                    onSuccess: () => {
                        toast.add({
                            severity: "success",
                            summary: "Deleted",
                            detail: "Item deleted successfully",
                            life: 3000,
                        });
                    },
                }
            );
        },
    });
};
</script>

<template>
    <Head title="Stationary Items" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="p-6 card">
            <div
                class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center"
            >
                <Button
                    label="Create New Item"
                    icon="pi pi-plus"
                    class="p-button-success p-button-sm"
                    @click="createItem"
                />
                <InputText
                    v-model="search"
                    placeholder="Search items..."
                    class="w-full p-inputtext-sm sm:w-64"
                />
            </div>

            <DataTable
                :value="items.data"
                showGridlines
                :rowHover="true"
                paginator
                lazy
                :rows="items.per_page"
                :totalRecords="items.total"
                :first="(items.current_page - 1) * items.per_page"
                @page="onPageChange"
                responsiveLayout="scroll"
                tableStyle="min-width: 70rem"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <p class="mt-2 text-lg font-medium text-gray-500">
                            No items found
                        </p>
                        <p class="text-sm text-gray-400">
                            Try adjusting your filters or add a new item.
                        </p>
                    </div>
                </template>

                <!-- Table Columns -->
                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{
                            (items.current_page - 1) * items.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>

                <Column field="description" header="Description" />
                <Column field="unit" header="Unit" />
                <Column field="unit_cost" header="Unit Cost (RM)" />
                <Column header="Stock In">
                    <template #body="slotProps">
                        {{
                            slotProps.data.stationary_item_movements[0]?.in || 0
                        }}
                    </template>
                </Column>
                <Column header="Stock Out">
                    <template #body="slotProps">
                        {{
                            slotProps.data.stationary_item_movements[0]?.out ||
                            0
                        }}
                    </template>
                </Column>
                <Column header="Stock Quantity">
                    <template #body="slotProps">
                        {{
                            slotProps.data.stationary_item_movements[0]
                                ?.closing_balance || 0
                        }}
                    </template>
                </Column>
                <Column header="Stock Value (RM)">
                    <template #body="slotProps">
                        {{
                            (slotProps.data.stationary_item_movements[0]
                                ?.closing_balance || 0) *
                            (slotProps.data.unit_cost || 0)
                        }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="slotProps">
                        <span
                            :class="{
                                'bg-green-100 text-green-800 border border-green-300':
                                    slotProps.data.status,
                                'bg-red-100 text-red-800 border border-red-300':
                                    !slotProps.data.status,
                            }"
                            class="px-3 py-1 text-sm font-semibold rounded-full"
                        >
                            {{ slotProps.data.status ? "Active" : "Inactive" }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 14rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-eye"
                            outlined
                            rounded
                            severity="info"
                            class="mr-2"
                            @click="viewItem(slotProps.data.id)"
                        />
                        <Button
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="editItem(slotProps.data.id)"
                        />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="deleteItem(slotProps.data.id)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
</style>
