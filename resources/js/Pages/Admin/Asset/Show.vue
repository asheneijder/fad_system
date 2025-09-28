<script setup>
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Textarea from "primevue/textarea";
import { ref, onMounted, computed } from "vue";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps({
    asset: Object,
});

const showHistory = ref(false);
const showStatusDialog = ref(false);

const selectedStatus = ref(null);
const statusRemarks = ref("");
const returnedAt = ref(null);

const statuses = [
    { label: "Available", value: "available" },
    { label: "In Maintenance", value: "in_maintenance" },
    { label: "Damaged", value: "damaged" },
    { label: "Lost", value: "lost" },
    { label: "Retired", value: "retired" },
    { label: "Disposed", value: "disposed" },
];

onMounted(() => {
    selectedStatus.value = props.asset.status;
});

const updateStatus = () => {
    const statusesThatRequireReturnDate = [
        "retired",
        "disposed",
        "lost",
        "damaged",
        "available",
    ];

    if (!selectedStatus.value) {
        toast.add({
            severity: "warn",
            summary: "Validation Error",
            detail: "Please select a new status.",
            life: 3000,
        });
        return;
    }

    if (
        statusesThatRequireReturnDate.includes(selectedStatus.value) &&
        !returnedAt.value
    ) {
        toast.add({
            severity: "warn",
            summary: "Validation Error",
            detail: "Return date is required for this status.",
            life: 3000,
        });
        return;
    }

    let formattedReturnDate = null;
    if (returnedAt.value instanceof Date) {
        const d = returnedAt.value;
        const pad = (n) => n.toString().padStart(2, "0");
        formattedReturnDate = `${d.getFullYear()}-${pad(
            d.getMonth() + 1
        )}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(
            d.getMinutes()
        )}:${pad(d.getSeconds())}`;
    }

    router.put(
        route("admin.assets.updateStatus", props.asset.id),
        {
            status: selectedStatus.value,
            remarks: statusRemarks.value,
            returned_at: formattedReturnDate,
        },
        {
            onSuccess: () => {
                showStatusDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Updated",
                    detail: "Asset status updated successfully",
                    life: 3000,
                });
            },
        }
    );
};

const getStatusColor = (status) => {
    const colors = {
        available: "bg-green-100 text-green-800 border-green-200",
        active: "bg-green-100 text-green-800 border-green-200",
        in_maintenance: "bg-yellow-100 text-yellow-800 border-yellow-200",
        damaged: "bg-red-100 text-red-800 border-red-200",
        lost: "bg-red-100 text-red-800 border-red-200",
        retired: "bg-gray-100 text-gray-800 border-gray-200",
        disposed: "bg-gray-100 text-gray-800 border-gray-200",
    };
    return colors[status] || "bg-gray-100 text-gray-800 border-gray-200";
};

const statusIcon = computed(() => {
    const icons = {
        available: "pi-check-circle",
        active: "pi-check-circle",
        in_maintenance: "pi-wrench",
        damaged: "pi-exclamation-triangle",
        lost: "pi-question-circle",
        retired: "pi-history",
        disposed: "pi-trash",
    };
    return icons[props.asset.status] || "pi-info-circle";
});

