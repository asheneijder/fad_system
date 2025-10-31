<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Card from "primevue/card";
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    userAssignments: Array
});

const toast = useToast();
const showAcknowledgmentDialog = ref(false);
const selectedAssignment = ref(null);
const agreedToTerms = ref(false);
const globalFilter = ref('');

const breadcrumbItems = ref([
    { label: 'Dashboard', route: '/user/dashboard' },
    { label: 'My Asset Registry' }
]);

// Computed properties for summary
const summary = computed(() => {
    const total = props.userAssignments.length;
    const acknowledged = props.userAssignments.filter(a => a.acknowledged_at).length;
    const pending = props.userAssignments.filter(a => !a.acknowledged_at && !a.returned_at).length;
    const returned = props.userAssignments.filter(a => a.returned_at).length;
    const active = props.userAssignments.filter(a => !a.returned_at).length;
    
    return { total, acknowledged, pending, returned, active };
});

const acknowledgmentRate = computed(() => {
    if (summary.value.total === 0) return 100;
    return Math.round((summary.value.acknowledged / summary.value.total) * 100);
});

const filteredAssignments = computed(() => {
    if (!globalFilter.value) return props.userAssignments;
    
    const filter = globalFilter.value.toLowerCase();
    return props.userAssignments.filter(assignment => 
        assignment.asset.asset_name.toLowerCase().includes(filter) ||
        assignment.asset.asset_tag_no.toLowerCase().includes(filter) ||
        assignment.asset.serial_no?.toLowerCase().includes(filter) ||
        assignment.asset.category?.name.toLowerCase().includes(filter) ||
        assignment.asset.model?.name.toLowerCase().includes(filter)
    );
});

const acknowledgeAssignment = (assignment) => {
    selectedAssignment.value = assignment;
    showAcknowledgmentDialog.value = true;
};

const confirmAcknowledgment = () => {
    if (!agreedToTerms.value) {
        toast.add({ 
            severity: 'warn', 
            summary: 'Warning', 
            detail: 'Please agree to the terms before acknowledging.', 
            life: 3000 
        });
        return;
    }

    router.post(route('user.asset-registry.acknowledge', selectedAssignment.value.id), {}, {
        onSuccess: () => {
            toast.add({ 
                severity: 'success', 
                summary: 'Success', 
                detail: 'Asset assignment acknowledged successfully.', 
                life: 3000 
            });
            showAcknowledgmentDialog.value = false;
            agreedToTerms.value = false;
            selectedAssignment.value = null;
            
            // Refresh the page to get updated data
            router.reload({ only: ['userAssignments'] });
        },
        onError: (errors) => {
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: 'Failed to acknowledge assignment.', 
                life: 3000 
            });
        }
    });
};

const getStatusSeverity = (assignment) => {
    if (assignment.returned_at) return 'info';
    if (assignment.acknowledged_at) return 'success';
    return 'warning';
};

const getStatusLabel = (assignment) => {
    if (assignment.returned_at) return 'Returned';
    if (assignment.acknowledged_at) return 'Acknowledged';
    return 'Pending Acknowledgment';
};

