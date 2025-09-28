<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { computed } from "vue";

const props = defineProps({
    license: Object,
});

const statusColor = computed(() => {
    return props.license.status ? "emerald" : "rose";
});

const expirationStatus = computed(() => {
    if (props.license.is_expired) return { text: "EXPIRED", color: "red" };
    const days = parseInt(props.license.days_remaining);
    if (days <= 7) return { text: `${days} days left`, color: "amber" };
    return { text: `${days} days remaining`, color: "green" };
});
</script>

<template>
    <Head title="License Details" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6 animate-fadeIn">
            <!-- Header Section with improved styling -->
            <div class="mb-8 text-center">
                <h2
                    class="text-3xl font-bold text-gray-900 mb-2 relative inline-block"
                >
                    License Information
                    <span
                        class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-600 transform scale-x-0 transition-transform duration-300 group-hover:scale-x-100"
                    ></span>
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Detailed view of your license configuration and status
                </p>
            </div>

            <!-- Main Card with enhanced shadows and animations -->
            <div
                class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-2xl"
            >
                <!-- Status Banner with improved gradients -->
                <div
                    class="px-6 py-5 border-b transition-colors duration-300"
                    :class="{
                        'bg-gradient-to-r from-green-50 via-emerald-50 to-teal-50 border-green-100':
                            license.status,
                        'bg-gradient-to-r from-red-50 via-rose-50 to-pink-50 border-red-100':
                            !license.status,
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <div
                                    :class="{
                                        'bg-green-500 ring-4 ring-green-100':
                                            license.status,
                                        'bg-red-500 ring-4 ring-red-100':
                                            !license.status,
                                    }"
                                    class="w-3 h-3 rounded-full transition-all duration-300 animate-pulse"
                                ></div>
                                <span
                                    class="text-lg font-semibold transition-colors duration-300"
                                    :class="{
                                        'text-green-800': license.status,
                                        'text-red-800': !license.status,
                                    }"
                                >
                                    {{
                                        license.status
                                            ? "Active License"
                                            : "Inactive License"
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span
                                class="text-sm font-medium px-3 py-1 rounded-full transition-colors duration-300"
                                :class="{
                                    'bg-red-100 text-red-700':
                                        license.is_expired,
                                    'bg-green-100 text-green-700':
                                        !license.is_expired,
                                }"
                            >
                                {{
                                    license.is_expired
                                        ? "EXPIRED"
                                        : parseInt(license.days_remaining) +
                                          " days remaining"
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Content Grid with improved card designs -->
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- License Name with enhanced styling -->
                        <div
                            class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg p-3 flex-shrink-0 shadow-md"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-blue-700 mb-1 uppercase tracking-wider"
                                    >
                                        License Name
                                    </p>
                                    <p
                                        class="text-xl font-bold text-gray-900 truncate"
                                    >
                                        {{ license.license_name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Product Key with enhanced styling -->
                        <div
                            class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg p-3 flex-shrink-0 shadow-md"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 7a2 2 0 012 2m-2-2a2 2 0 00-2 2m2-2h.01M9 20h6a2 2 0 002-2V9a2 2 0 00-2-2H9a2 2 0 00-2 2v9a2 2 0 002 2zm3-7a1 1 0 100-2 1 1 0 000 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-purple-700 mb-1 uppercase tracking-wider"
                                    >
                                        Product Key
                                    </p>
                                    <p
                                        class="font-mono text-sm text-gray-700 bg-white px-4 py-3 rounded-lg border shadow-inner break-all"
                                    >
                                        {{ license.product_key }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Expiration Date with enhanced styling -->
                        <div
                            class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-6 border border-orange-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg p-3 flex-shrink-0 shadow-md"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-orange-700 mb-1 uppercase tracking-wider"
                                    >
                                        Expiration Date
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        {{ license.expiration_date }}
                                    </p>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mt-2 shadow-sm"
                                        :class="{
                                            'bg-red-100 text-red-800':
                                                license.is_expired,
                                            'bg-amber-100 text-amber-800':
                                                !license.is_expired &&
                                                parseInt(
                                                    license.days_remaining
                                                ) <= 7,
                                            'bg-green-100 text-green-800':
                                                !license.is_expired &&
                                                parseInt(
                                                    license.days_remaining
                                                ) > 7,
                                        }"
                                    >
                                        {{
                                            license.is_expired
                                                ? "Expired"
                                                : parseInt(
                                                      license.days_remaining
                                                  ) + " days left"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quantities with enhanced styling -->
                        <div
                            class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl p-6 border border-teal-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg p-3 flex-shrink-0 shadow-md"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-teal-700 mb-3 uppercase tracking-wider"
                                    >
                                        Quantity Information
                                    </p>
                                    <div class="space-y-3">
                                        <div
                                            class="flex justify-between items-center bg-white p-3 rounded-lg shadow-sm"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-600"
                                                >Minimum:</span
                                            >
                                            <span
                                                class="font-bold text-gray-900 text-lg"
                                                >{{ license.min_qty }}</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between items-center bg-white p-3 rounded-lg shadow-sm"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-600"
                                                >Available:</span
                                            >
                                            <span
                                                class="font-bold text-gray-900 text-lg"
                                                >{{
                                                    license.available_qty
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamps Section with improved styling -->
                    <div class="mt-10 pt-8 border-t border-gray-100">
                        <h3
                            class="text-xl font-bold text-gray-900 mb-6 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-gray-700"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            Timeline
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                class="bg-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-full p-3 shadow-md"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-700 uppercase tracking-wider"
                                        >
                                            Created
                                        </p>
                                        <p
                                            class="text-base text-gray-600 mt-1 font-medium"
                                        >
                                            {{ license.created_at }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="bg-gray-50 rounded-xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                            >
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-full p-3 shadow-md"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-700 uppercase tracking-wider"
                                        >
                                            Last Updated
                                        </p>
                                        <p
                                            class="text-base text-gray-600 mt-1 font-medium"
                                        >
                                            {{ license.updated_at }}
                                        </p>
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
.animate-fadeIn {
    animation: fadeIn 0.5s ease-in-out;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

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

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
</style>
