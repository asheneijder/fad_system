<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Breadcrumb from 'primevue/breadcrumb';
import Badge from 'primevue/badge';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    cronJobs: Array,
    stats: Object
});

const home = { icon: 'pi pi-home', url: route('admin.dashboard') };
const items = [{ label: 'Cron Jobs' }];

const loadingCommands = ref({});
const testLoading = ref(false);

const runCommand = async (command) => {
    loadingCommands.value[command] = true;
    
    try {
        const response = await fetch(route('admin.cron.run-command'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ command })
        });

        const result = await response.json();
        
        if (result.success) {
            alert(`Command executed successfully!\n\nOutput: ${result.output}`);
        } else {
            alert(`Command failed: ${result.message}`);
        }
    } catch (error) {
        alert('Error executing command: ' + error.message);
    } finally {
        loadingCommands.value[command] = false;
    }
};

const testNotifications = async (type) => {
    testLoading.value = true;
    
    try {
        const response = await fetch(route('admin.cron.test-notifications'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ type })
        });

        const result = await response.json();
        
        if (result.success) {
            alert(`Test notifications sent successfully!\n\n${result.message}`);
        } else {
            alert(`Test failed: ${result.message}`);
        }
    } catch (error) {
        alert('Error testing notifications: ' + error.message);
    } finally {
        testLoading.value = false;
    }
};

const getStatusSeverity = (lastRun) => {
    return lastRun === 'Not tracked' ? 'warning' : 'success';
};
</script>

<template>
    <Head title="Cron Jobs Management" />
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span class="font-semibold text-gray-700">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Cron Jobs & Notifications</h1>
                    <p class="mt-1 text-gray-500">Manage automated notifications for license expirations and low stock</p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="border-l-4 border-blue-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Licenses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ stats.total_licenses }}
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="pi pi-key text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-orange-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ stats.expiring_soon }}
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="pi pi-clock text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-green-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Stationary Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ stats.total_stationary }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="pi pi-shopping-cart text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="border-l-4 border-red-500">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                    {{ stats.low_stock }}
                                </p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="pi pi-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Cron Jobs Table -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-clock text-blue-500"></i>
                        <span>Scheduled Cron Jobs</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="cronJobs" showGridlines stripedRows>
                        <Column field="name" header="Job Name" style="min-width: 250px">
                            <template #body="slotProps">
                                <div class="font-medium text-gray-900">{{ slotProps.data.name }}</div>
                                <div class="text-xs text-gray-500">{{ slotProps.data.description }}</div>
                            </template>
                        </Column>
                        <Column field="command" header="Command" style="min-width: 300px">
                            <template #body="slotProps">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ slotProps.data.command }}</code>
                            </template>
                        </Column>
                        <Column field="schedule" header="Schedule" style="min-width: 150px">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.schedule" severity="info" />
                            </template>
                        </Column>
                        <Column field="last_run" header="Last Run" style="min-width: 150px">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.last_run" :severity="getStatusSeverity(slotProps.data.last_run)" />
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 200px">
                            <template #body="slotProps">
                                <Button
                                    label="Run Now"
                                    icon="pi pi-play"
                                    severity="secondary"
                                    size="small"
                                    :loading="loadingCommands[slotProps.data.command]"
                                    @click="runCommand(slotProps.data.command)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Test Notifications -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-envelope text-purple-500"></i>
                        <span>Test Notifications</span>
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <p class="text-gray-600">Test the notification system by sending sample emails to admin users.</p>
                        
                        <div class="flex gap-4">
                            <Button
                                label="Test License Expiration Notifications"
                                icon="pi pi-key"
                                severity="primary"
                                :loading="testLoading"
                                @click="testNotifications('licenses')"
                            />
                            <Button
                                label="Test Low Stock Notifications"
                                icon="pi pi-shopping-cart"
                                severity="warning"
                                :loading="testLoading"
                                @click="testNotifications('stock')"
                            />
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <i class="pi pi-info-circle text-blue-500 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-800">How it works</h4>
                                    <p class="text-blue-700 text-sm mt-1">
                                        Notifications are automatically sent to all admin users when:
                                    </p>
                                    <ul class="text-blue-700 text-sm mt-2 list-disc list-inside">
                                        <li>Licenses are expiring within 30 days (daily check)</li>
                                        <li>Licenses are expiring within 7 days (critical, daily check)</li>
                                        <li>Stationary items fall below minimum stock levels (weekly check)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>