<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import { ref } from 'vue';
import { router, Head } from "@inertiajs/vue3";

const props = defineProps({
    log: Object
});

// Breadcrumb
const home = ref({
    icon: 'pi pi-home',
    route: '/dashboard'
});

const items = ref([
    { label: 'Admin', route: '/admin/audit-logs' },
    { label: 'Audit Logs', route: '/admin/audit-logs' },
    { label: `Log #${props.log.id}` }
]);

// Methods
const goBack = () => {
    router.get(route('admin.audit-logs.index'));
};

const getEventSeverity = (event) => {
    const severityMap = {
        created: 'success',
        updated: 'info',
        deleted: 'danger',
        restored: 'warning',
    };
    return severityMap[event] || 'secondary';
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};
</script>

<template>
    <Head :title="`Audit Log #${log.id}`" />
    <AppLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Audit Log #{{ log.id }}</h1>
                    <p class="text-gray-600 mt-1">Detailed view of system activity</p>
                </div>
                <Button 
                    label="Back to Logs" 
                    icon="pi pi-arrow-left" 
                    severity="secondary"
                    @click="goBack"
                />
            </div>

            <!-- Log Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info Card -->
                    <Card>
                        <template #title>Activity Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Description</label>
                                        <p class="mt-1 text-gray-900">{{ log.description }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Event</label>
                                        <Badge 
                                            :value="log.event" 
                                            :severity="getEventSeverity(log.event)"
                                            class="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Log Type</label>
                                        <p class="mt-1 text-gray-900">{{ log.log_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Timestamp</label>
                                        <p class="mt-1 text-gray-900">{{ formatDateTime(log.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Properties Card -->
                    <Card v-if="log.properties && Object.keys(log.properties).length > 0">
                        <template #title>Properties</template>
                        <template #content>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Causer Information -->
                    <Card v-if="log.causer">
                        <template #title>Performed By</template>
                        <template #content>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Name</label>
                                    <p class="mt-1 text-gray-900">{{ log.causer.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Email</label>
                                    <p class="mt-1 text-gray-900">{{ log.causer.email }}</p>
                                </div>
                                <div v-if="log.causer.department">
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="mt-1 text-gray-900">{{ log.causer.department }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Subject Information -->
                    <Card v-if="log.subject">
                        <template #title>Subject</template>
                        <template #content>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Type</label>
                                    <p class="mt-1 text-gray-900">{{ log.subject_type ? log.subject_type.split('\\').pop() : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">ID</label>
                                    <p class="mt-1 text-gray-900">{{ log.subject_id }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Additional Info -->
                    <Card>
                        <template #title>Additional Information</template>
                        <template #content>
                            <div class="space-y-3">
                                <div v-if="log.properties?.ip_address">
                                    <label class="block text-sm font-medium text-gray-500">IP Address</label>
                                    <p class="mt-1 text-gray-900">{{ log.properties.ip_address }}</p>
                                </div>
                                <div v-if="log.properties?.user_agent">
                                    <label class="block text-sm font-medium text-gray-500">User Agent</label>
                                    <p class="mt-1 text-gray-900 text-xs">{{ log.properties.user_agent }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>