<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Breadcrumb from 'primevue/breadcrumb';
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref, computed } from 'vue';
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
    router.visit(route('admin.audit-logs.index'));
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
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const formatValue = (value) => {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value); 
    return value;
};

// Computed properties for changes
const changes = computed(() => {
    const logProps = props.log.properties || {};
    const attributes = logProps.attributes || {};
    const old = logProps.old || {};
    
    // If we have both old and new (attributes), it's likely an update
    if (Object.keys(old).length > 0 && Object.keys(attributes).length > 0) {
        const allKeys = [...new Set([...Object.keys(old), ...Object.keys(attributes)])];
        // Filter out keys that shouldn't be shown or haven't changed (if strict)
        return allKeys.map(key => ({
            attribute: key,
            old: old[key],
            new: attributes[key],
            isChanged: JSON.stringify(old[key]) !== JSON.stringify(attributes[key])
        })).filter(item => item.isChanged); // Only show actual changes
    }
    
    // If only attributes (created)
    if (Object.keys(attributes).length > 0 && Object.keys(old).length === 0) {
        return Object.keys(attributes).map(key => ({
            attribute: key,
            old: null,
            new: attributes[key],
            type: 'created'
        }));
    }

    // If only old (deleted) - typically spatie logs 'old' on delete or 'attributes' depending on config
    // Checking where data resides for deleted events
    const deletedData = logProps.old || logProps.attributes || {};
    if (Object.keys(deletedData).length > 0 && props.log?.event === 'deleted') {
         return Object.keys(deletedData).map(key => ({
            attribute: key,
            old: deletedData[key],
            new: null,
            type: 'deleted'
        }));
    }

    return [];
});

const hasChanges = computed(() => changes.value.length > 0);

</script>

<template>
    <Head :title="`Audit Log #${log.id}`" />
    <AppLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4 bg-transparent p-0 border-0" />

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Audit Log #{{ log.id }}</h1>
                        <Badge 
                            :value="log.event" 
                            :severity="getEventSeverity(log.event)"
                            class="uppercase tracking-wider"
                        />
                    </div>
                    <p class="text-gray-600 mt-1">
                        {{ log.description }} on 
                        <span class="font-medium text-gray-900">{{ formatDateTime(log.created_at) }}</span>
                    </p>
                </div>
                <Button 
                    label="Back to Logs" 
                    icon="pi pi-arrow-left" 
                    severity="secondary"
                    @click="goBack"
                    outlined
                />
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="xl:col-span-2 space-y-6">
                    
                    <!-- Changes Table -->
                    <Card v-if="hasChanges" class="overflow-hidden border border-gray-100 shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-history text-blue-500"></i>
                                <span>Changes Recorded</span>
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="changes" stripedRows size="small" :rowHover="true">
                                <Column field="attribute" header="Attribute" class="font-medium capitalize w-1/4">
                                    <template #body="{ data }">
                                        {{ data.attribute.replace(/_/g, ' ') }}
                                    </template>
                                </Column>
                                <Column header="Old Value" class="w-1/3">
                                    <template #body="{ data }">
                                        <div v-if="data.type === 'created'" class="text-gray-400 italic">
                                            (None)
                                        </div>
                                        <div v-else class="text-red-600 bg-red-50 px-2 py-1 rounded inline-block text-sm break-all">
                                            {{ formatValue(data.old) }}
                                        </div>
                                    </template>
                                </Column>
                                <Column header="New Value" class="w-1/3">
                                    <template #body="{ data }">
                                        <div v-if="data.type === 'deleted'" class="text-gray-400 italic">
                                            (Deleted)
                                        </div>
                                        <div v-else class="text-green-600 bg-green-50 px-2 py-1 rounded inline-block text-sm break-all">
                                            {{ formatValue(data.new) }}
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <!-- Raw Properties Fallback (if no formatted changes but properties exist) -->
                    <Card v-else-if="log.properties && Object.keys(log.properties).length > 0" class="border border-gray-100 shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-code text-gray-500"></i>
                                <span>Raw Properties</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="bg-gray-50 rounded-lg p-4 font-mono text-xs overflow-x-auto">
                                <pre>{{ JSON.stringify(log.properties, null, 2) }}</pre>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right Column: Metadata -->
                <div class="space-y-6">
                    <!-- Actor -->
                    <Card class="border border-gray-100 shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-user text-purple-500"></i>
                                <span>Performed By</span>
                            </div>
                        </template>
                        <template #content>
                            <div v-if="log.causer" class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-lg">
                                    {{ log.causer.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ log.causer.name }}</div>
                                    <div class="text-sm text-gray-500">{{ log.causer.email }}</div>
                                    <div v-if="log.causer.department" class="text-xs text-gray-400 mt-0.5">
                                        {{ log.causer.department }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex items-center gap-2 text-gray-500 italic">
                                <i class="pi pi-desktop"></i>
                                <span>System / Automated Process</span>
                            </div>
                        </template>
                    </Card>

                    <!-- Subject -->
                    <Card class="border border-gray-100 shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-box text-orange-500"></i>
                                <span>Subject</span>
                            </div>
                        </template>
                        <template #content>
                            <div v-if="log.subject" class="space-y-3">
                                <div class="p-3 bg-orange-50 rounded-lg border border-orange-100">
                                    <div class="text-xs text-orange-600 uppercase font-semibold">Type</div>
                                    <div class="text-gray-900 font-medium">
                                        {{ log.subject_type ? log.subject_type.split('\\').pop() : 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="text-xs text-gray-500 uppercase font-semibold">ID</div>
                                    <div class="text-gray-900 font-mono">{{ log.subject_id }}</div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 italic">
                                No specific subject associated.
                            </div>
                        </template>
                    </Card>

                    <!-- System Info -->
                    <Card class="border border-gray-100 shadow-sm">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-server text-gray-500"></i>
                                <span>System Details</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between py-1 border-b border-gray-50">
                                    <span class="text-gray-500">Log Name</span>
                                    <span class="font-medium text-gray-900">{{ log.log_name }}</span>
                                </div>
                                <div v-if="log.properties?.ip_address" class="flex justify-between py-1 border-b border-gray-50">
                                    <span class="text-gray-500">IP Address</span>
                                    <span class="font-mono text-gray-900">{{ log.properties.ip_address }}</span>
                                </div>
                                <div v-if="log.properties?.user_agent" class="pt-1">
                                    <span class="text-gray-500 block mb-1">User Agent</span>
                                    <div class="text-xs text-gray-700 bg-gray-50 p-2 rounded break-all">
                                        {{ log.properties.user_agent }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>