const getConditionSeverity = (condition) => {
    if (!condition) return 'secondary';
    
    const conditionLower = condition.toLowerCase();
    if (conditionLower.includes('excellent') || conditionLower.includes('new')) return 'success';
    if (conditionLower.includes('good')) return 'info';
    if (conditionLower.includes('fair')) return 'warning';
    if (conditionLower.includes('poor')) return 'danger';
    return 'secondary';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getAssetTypeIcon = (category) => {
    if (!category) return 'pi pi-desktop';
    
    const categoryLower = category.toLowerCase();
    if (categoryLower.includes('laptop') || categoryLower.includes('computer')) return 'pi pi-laptop';
    if (categoryLower.includes('phone') || categoryLower.includes('mobile')) return 'pi pi-mobile';
    if (categoryLower.includes('tablet')) return 'pi pi-tablet';
    if (categoryLower.includes('printer')) return 'pi pi-print';
    if (categoryLower.includes('monitor')) return 'pi pi-desktop';
    if (categoryLower.includes('network')) return 'pi pi-wifi';
    return 'pi pi-box';
};
</script>

<template>
    <Head title="My Asset Registry" />
    <AppLayout>
        <Toast />

        <!-- Acknowledgment Dialog with Malay content -->
        <Dialog v-model:visible="showAcknowledgmentDialog" modal header="Pengesahan/Pengakuan Kakitangan" 
                :style="{ width: '60rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div class="space-y-4">
                <div class="text-sm text-gray-700">
                    <p class="mb-4 font-semibold text-lg text-center">Dengan ini saya bersetuju dan berjanji bahawa:</p>
                    
                    <div v-if="selectedAssignment" class="bg-blue-50 border border-blue-200 p-4 rounded-md mb-4">
                        <h5 class="font-bold text-blue-900 mb-2">Asset Details:</h5>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div><strong>Asset Name:</strong> {{ selectedAssignment.asset.asset_name }}</div>
                            <div><strong>Tag No:</strong> {{ selectedAssignment.asset.asset_tag_no }}</div>
                            <div><strong>Serial No:</strong> {{ selectedAssignment.asset.serial_no || 'N/A' }}</div>
                            <div><strong>Condition:</strong> {{ selectedAssignment.condition_assigned || 'Not specified' }}</div>
                            <div><strong>Model:</strong> {{ selectedAssignment.asset.model?.name || 'N/A' }}</div>
                            <div><strong>Category:</strong> {{ selectedAssignment.asset.category?.name || 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-md space-y-3">
                        <div class="flex items-start gap-3">
                            <i class="pi pi-shield text-yellow-600 mt-1"></i>
                            <div>
                                <strong class="text-yellow-800">a)</strong>
                                <span class="text-yellow-700 ml-2">Menjaga komputer riba yang diberi dengan baik dan menggunakannya untuk tujuan rasmi sahaja.</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-exclamation-triangle text-yellow-600 mt-1"></i>
                            <div>
                                <strong class="text-yellow-800">b)</strong>
                                <span class="text-yellow-700 ml-2">Bertanggungjawab di atas sebarang kerosakan atau kehilangan komputer riba disebabkan oleh kecuaian diri sendiri.</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-undo text-yellow-600 mt-1"></i>
                            <div>
                                <strong class="text-yellow-800">c)</strong>
                                <span class="text-yellow-700 ml-2">Menyerah semula komputer riba kepada Jabatan Kewangan dan Pentadbiran (JKP) setelah tamat perkhidmatan/kontrak mengikut tarikh yang dipersetujui atau diperlukan berbuat demikian pada bila-bila masa.</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-file text-yellow-600 mt-1"></i>
                            <div>
                                <strong class="text-yellow-800">d)</strong>
                                <span class="text-yellow-700 ml-2">Mematuhi Arahan Syarikat 020 - Tatacara Penggunaan Komputer Riba.</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2 p-3 bg-gray-50 rounded-md">
                    <Checkbox v-model="agreedToTerms" inputId="terms" :binary="true" />
                    <label for="terms" class="text-sm font-medium text-gray-700">
                        I confirm receipt and accept responsibility for this asset
                    </label>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <Button type="button" label="Cancel" severity="secondary" @click="showAcknowledgmentDialog = false" />
                    <Button type="button" label="Confirm Acknowledgment" :disabled="!agreedToTerms" 
                            icon="pi pi-check" @click="confirmAcknowledgment" />
                </div>
            </div>
        </Dialog>

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :model="breadcrumbItems" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-blue-600 cursor-pointer hover:underline text-sm sm:text-base" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                <div class="w-full sm:w-auto">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Asset Registry</h1>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-2">
                        <Badge :value="`${summary.total} Assets`" 
                               severity="info"
                               class="text-xs sm:text-sm" />
                        <span class="text-xs sm:text-sm text-gray-500">
                            Updated as of {{ new Date().toLocaleDateString('en-US') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Asset Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3 md:gap-4">
                <Card class="bg-blue-50 border-blue-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600">{{ summary.total }}</div>
                            <div class="text-xs sm:text-sm text-blue-700">Total Assets</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-green-50 border-green-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-green-600">{{ summary.active }}</div>
                            <div class="text-xs sm:text-sm text-green-700">Active</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-orange-50 border-orange-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600">{{ summary.pending }}</div>
                            <div class="text-xs sm:text-sm text-orange-700">Pending</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-cyan-50 border-cyan-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-cyan-600">{{ summary.returned }}</div>
                            <div class="text-xs sm:text-sm text-cyan-700">Returned</div>
                        </div>
                    </template>
                </Card>

                <Card class="bg-purple-50 border-purple-200">
                    <template #content>
                        <div class="text-center">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold text-purple-600">{{ acknowledgmentRate }}%</div>
                            <div class="text-xs sm:text-sm text-purple-700">Acknowledgment Rate</div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Asset List - Card Layout (No Horizontal Scrolling) -->
            <Card class="shadow-lg">
                <template #content>
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800">My Assigned Assets</h3>
                            <p class="text-gray-600 mt-1 text-sm">Manage and acknowledge your company assets</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-search text-gray-400"></i>
                            <InputText v-model="globalFilter" placeholder="Search assets..." class="w-full lg:w-80" />
                        </div>
                    </div>
                    
                    <!-- No Assets State -->
                    <div v-if="filteredAssignments.length === 0" class="text-center py-8">
                        <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 text-lg">No assets found</p>
                        <p class="text-sm text-gray-400 mt-2" v-if="globalFilter">
                            No results for "{{ globalFilter }}". Try a different search term.
                        </p>
                        <p class="text-sm text-gray-400 mt-2" v-else>
                            Assets assigned to you will appear here.
                        </p>
                    </div>

                    <!-- Asset Cards Layout -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <Card v-for="assignment in filteredAssignments" :key="assignment.id" 
                              class="hover:shadow-lg transition-all duration-200 border border-gray-200">
                            <template #content>
                                <!-- Header with Asset Info -->
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-lg">{{ assignment.asset.asset_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ assignment.asset.category?.name || 'Uncategorized' }}</p>
                                    </div>
                                    <Tag :value="getStatusLabel(assignment)" 
                                         :severity="getStatusSeverity(assignment)"
                                         class="text-xs" />
                                </div>

                                <!-- Asset Details -->
                                <div class="space-y-3 mb-4">
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Tag No</label>
                                            <p class="font-semibold text-gray-900">{{ assignment.asset.asset_tag_no }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Serial No</label>
                                            <p class="font-semibold text-gray-900">{{ assignment.asset.serial_no || 'N/A' }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Assigned Date</label>
                                            <p class="font-semibold text-gray-900">{{ formatDate(assignment.assigned_at) }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Condition</label>
                                            <Tag :value="assignment.condition_assigned || 'Not Specified'" 
                                                 :severity="getConditionSeverity(assignment.condition_assigned)"
                                                 class="text-xs" />
                                        </div>
                                    </div>

                                    <div v-if="assignment.asset.model?.name" class="text-sm">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Model</label>
                                        <p class="font-semibold text-gray-900">{{ assignment.asset.model.name }}</p>
                                    </div>
                                </div>

                                <!-- Action Button - Always Visible -->
                                <div class="border-t pt-4">
                                    <div class="flex justify-between items-center">
                                        <div class="text-xs text-gray-500">
                                            Last updated: {{ formatDate(assignment.updated_at) }}
                                        </div>
                                        <Button 
                                            v-if="!assignment.acknowledged_at && !assignment.returned_at"
                                            label="Acknowledge" 
                                            icon="pi pi-check" 
                                            size="small"
                                            @click="acknowledgeAssignment(assignment)"
                                            class="min-w-[120px]"
                                        />
                                        <Tag 
                                            v-else
                                            :value="assignment.returned_at ? 'Returned' : 'Acknowledged'"
                                            :severity="assignment.returned_at ? 'info' : 'success'"
                                            class="px-3 py-1 text-xs"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Pagination for Card Layout -->
                    <div v-if="filteredAssignments.length > 6" class="flex justify-center mt-6">
                        <div class="flex gap-2">
                            <Button icon="pi pi-chevron-left" severity="secondary" text rounded />
                            <Button v-for="page in Math.ceil(filteredAssignments.length / 6)" :key="page" 
                                    :label="page.toString()" 
                                    :severity="page === 1 ? 'primary' : 'secondary'" 
                                    text rounded />
                            <Button icon="pi pi-chevron-right" severity="secondary" text rounded />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Sidebar Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Status Summary -->
                <Card class="shadow-lg">
                    <template #content>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Status Summary</h3>
                        <div class="space-y-3 sm:space-y-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500">Total Assets</label>
                                <p class="mt-1 text-sm sm:text-base font-medium text-gray-900">{{ summary.total }} Units</p>
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500">Currently Active</label>
                                <p class="mt-1 text-sm sm:text-base font-medium text-green-600">{{ summary.active }} Units</p>
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500">Pending Acknowledgment</label>
                                <p class="mt-1 text-sm sm:text-base font-medium text-orange-600">{{ summary.pending }} Units</p>
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500">Returned Assets</label>
                                <p class="mt-1 text-sm sm:text-base font-medium text-cyan-600">{{ summary.returned }} Units</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Acknowledgment Progress -->
                <Card class="shadow-lg">
                    <template #content>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Acknowledgment Progress</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div class="flex justify-between items-center text-xs sm:text-sm">
                                <span class="text-gray-600">Total Assets:</span>
                                <span class="font-medium">{{ summary.total }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs sm:text-sm">
                                <span class="text-gray-600">Acknowledged:</span>
                                <span class="font-medium text-green-600">{{ summary.acknowledged }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs sm:text-sm">
                                <span class="text-gray-600">Pending:</span>
                                <span class="font-medium text-orange-600">{{ summary.pending }}</span>
                            </div>
                            <div class="pt-2 border-t">
                                <div class="flex justify-between items-center text-xs sm:text-sm mb-2">
                                    <span class="text-gray-600 font-semibold">Acknowledgment Rate:</span>
                                    <span class="font-bold text-base sm:text-lg" :class="{
                                        'text-green-600': acknowledgmentRate >= 80,
                                        'text-yellow-600': acknowledgmentRate >= 50 && acknowledgmentRate < 80,
                                        'text-red-600': acknowledgmentRate < 50
                                    }">
                                        {{ acknowledgmentRate }}%
                                    </span>
                                </div>
                                <ProgressBar :value="acknowledgmentRate" 
                                            :showValue="false"
                                            :class="{
                                                'p-progressbar-success': acknowledgmentRate >= 80,
                                                'p-progressbar-warning': acknowledgmentRate >= 50 && acknowledgmentRate < 80,
                                                'p-progressbar-danger': acknowledgmentRate < 50
                                            }" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-badge) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-badge) {
        font-size: 0.875rem;
    }
}

:deep(.p-button) {
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(.p-button) {
        font-size: 1rem;
    }
}

:deep(.p-progressbar) {
    height: 0.75rem;
}

@media (min-width: 640px) {
    :deep(.p-progressbar) {
        height: 1rem;
    }
}

:deep(.p-progressbar-success .p-progressbar-value) {
    background-color: #22c55e;
}

:deep(.p-progressbar-warning .p-progressbar-value) {
    background-color: #f59e0b;
}

:deep(.p-progressbar-danger .p-progressbar-value) {
    background-color: #ef4444;
}

/* Hover effects for asset cards */
.asset-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>