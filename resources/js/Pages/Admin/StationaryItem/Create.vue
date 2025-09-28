<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const toast = useToast();

const props = defineProps({
    stationaryItem: Object,
});

// Sample data for demonstration (replace with actual data from props)
const item = ref(
    props.stationaryItem || {
        id: 1,
        description: "A4 Paper 80gsm",
        unit: "Ream",
        unit_cost: 12.5,
        stock_in: 50,
        stock_out: 30,
        stock_quantity: 120,
        status: "Active",
        created_at: "2023-07-15T08:30:00",
        updated_at: "2023-08-20T14:45:00",
    }
);

const stockValue = computed(() => {
    return (item.value.stock_quantity * item.value.unit_cost).toFixed(2);
});

const stockStatus = computed(() => {
    if (item.value.stock_quantity > 50)
        return { label: "High", color: "green" };
    if (item.value.stock_quantity > 10)
        return { label: "Medium", color: "orange" };
    return { label: "Low", color: "red" };
});

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-MY", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const navigateToEdit = () => {
    router.get(route("admin.stationary-items.edit", item.value.id));
};

const navigateBack = () => {
    router.get(route("admin.stationary-items.index"));
};
</script>

<template>
    <Head title="View Stationary Item" />
    <AppLayout>
        <Toast />

        <div class="max-w-4xl mx-auto">
            <!-- Back button -->
            <div class="mb-4">
                <Button
                    icon="pi pi-arrow-left"
                    label="Back to List"
                    class="p-button-text"
                    @click="navigateBack"
                />
            </div>

            <!-- Main card -->
            <div
                class="bg-white rounded-xl overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl"
            >
                <!-- Header with gradient -->
                <div
                    class="bg-gradient-to-r from-blue-300 to-indigo-500 p-6 text-white"
                >
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold flex items-center">
                            <i class="pi pi-box mr-3 text-3xl"></i>
                            {{ item.description }}
                        </h1>
                        <div>
                            <Button
                                icon="pi pi-pencil"
                                label="Edit"
                                class="p-button-rounded p-button-warning"
                                @click="navigateToEdit"
                            />
                        </div>
                    </div>
                    <div class="mt-2 text-blue-100">Item #{{ item.id }}</div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Status badge -->
                    <div class="flex justify-end mb-4">
                        <span
                            :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            ${
                                item.status === 'Active'
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            }`"
                        >
                            <span
                                class="mr-1 h-2 w-2 rounded-full"
                                :class="
                                    item.status === 'Active'
                                        ? 'bg-green-500'
                                        : 'bg-red-500'
                                "
                            ></span>
                            {{ item.status }}
                        </span>
                    </div>

                    <!-- Item details in a beautiful grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left column - Basic info -->
                        <div class="space-y-6">
                            <div
                                class="bg-gray-50 rounded-lg p-5 transform transition-all duration-300 hover:scale-105 hover:shadow-md"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2"
                                >
                                    Basic Information
                                </h2>

                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <div class="w-1/3 text-gray-500">
                                            Description:
                                        </div>
                                        <div class="w-2/3 font-medium">
                                            {{ item.description }}
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <div class="w-1/3 text-gray-500">
                                            Unit:
                                        </div>
                                        <div class="w-2/3 font-medium">
                                            {{ item.unit }}
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <div class="w-1/3 text-gray-500">
                                            Unit Cost:
                                        </div>
                                        <div class="w-2/3 font-medium">
                                            <span class="text-blue-600"
                                                >RM
                                                {{
                                                    item.unit_cost.toFixed(2)
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Timestamps -->
                            <div
                                class="bg-gray-50 rounded-lg p-5 transform transition-all duration-300 hover:scale-105 hover:shadow-md"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2"
                                >
                                    Timestamps
                                </h2>

                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <div class="w-1/3 text-gray-500">
                                            Created:
                                        </div>
                                        <div class="w-2/3 font-medium">
                                            {{ formatDate(item.created_at) }}
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <div class="w-1/3 text-gray-500">
                                            Updated:
                                        </div>
                                        <div class="w-2/3 font-medium">
                                            {{ formatDate(item.updated_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right column - Stock info -->
                        <div class="space-y-6">
                            <!-- Stock metrics -->
                            <div
                                class="bg-gray-50 rounded-lg p-5 transform transition-all duration-300 hover:scale-105 hover:shadow-md"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2"
                                >
                                    Stock Information
                                </h2>

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Stock In -->
                                    <div
                                        class="bg-blue-50 p-4 rounded-lg text-center"
                                    >
                                        <div class="text-sm text-gray-500">
                                            Stock In
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-600"
                                        >
                                            {{ item.stock_in }}
                                        </div>
                                    </div>

                                    <!-- Stock Out -->
                                    <div
                                        class="bg-amber-50 p-4 rounded-lg text-center"
                                    >
                                        <div class="text-sm text-gray-500">
                                            Stock Out
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-amber-600"
                                        >
                                            {{ item.stock_out }}
                                        </div>
                                    </div>

                                    <!-- Stock Quantity -->
                                    <div
                                        class="bg-indigo-50 p-4 rounded-lg text-center"
                                    >
                                        <div class="text-sm text-gray-500">
                                            Stock Quantity
                                        </div>
                                        <div
                                            class="text-2xl font-bold"
                                            :class="`text-${stockStatus.color}-600`"
                                        >
                                            {{ item.stock_quantity }}
                                            <span
                                                class="text-xs ml-1 px-2 py-1 rounded-full"
                                                :class="`bg-${stockStatus.color}-100 text-${stockStatus.color}-800`"
                                            >
                                                {{ stockStatus.label }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Stock Value -->
                                    <div
                                        class="bg-emerald-50 p-4 rounded-lg text-center"
                                    >
                                        <div class="text-sm text-gray-500">
                                            Stock Value
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-emerald-600"
                                        >
                                            RM {{ stockValue }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock visualization -->
                            <div
                                class="bg-gray-50 rounded-lg p-5 transform transition-all duration-300 hover:scale-105 hover:shadow-md"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2"
                                >
                                    Stock Level
                                </h2>

                                <div class="relative pt-1">
                                    <div
                                        class="flex mb-2 items-center justify-between"
                                    >
                                        <div>
                                            <span
                                                class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full"
                                                :class="`text-${stockStatus.color}-600 bg-${stockStatus.color}-200`"
                                            >
                                                {{ stockStatus.label }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                class="text-xs font-semibold inline-block text-blue-600"
                                            >
                                                {{
                                                    Math.min(
                                                        100,
                                                        Math.round(
                                                            (item.stock_quantity /
                                                                200) *
                                                                100
                                                        )
                                                    )
                                                }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200"
                                    >
                                        <div
                                            :style="{
                                                width:
                                                    Math.min(
                                                        100,
                                                        Math.round(
                                                            (item.stock_quantity /
                                                                200) *
                                                                100
                                                        )
                                                    ) + '%',
                                            }"
                                            :class="`shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-${stockStatus.color}-500`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add subtle hover effects */
.hover-lift {
    transition: transform 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
}

/* Add animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out;
}
</style>