const formattedPurchaseDate = computed(() => {
    if (!props.asset.purchase_date) return "-";
    return new Date(props.asset.purchase_date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});
</script>

<template>
    <Head title="Asset Details" />
    <AppLayout>
        <div
            class="max-w-6xl mx-auto space-y-8 py-6 px-4 sm:px-6 lg:px-8 transition-all duration-500 ease-in-out"
        >
            <!-- Header Section -->
            <div
                class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
            >
                <div
                    class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="pi pi-box text-blue-500 text-xl"></i>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ asset.asset_name }}
                            </h1>
                        </div>
                        <span
                            class="px-4 py-1.5 text-sm font-medium rounded-full border flex items-center gap-2 transition-all duration-300 transform hover:scale-105"
                            :class="getStatusColor(asset.status)"
                        >
                            <i :class="`pi ${statusIcon} text-sm`"></i>
                            {{
                                asset.status.charAt(0).toUpperCase() +
                                asset.status.slice(1).replace("_", " ")
                            }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 flex items-center">
                        <i class="pi pi-tag mr-2 text-gray-400"></i>
                        Asset Tag:
                        <span class="ml-1 font-medium text-gray-700">{{
                            asset.asset_tag_no
                        }}</span>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Asset Image -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
                    >
                        <div
                            class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex items-center"
                        >
                            <i class="pi pi-image text-gray-500 mr-2"></i>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Asset Image
                            </h2>
                        </div>
                        <div class="p-6">
                            <div
                                v-if="asset.media?.length"
                                class="flex items-center justify-center"
                            >
                                <img
                                    :src="asset.media[0].original_url"
                                    alt="Asset Image"
                                    class="w-full max-w-xs rounded-lg shadow-md transition-all duration-300 hover:shadow-xl transform hover:scale-[1.02]"
                                />
                            </div>
                            <div
                                v-else
                                class="flex items-center justify-center h-48 bg-gray-50 rounded-lg border border-dashed border-gray-300"
                            >
                                <div class="text-center">
                                    <i
                                        class="pi pi-image text-4xl text-gray-400"
                                    ></i>
                                    <p class="mt-2 text-sm text-gray-500">
                                        No image available
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Details -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
                    >
                        <div
                            class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex items-center"
                        >
                            <i class="pi pi-list text-gray-500 mr-2"></i>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Asset Details
                            </h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-id-card mr-2 opacity-70"
                                        ></i>
                                        Serial Number
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{ asset.serial_no || "-" }}
                                    </dd>
                                </div>

                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-folder mr-2 opacity-70"
                                        ></i>
                                        Category
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{
                                            asset.model_type?.category_type
                                                ?.category_name || "-"
                                        }}
                                    </dd>
                                </div>

                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-desktop mr-2 opacity-70"
                                        ></i>
                                        Model
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{
                                            asset.model_type?.model_name || "-"
                                        }}
                                    </dd>
                                </div>

                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-map-marker mr-2 opacity-70"
                                        ></i>
                                        Location
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{ asset.location || "-" }}
                                    </dd>
                                </div>

                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-hashtag mr-2 opacity-70"
                                        ></i>
                                        Quantity
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{ asset.qty }}
                                    </dd>
                                </div>

                                <div
                                    class="group p-3 rounded-lg hover:bg-blue-50 transition-colors duration-300"
                                >
                                    <dt
                                        class="text-sm font-medium text-gray-500 group-hover:text-blue-700 transition-colors duration-300 flex items-center"
                                    >
                                        <i
                                            class="pi pi-calendar mr-2 opacity-70"
                                        ></i>
                                        Purchase Date
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium text-gray-900 group-hover:text-blue-900 transition-colors duration-300"
                                    >
                                        {{ formattedPurchaseDate }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Financial Information -->
                <div
                    class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
                >
                    <div
                        class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-white flex items-center"
                    >
                        <i class="pi pi-dollar text-green-600 mr-2"></i>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Financial Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-6">
                            <div
                                class="p-4 rounded-lg bg-gradient-to-r from-green-50 to-white border border-green-100 shadow-sm"
                            >
                                <dt
                                    class="text-sm font-medium text-gray-500 flex items-center"
                                >
                                    <i
                                        class="pi pi-shopping-cart mr-2 text-green-500"
                                    ></i>
                                    Purchase Cost
                                </dt>
                                <dd
                                    class="mt-2 text-3xl font-bold text-green-700 transition-all duration-300 transform hover:scale-105"
                                >
                                    RM
                                    {{
                                        Number(
                                            asset.purchase_cost || 0
                                        ).toFixed(2)
                                    }}
                                </dd>
                            </div>
                            <div
                                class="p-4 rounded-lg bg-gradient-to-r from-blue-50 to-white border border-blue-100 shadow-sm"
                            >
                                <dt
                                    class="text-sm font-medium text-gray-500 flex items-center"
                                >
                                    <i
                                        class="pi pi-chart-line mr-2 text-blue-500"
                                    ></i>
                                    Current Value
                                </dt>
                                <dd
                                    class="mt-2 text-3xl font-bold text-blue-700 transition-all duration-300 transform hover:scale-105"
                                >
                                    RM
                                    {{
                                        Number(
                                            asset.current_value || 0
                                        ).toFixed(2)
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Assignment Information -->
                <div
                    class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
                >
                    <div
                        class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white flex items-center"
                    >
                        <i class="pi pi-user-plus text-purple-600 mr-2"></i>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Assignment Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div
                                class="p-4 rounded-lg bg-gradient-to-r from-purple-50 to-white border border-purple-100 shadow-sm"
                            >
                                <dt
                                    class="text-sm font-medium text-gray-500 flex items-center"
                                >
                                    <i
                                        class="pi pi-user mr-2 text-purple-500"
                                    ></i>
                                    Currently Assigned To
                                </dt>
                                <dd class="mt-3">
                                    <div
                                        v-if="
                                            asset.current_assignment?.user?.name
                                        "
                                        class="flex items-center p-3 bg-white rounded-lg border border-purple-200 shadow-sm transition-all duration-300 hover:shadow-md"
                                    >
                                        <div
                                            class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center"
                                        >
                                            <i
                                                class="pi pi-user text-purple-600"
                                            ></i>
                                        </div>
                                        <div class="ml-4">
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    asset.current_assignment
                                                        .user.name
                                                }}</span
                                            >
                                            <p
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                Assigned user
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="flex items-center p-3 bg-white rounded-lg border border-gray-200 shadow-sm"
                                    >
                                        <div
                                            class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center"
                                        >
                                            <i
                                                class="pi pi-minus-circle text-gray-400"
                                            ></i>
                                        </div>
                                        <div class="ml-4">
                                            <span class="text-sm text-gray-500"
                                                >Unassigned</span
                                            >
                                            <p
                                                class="text-xs text-gray-400 mt-1"
                                            >
                                                No current assignment
                                            </p>
                                        </div>
                                    </div>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg"
            >
                <div class="px-6 py-5">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:justify-center"
                    >
                        <Button
                            label="View Assignment History"
                            icon="pi pi-history"
                            @click="showHistory = true"
                            class="flex-1 sm:flex-none transition-all duration-300 transform hover:scale-105"
                            outlined
                            severity="secondary"
                        />

                        <Button
                            label="Change Status"
                            icon="pi pi-cog"
                            @click="showStatusDialog = true"
                            severity="info"
                            class="flex-1 sm:flex-none transition-all duration-300 transform hover:scale-105"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment History Dialog -->
        <Dialog
            v-model:visible="showHistory"
            modal
            header="Assignment History"
            :style="{ width: '70vw' }"
            :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
            :contentStyle="{ 'border-radius': '12px' }"
            class="assignment-history-dialog"
        >
            <div class="overflow-x-auto p-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200 bg-gray-50">
                            <th
                                class="px-4 py-3 text-left font-semibold text-gray-900 rounded-tl-lg"
                            >
                                User
                            </th>
                            <th
                                class="px-4 py-3 text-left font-semibold text-gray-900"
                            >
                                Assigned At
                            </th>
                            <th
                                class="px-4 py-3 text-left font-semibold text-gray-900"
                            >
                                Returned At
                            </th>
                            <th
                                class="px-4 py-3 text-left font-semibold text-gray-900 rounded-tr-lg"
                            >
                                Remarks
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(assignment, index) in asset.assignments"
                            :key="assignment.id"
                            class="border-b border-gray-100 hover:bg-blue-50 transition-colors duration-200"
                            :class="{ 'bg-gray-50': index % 2 === 0 }"
                        >
                            <td class="px-4 py-3 text-gray-900 font-medium">
                                <div class="flex items-center">
                                    <i
                                        class="pi pi-user mr-2 text-blue-500"
                                    ></i>
                                    {{ assignment.user?.name || "-" }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ assignment.assigned_at || "-" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ assignment.returned_at || "-" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ assignment.remarks || "-" }}
                            </td>
                        </tr>
                        <tr
                            v-if="
                                !asset.assignments ||
                                asset.assignments.length === 0
                            "
                        >
                            <td
                                colspan="4"
                                class="px-4 py-6 text-center text-gray-500"
                            >
                                <i class="pi pi-info-circle mr-2"></i>
                                No assignment history available
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Dialog>

        <!-- Change Status Dialog -->
        <Dialog
            v-model:visible="showStatusDialog"
            modal
            header="Change Asset Status"
            :style="{ width: '450px' }"
            :breakpoints="{ '1199px': '60vw', '575px': '95vw' }"
            :contentStyle="{ 'border-radius': '12px' }"
        >
            <div class="space-y-6 p-2">
                <div>
                    <label
                        class="block mb-2 text-sm font-medium text-gray-700 flex items-center"
                    >
                        <i class="pi pi-tag mr-2 text-blue-500"></i>
                        New Status
                    </label>
                    <Select
                        v-model="selectedStatus"
                        :options="statuses"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select Status"
                        class="w-full"
                    />
                </div>

                <div
                    v-if="
                        [
                            'retired',
                            'disposed',
                            'lost',
                            'damaged',
                            'available',
                        ].includes(selectedStatus)
                    "
                    class="transition-all duration-500 ease-in-out"
                >
                    <label
                        class="block mb-2 text-sm font-medium text-gray-700 flex items-center"
                    >
                        <i class="pi pi-calendar mr-2 text-blue-500"></i>
                        Return Date
                    </label>
                    <DatePicker
                        v-model="returnedAt"
                        class="w-full"
                        showIcon
                        placeholder="Select return date"
                    />
                </div>

                <div>
                    <label
                        class="block mb-2 text-sm font-medium text-gray-700 flex items-center"
                    >
                        <i class="pi pi-comment mr-2 text-blue-500"></i>
                        Remarks
                    </label>
                    <Textarea
                        v-model="statusRemarks"
                        autoResize
                        rows="4"
                        class="w-full"
                        placeholder="Add any relevant notes or comments..."
                    />
                </div>

                <div
                    class="flex justify-end gap-3 pt-4 border-t border-gray-200"
                >
                    <Button
                        label="Cancel"
                        severity="secondary"
                        outlined
                        icon="pi pi-times"
                        @click="showStatusDialog = false"
                        class="transition-all duration-300 hover:bg-gray-100"
                    />
                    <Button
                        label="Update Status"
                        icon="pi pi-check"
                        @click="updateStatus"
                        class="transition-all duration-300 transform hover:scale-105"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.assignment-history-dialog .p-dialog-content {
    border-radius: 0 0 12px 12px;
}
</style>
