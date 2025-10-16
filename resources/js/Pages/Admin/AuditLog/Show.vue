<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    log: Object
});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Audit Logs', url: route('admin.audit-logs.index') },
    { label: 'Log Details' }
];

const getActionSeverity = (action) => {
    switch (action) {
        case 'created': return 'success';
        case 'updated': return 'warning';
        case 'deleted': return 'danger';
        default: return 'info';
    }
};

const getActionLabel = (description) => {
    const action = description.toLowerCase();
    if (action.includes('created')) return 'Created';
    if (action.includes('updated')) return 'Updated';
    if (action.includes('deleted')) return 'Deleted';
    return description;
};

const formatDate = (date) => {
    if (!date) return '—';
    try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    } catch (error) {
        return '—';
    }
};

const formatModelName = (model) => {
    if (!model) return '—';
    return model.split('\\').pop();
};

const getPropertyChanges = (log) => {
    if (!log.properties) return null;
    
    if (log.description === 'updated' && log.properties.attributes && log.properties.old) {
        return {
            old: log.properties.old,
            new: log.properties.attributes
        };
    }
    
    if (log.description === 'created' && log.properties.attributes) {
        return {
            new: log.properties.attributes
        };
    }
    
    if (log.description === 'deleted' && log.properties.old) {
        return {
            old: log.properties.old
        };
    }
    
    return log.properties;
};
</script>

<template>
    <Head :title="`Audit Log #${log.id}`" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:text-blue-800" @click="$inertia.visit(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Audit Log Details</h1>
                    <p class="mt-1 text-gray-500">Detailed view of system activity</p>
                </div>
                <Button label="Back to Logs" icon="pi pi-arrow-left" severity="secondary"
                    @click="$inertia.visit(route('admin.audit-logs.index'))" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Basic Information -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Action Card -->
                    <Card>
                        <template #title>Action Information</template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Action Type</p>
                                    <Badge :value="getActionLabel(log.description)"
                                        :severity="getActionSeverity(log.description)"
                                        class="mt-1 capitalize text-lg" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Model</p>
                                    <p class="mt-1 text-base font-semibold text-gray-900">
                                        {{ formatModelName(log.subject_type) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Model ID</p>
                                    <p class="mt-1 text-base font-semibold text-gray-900">
                                        {{ log.subject_id || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- User Card -->
                    <Card>
                        <template #title>User Information</template>
                        <template #content>
                            <div v-if="log.causer" class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Name</p>
                                    <p class="mt-1 text-base font-semibold text-gray-900">{{ log.causer.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Email</p>
                                    <p class="mt-1 text-base text-gray-900">{{ log.causer.email }}</p>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-4">
                                <i class="pi pi-server text-2xl mb-2"></i>
                                <p>System Action</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Timestamp Card -->
                    <Card>
                        <template #title>Timestamps</template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Created At</p>
                                    <p class="mt-1 text-base font-semibold text-gray-900">
                                        {{ formatDate(log.created_at) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Updated At</p>
                                    <p class="mt-1 text-base font-semibold text-gray-900">
                                        {{ formatDate(log.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right Column - Changes -->
                <div class="lg:col-span-2">
                    <Card>
                        <template #title>Property Changes</template>
                        <template #content>
                            <div v-if="getPropertyChanges(log)" class="overflow-hidden border border-gray-200 rounded-lg">
                                <div v-if="log.description === 'updated'" class="grid grid-cols-3 text-sm border-b border-gray-200 bg-gray-50">
                                    <div class="p-3 font-medium">Field</div>
                                    <div class="p-3 font-medium text-red-600">Old Value</div>
                                    <div class="p-3 font-medium text-green-600">New Value</div>
                                </div>
                                <div v-else class="grid grid-cols-2 text-sm border-b border-gray-200 bg-gray-50">
                                    <div class="p-3 font-medium">Field</div>
                                    <div class="p-3 font-medium" :class="log.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                        {{ log.description === 'created' ? 'Value' : 'Old Value' }}
                                    </div>
                                </div>
                                
                                <div v-for="(value, key) in getPropertyChanges(log).new || getPropertyChanges(log).old" 
                                    :key="key" class="grid border-b border-gray-100 last:border-b-0"
                                    :class="log.description === 'updated' ? 'grid-cols-3' : 'grid-cols-2'">
                                    
                                    <div class="p-3 font-medium border-r border-gray-100 bg-gray-50 capitalize">
                                        {{ key.replace(/_/g, ' ') }}
                                    </div>
                                    
                                    <template v-if="log.description === 'updated'">
                                        <div class="p-3 text-red-600 border-r border-gray-100">
                                            <span v-if="getPropertyChanges(log).old?.[key] !== undefined">
                                                {{ getPropertyChanges(log).old[key] }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                        <div class="p-3 text-green-600">
                                            <span v-if="value !== undefined">
                                                {{ value }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                    </template>
                                    
                                    <template v-else>
                                        <div class="p-3" :class="log.description === 'created' ? 'text-green-600' : 'text-red-600'">
                                            <span v-if="value !== undefined">
                                                {{ value }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Raw Properties -->
                            <div v-else-if="log.properties" class="mt-4">
                                <p class="mb-2 text-sm font-medium text-gray-500">Raw Properties</p>
                                <pre class="p-4 overflow-auto text-sm bg-gray-100 rounded-lg max-h-96">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                            </div>

                            <div v-else class="text-center text-gray-500 py-8">
                                <i class="pi pi-info-circle text-4xl mb-3"></i>
                                <p>No property data available for this log entry.</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>