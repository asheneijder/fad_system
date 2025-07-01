<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/sakai/layout/AppLayout.vue'

const props = defineProps({
    license: Object,
})
</script>

<template>

    <Head title="License Details" />
    <AppLayout>
        <div class="p-6 bg-white rounded shadow-md">
            <h2 class="mb-6 text-2xl font-bold text-gray-800">License Information</h2>

            <div class="grid grid-cols-2 gap-6 text-sm">
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">License Name</p>
                    <p class="font-semibold text-gray-800">{{ license.license_name }}</p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Product Key</p>
                    <p class="font-mono text-blue-700 break-all">{{ license.product_key }}</p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Expiration Date</p>
                    <p class="text-gray-800">
                        {{ license.expiration_date }}
                        <span
                            :class="license.is_expired ? 'text-red-600 ml-2 font-semibold' : 'text-green-600 ml-2 font-semibold'">
                            ({{ license.is_expired ? 'Expired' : parseInt(license.days_remaining) + ' days left' }})
                        </span>
                    </p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Minimum Quantity</p>
                    <p class="text-gray-800">{{ license.min_qty }}</p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Available Quantity</p>
                    <p class="text-gray-800">{{ license.available_qty }}</p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Status</p>
                    <p :class="license.status ? 'text-green-600' : 'text-red-600'">
                        {{ license.status ? 'Active' : 'Inactive' }}
                    </p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Created At</p>
                    <p class="text-gray-800">{{ license.created_at }}</p>
                </div>

                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Updated At</p>
                    <p class="text-gray-800">{{ license.updated_at }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
