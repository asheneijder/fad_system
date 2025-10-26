<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const toast = useToast();

const props = defineProps({
    claimTypes: {
        type: Array,
        default: () => []
    },
    travelClaims: {
        type: Array,
        default: () => []
    },
    dailyAllowances: {
        type: Array,
        default: () => []
    },
    accommodationClaims: {
        type: Array,
        default: () => []
    },
    transportationClaims: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({
            totalClaims: 0,
            pending: 0,
            approved: 0,
            rejected: 0,
            totalAmount: 0
        })
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [{ label: 'Claim Requests' }];

const activeTab = ref(0);
const loading = ref(false);

// Statistics computed
const stats = computed(() => props.statistics);

// Claim counts for each type
const claimCounts = computed(() => ({
    travel: props.travelClaims.length,
    daily: props.dailyAllowances.length,
    accommodation: props.accommodationClaims.length,
    transportation: props.transportationClaims.length
}));

// Status badge styling
const getStatusSeverity = (status) => {
    const statusMap = {
        draft: 'secondary',
        submitted: 'info',
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        paid: 'info',
        cancelled: 'secondary'
    };
    return statusMap[status] || 'secondary';
};

const getStatusText = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const getClaimTypeSeverity = (type) => {
    const typeMap = {
        travel: 'blue',
        daily: 'green',
        accommodation: 'purple',
        transportation: 'orange'
    };
    return typeMap[type] || 'gray';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const navigateToClaim = (claimType) => {
    if (claimType.route) {
        router.get(claimType.route);
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Coming Soon',
            detail: `${claimType.name} feature is coming soon`,
            life: 3000
        });
    }
};

// Add this helper function
const getClaimRoute = (action, type, id) => {
    const routeMap = {
        travel: {
            show: 'user.travel-claims.show',
            edit: 'user.travel-claims.edit',
            param: 'travelClaim'
        },
        daily: {
            show: 'user.daily-allowances.show',
            edit: 'user.daily-allowances.edit',
            param: 'dailyAllowance'
        },
        accommodation: {
            show: 'user.accommodation-claims.show',
            edit: 'user.accommodation-claims.edit',
            param: 'accommodationClaim'
        },
        transportation: {
            show: 'user.transportation-claims.show',
            edit: 'user.transportation-claims.edit',
            param: 'transportationClaim'
        }
    };

    const routeConfig = routeMap[type];
    if (!routeConfig || !routeConfig[action]) {
        throw new Error(`Route not found for ${action} ${type} claim`);
    }

    return {
        name: routeConfig[action],
        params: { [routeConfig.param]: id }
    };
};

// Updated functions
const viewClaimDetails = (claimId, type) => {
    try {
        const routeInfo = getClaimRoute('show', type, claimId);
        router.get(route(routeInfo.name, routeInfo.params));
    } catch (error) {
        console.error('Error navigating to claim details:', error);
        toast.add({
            severity: 'error',
            summary: 'Navigation Error',
            detail: `Unable to view ${type} claim details. Please try again.`,
            life: 3000
        });
    }
};

const editClaim = (claimId, type) => {
    try {
        const routeInfo = getClaimRoute('edit', type, claimId);
        router.get(route(routeInfo.name, routeInfo.params));
    } catch (error) {
        console.error('Error navigating to edit claim:', error);
        toast.add({
            severity: 'error',
            summary: 'Navigation Error',
            detail: `Unable to edit ${type} claim. Please try again.`,
            life: 3000
        });
    }
};

const getClaimCount = (type) => {
    return claimCounts.value[type] || 0;
};

// Helper functions for display
const getAllowanceTypeDisplay = (type) => {
    const typeMap = {
        full_day: 'Full Day (100%)',
        breakfast: 'Breakfast (20%)',
        lunch: 'Lunch (40%)',
        dinner: 'Dinner (40%)'
    };
    return typeMap[type] || type;
};

const getTransportTypeDisplay = (type) => {
    const typeMap = {
        grab: 'Grab',
        taxi: 'Taxi',
        mrt: 'MRT',
        lrt: 'LRT',
        ecl: 'Electric Train',
        bus: 'Bus',
        toll: 'Toll',
        parking: 'Parking',
        other: 'Other'
    };
    return typeMap[type] || type;
};
</script>

<template>
    <Head title="Claim Requests" />
    <AppLayout>
        <Toast />

        <div class="page-container">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="breadcrumb-nav">
                <template #item="{ item }">
                    <span class="breadcrumb-label">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Claim Requests</h1>
                    <p class="page-subtitle">Submit and manage your expense claims</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <Card class="stat-card stat-card-blue">
                    <template #content>
                        <div class="stat-content">
                            <div>
                                <p class="stat-label">Total Claims</p>
                                <p class="stat-value">{{ stats.totalClaims }}</p>
                            </div>
                            <div class="stat-icon stat-icon-blue">
                                <i class="pi pi-file"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card stat-card-yellow">
                    <template #content>
                        <div class="stat-content">
                            <div>
                                <p class="stat-label">Submitted</p>
                                <p class="stat-value">{{ stats.submitted }}</p>
                            </div>
                            <div class="stat-icon stat-icon-yellow">
                                <i class="pi pi-clock"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card stat-card-green">
                    <template #content>
                        <div class="stat-content">
                            <div>
                                <p class="stat-label">Approved</p>
                                <p class="stat-value">{{ stats.approved }}</p>
                            </div>
                            <div class="stat-icon stat-icon-green">
                                <i class="pi pi-check-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card stat-card-red">
                    <template #content>
                        <div class="stat-content">
                            <div>
                                <p class="stat-label">Rejected</p>
                                <p class="stat-value">{{ stats.rejected }}</p>
                            </div>
                            <div class="stat-icon stat-icon-red">
                                <i class="pi pi-times-circle"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card stat-card-purple">
                    <template #content>
                        <div class="stat-content">
                            <div>
                                <p class="stat-label">Total Amount</p>
                                <p class="stat-value stat-value-small">{{ formatCurrency(stats.totalAmount) }}</p>
                            </div>
                            <div class="stat-icon stat-icon-purple">
                                <i class="pi pi-money-bill"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Claim Types Grid -->
            <Card class="content-card">
                <template #content>
                    <div class="card-header">
                        <h2 class="card-title">Select Claim Type</h2>
                        <div class="card-subtitle">
                            Choose the type of expense you want to claim
                        </div>
                    </div>

                    <div class="claim-types-grid">
                        <div v-for="claimType in claimTypes" :key="claimType.id"
                            class="claim-type-card"
                            @click="navigateToClaim(claimType)">
                            <div class="claim-type-content">
                                <div :class="['claim-type-icon-wrapper', `bg-${claimType.bgColor}`]">
                                    <i :class="[claimType.icon, `text-${claimType.color}`, 'claim-type-icon']"></i>
                                </div>
                                
                                <div class="claim-type-info">
                                    <h3 class="claim-type-name">{{ claimType.name }}</h3>
                                    <p class="claim-type-desc">{{ claimType.description }}</p>
                                </div>
                                
                                <Button label="Create Claim" 
                                    :severity="getClaimTypeSeverity(claimType.id)"
                                    class="claim-type-button" />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- My Claims Section with Tabs -->
            <Card class="content-card">
                <template #content>
                    <div class="card-header">
                        <h2 class="card-title">My Claims</h2>
                        <div class="card-subtitle">
                            Total: {{ stats.totalClaims }} claims
                        </div>
                    </div>

                    <TabView v-model:activeIndex="activeTab" class="claims-tabview">
                        <!-- Travel Claims Tab -->
                        <TabPanel>
                            <template #header>
                                <div class="tab-header">
                                    <i class="pi pi-car tab-icon"></i>
                                    <span class="tab-label">Travel</span>
                                    <Badge v-if="claimCounts.travel > 0" :value="claimCounts.travel" class="tab-badge" />
                                </div>
                            </template>
                            
                            <div class="table-wrapper">
                                <DataTable :value="travelClaims" showGridlines stripedRows
                                    :rowHover="true" responsiveLayout="scroll" 
                                    :loading="loading">

                                    <template #empty>
                                        <div class="empty-state">
                                            <div class="empty-icon empty-icon-blue">
                                                <i class="pi pi-car"></i>
                                            </div>
                                            <h3 class="empty-title">No Travel Claims</h3>
                                            <p class="empty-desc">
                                                You haven't submitted any travel claims yet.
                                            </p>
                                            <Button label="Create Travel Claim" icon="pi pi-plus" severity="primary"
                                                @click="navigateToClaim(claimTypes.find(ct => ct.id === 'travel'))" />
                                        </div>
                                    </template>

                                    <Column header="Travel Date" :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">
                                                {{ formatDate(slotProps.data.date_of_travel) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="travel_from" header="From" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-bold">{{ slotProps.data.travel_from }}</div>
                                        </template>
                                    </Column>

                                    <Column field="travel_to" header="To" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-bold">{{ slotProps.data.travel_to }}</div>
                                        </template>
                                    </Column>

                                    <Column field="total_distance" header="Distance" sortable :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.total_distance }} km</div>
                                        </template>
                                    </Column>

                                    <Column field="total_cost" header="Amount" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-amount">
                                                {{ formatCurrency(slotProps.data.total_cost) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Status" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <Badge :value="getStatusText(slotProps.data.status)"
                                                :severity="getStatusSeverity(slotProps.data.status)"
                                                class="status-badge" />
                                        </template>
                                    </Column>

                                    <Column header="Actions" :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="action-buttons">
                                                <Button icon="pi pi-eye" outlined rounded severity="info"
                                                    v-tooltip.top="'View Details'" 
                                                    @click="viewClaimDetails(slotProps.data.id, 'travel')"
                                                    class="action-btn" />   
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <!-- Daily Allowance Tab -->
                        <TabPanel>
                            <template #header>
                                <div class="tab-header">
                                    <i class="pi pi-wallet tab-icon"></i>
                                    <span class="tab-label">Daily</span>
                                    <Badge v-if="claimCounts.daily > 0" :value="claimCounts.daily" class="tab-badge" />
                                </div>
                            </template>
                            
                            <div class="table-wrapper">
                                <DataTable :value="dailyAllowances" showGridlines stripedRows
                                    :rowHover="true" responsiveLayout="scroll" 
                                    :loading="loading">

                                    <template #empty>
                                        <div class="empty-state">
                                            <div class="empty-icon empty-icon-green">
                                                <i class="pi pi-wallet"></i>
                                            </div>
                                            <h3 class="empty-title">No Daily Allowance Claims</h3>
                                            <p class="empty-desc">
                                                You haven't submitted any daily allowance claims yet.
                                            </p>
                                            <Button label="Create Daily Allowance" icon="pi pi-plus" severity="success"
                                                @click="navigateToClaim(claimTypes.find(ct => ct.id === 'daily'))" />
                                        </div>
                                    </template>

                                    <Column header="Date" :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">
                                                {{ formatDate(slotProps.data.claim_date) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="allowance_type" header="Type" sortable :style="{ minWidth: '150px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-bold">
                                                {{ getAllowanceTypeDisplay(slotProps.data.allowance_type) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="destination" header="Destination" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.destination }}</div>
                                        </template>
                                    </Column>

                                    <Column field="daily_rate" header="Daily Rate" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ formatCurrency(slotProps.data.daily_rate) }} {{ slotProps.data.currency }}</div>
                                        </template>
                                    </Column>

                                    <Column field="total_amount" header="Claim Amount" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-amount">
                                                {{ formatCurrency(slotProps.data.total_amount) }} {{ slotProps.data.currency }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Status" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <Badge :value="getStatusText(slotProps.data.status)"
                                                :severity="getStatusSeverity(slotProps.data.status)"
                                                class="status-badge" />
                                        </template>
                                    </Column>

                                    <Column header="Actions" :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="action-buttons">
                                                <Button icon="pi pi-eye" outlined rounded severity="info"
                                                    v-tooltip.top="'View Details'" 
                                                    @click="viewClaimDetails(slotProps.data.id, 'daily')"
                                                    class="action-btn" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <!-- Accommodation Claims Tab -->
                        <TabPanel>
                            <template #header>
                                <div class="tab-header">
                                    <i class="pi pi-building tab-icon"></i>
                                    <span class="tab-label">Accommodation</span>
                                    <Badge v-if="claimCounts.accommodation > 0" :value="claimCounts.accommodation" class="tab-badge" />
                                </div>
                            </template>
                            
                            <div class="table-wrapper">
                                <DataTable :value="accommodationClaims" showGridlines stripedRows
                                    :rowHover="true" responsiveLayout="scroll" 
                                    :loading="loading">

                                    <template #empty>
                                        <div class="empty-state">
                                            <div class="empty-icon empty-icon-purple">
                                                <i class="pi pi-building"></i>
                                            </div>
                                            <h3 class="empty-title">No Accommodation Claims</h3>
                                            <p class="empty-desc">
                                                You haven't submitted any accommodation claims yet.
                                            </p>
                                            <Button label="Create Accommodation Claim" icon="pi pi-plus" severity="help"
                                                @click="navigateToClaim(claimTypes.find(ct => ct.id === 'accommodation'))" />
                                        </div>
                                    </template>

                                    <Column header="Check-in" :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">
                                                {{ formatDate(slotProps.data.check_in_date) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Check-out" :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">
                                                {{ formatDate(slotProps.data.check_out_date) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="hotel_name" header="Hotel" sortable :style="{ minWidth: '150px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-bold">{{ slotProps.data.hotel_name }}</div>
                                        </template>
                                    </Column>

                                    <Column field="number_of_nights" header="Nights" sortable :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.number_of_nights }} nights</div>
                                        </template>
                                    </Column>

                                    <Column field="total_amount" header="Amount" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-amount">
                                                {{ formatCurrency(slotProps.data.total_amount) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Status" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <Badge :value="getStatusText(slotProps.data.status)"
                                                :severity="getStatusSeverity(slotProps.data.status)"
                                                class="status-badge" />
                                        </template>
                                    </Column>

                                    <Column header="Actions" :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="action-buttons">
                                                <Button icon="pi pi-eye" outlined rounded severity="info"
                                                    v-tooltip.top="'View Details'" 
                                                    @click="viewClaimDetails(slotProps.data.id, 'accommodation')"
                                                    class="action-btn" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <!-- Transportation Claims Tab -->
                        <TabPanel>
                            <template #header>
                                <div class="tab-header">
                                    <i class="pi pi-map-marker tab-icon"></i>
                                    <span class="tab-label">Transport</span>
                                    <Badge v-if="claimCounts.transportation > 0" :value="claimCounts.transportation" class="tab-badge" />
                                </div>
                            </template>
                            
                            <div class="table-wrapper">
                                <DataTable :value="transportationClaims" showGridlines stripedRows
                                    :rowHover="true" responsiveLayout="scroll" 
                                    :loading="loading">

                                    <template #empty>
                                        <div class="empty-state">
                                            <div class="empty-icon empty-icon-orange">
                                                <i class="pi pi-map-marker"></i>
                                            </div>
                                            <h3 class="empty-title">No Transportation Claims</h3>
                                            <p class="empty-desc">
                                                You haven't submitted any transportation claims yet.
                                            </p>
                                            <Button label="Create Transportation Claim" icon="pi pi-plus" severity="warning"
                                                @click="navigateToClaim(claimTypes.find(ct => ct.id === 'transportation'))" />
                                        </div>
                                    </template>

                                    <Column header="Date" :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">
                                                {{ formatDate(slotProps.data.claim_date) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="transport_type" header="Transport Type" sortable :style="{ minWidth: '150px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-bold">
                                                {{ getTransportTypeDisplay(slotProps.data.transport_type) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="from_location" header="From" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.from_location }}</div>
                                        </template>
                                    </Column>

                                    <Column field="to_location" header="To" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.to_location }}</div>
                                        </template>
                                    </Column>

                                    <Column field="distance_km" header="Distance" sortable :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell">{{ slotProps.data.distance_km }} km</div>
                                        </template>
                                    </Column>

                                    <Column field="total_amount" header="Amount" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <div class="table-cell table-cell-amount">
                                                {{ formatCurrency(slotProps.data.total_amount) }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column header="Status" sortable :style="{ minWidth: '120px' }">
                                        <template #body="slotProps">
                                            <Badge :value="getStatusText(slotProps.data.status)"
                                                :severity="getStatusSeverity(slotProps.data.status)"
                                                class="status-badge" />
                                        </template>
                                    </Column>

                                    <Column header="Actions" :style="{ minWidth: '100px' }">
                                        <template #body="slotProps">
                                            <div class="action-buttons">
                                                <Button icon="pi pi-eye" outlined rounded severity="info"
                                                    v-tooltip.top="'View Details'" 
                                                    @click="viewClaimDetails(slotProps.data.id, 'transportation')"
                                                    class="action-btn" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>
                    </TabView>
                </template>
            </Card>

            <!-- Quick Tips Section -->
            <Card class="tips-card">
                <template #content>
                    <div class="tips-content">
                        <div class="tips-icon-wrapper">
                            <i class="pi pi-info-circle tips-icon"></i>
                        </div>
                        <div class="tips-info">
                            <h3 class="tips-title">Claim Submission Tips</h3>
                            <ul class="tips-list">
                                <li>Ensure all receipts and supporting documents are clear and readable</li>
                                <li>Submit claims within 30 days of the expense date</li>
                                <li>Provide detailed descriptions for all expenses</li>
                                <li>Keep original receipts for audit purposes</li>
                                <li>Contact HR for any claim-related questions</li>
                            </ul>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Base Container */
.page-container {
    padding: 0.75rem;
    max-width: 100%;
}

@media (min-width: 640px) {
    .page-container {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    .page-container {
        padding: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .page-container {
        padding: 2rem;
    }
}

/* Breadcrumb */
.breadcrumb-nav {
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .breadcrumb-nav {
        margin-bottom: 1.5rem;
    }
}

.breadcrumb-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

@media (min-width: 768px) {
    .breadcrumb-label {
        font-size: 1rem;
    }
}

/* Page Header */
.page-header {
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .page-header {
        margin-bottom: 2rem;
    }
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

@media (min-width: 640px) {
    .page-title {
        font-size: 1.875rem;
    }
}

@media (min-width: 1024px) {
    .page-title {
        font-size: 2.25rem;
    }
}

.page-subtitle {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
}

@media (min-width: 768px) {
    .page-subtitle {
        font-size: 1rem;
        margin-top: 0.5rem;
    }
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

@media (min-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
}

/* Stat Cards */
.stat-card {
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.stat-card-blue {
    border-left: 4px solid #3b82f6;
}

.stat-card-yellow {
    border-left: 4px solid #eab308;
}

.stat-card-green {
    border-left: 4px solid #22c55e;
}

.stat-card-red {
    border-left: 4px solid #ef4444;
}

.stat-card-purple {
    border-left: 4px solid #a855f7;
}

.stat-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .stat-label {
        font-size: 0.875rem;
    }
}

.stat-value {
    margin-top: 0.25rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}

@media (min-width: 640px) {
    .stat-value {
        font-size: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .stat-value {
        font-size: 1.875rem;
    }
}

.stat-value-small {
    font-size: 1rem;
}

@media (min-width: 640px) {
    .stat-value-small {
        font-size: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .stat-value-small {
        font-size: 1.5rem;
    }
}

.stat-icon {
    padding: 0.5rem;
    border-radius: 9999px;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .stat-icon {
        padding: 0.75rem;
    }
}

.stat-icon i {
    font-size: 1.125rem;
}

@media (min-width: 640px) {
    .stat-icon i {
        font-size: 1.25rem;
    }
}

.stat-icon-blue {
    background-color: #dbeafe;
    color: #2563eb;
}

.stat-icon-yellow {
    background-color: #fef3c7;
    color: #ca8a04;
}

.stat-icon-green {
    background-color: #dcfce7;
    color: #16a34a;
}

.stat-icon-red {
    background-color: #fee2e2;
    color: #dc2626;
}

.stat-icon-purple {
    background-color: #f3e8ff;
    color: #9333ea;
}

/* Content Cards */
.content-card {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .content-card {
        margin-bottom: 2rem;
    }
}

.card-header {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .card-header {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

@media (min-width: 768px) {
    .card-title {
        font-size: 1.25rem;
    }
}

.card-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Claim Types Grid */
.claim-types-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 640px) {
    .claim-types-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .claim-types-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
}

.claim-type-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

@media (min-width: 768px) {
    .claim-type-card {
        padding: 1.5rem;
    }
}

.claim-type-card:hover {
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    transform: translateY(-4px);
    border-color: #3b82f6;
}

.claim-type-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1rem;
}

.claim-type-icon-wrapper {
    padding: 0.75rem;
    border-radius: 9999px;
    transition: transform 0.3s ease;
}

@media (min-width: 768px) {
    .claim-type-icon-wrapper {
        padding: 1rem;
    }
}

.claim-type-card:hover .claim-type-icon-wrapper {
    transform: scale(1.1);
}

.claim-type-icon {
    font-size: 1.5rem;
}

@media (min-width: 768px) {
    .claim-type-icon {
        font-size: 1.875rem;
    }
}

.claim-type-info {
    flex: 1;
}

.claim-type-name {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem 0;
}

@media (min-width: 768px) {
    .claim-type-name {
        font-size: 1.125rem;
    }
}

.claim-type-desc {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

@media (min-width: 768px) {
    .claim-type-desc {
        font-size: 1rem;
    }
}

.claim-type-button {
    width: 100%;
    transition: transform 0.3s ease;
}

.claim-type-card:hover .claim-type-button {
    transform: scale(1.05);
}

/* TabView Styling */
.claims-tabview :deep(.p-tabview-nav) {
    background: transparent;
    border: none;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
}

.claims-tabview :deep(.p-tabview-nav-link) {
    background: transparent;
    border: none;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    transition: all 0.2s ease;
}

@media (min-width: 768px) {
    .claims-tabview :deep(.p-tabview-nav-link) {
        padding: 1rem 1.5rem;
    }
}

.claims-tabview :deep(.p-tabview-nav-link:hover) {
    background: #f9fafb;
    border-bottom-color: #d1d5db;
}

.claims-tabview :deep(.p-highlight .p-tabview-nav-link) {
    background: transparent;
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.claims-tabview :deep(.p-tabview-panels) {
    background: transparent;
    border: none;
    padding: 1rem 0 0 0;
}

.tab-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-icon {
    font-size: 1rem;
}

.tab-label {
    font-size: 0.875rem;
}

@media (min-width: 768px) {
    .tab-label {
        font-size: 1rem;
    }
}

.tab-badge {
    margin-left: 0.25rem;
}

/* Table Wrapper */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* DataTable Customization */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
    font-weight: 600;
    color: #374151;
    border-color: #e5e7eb;
    padding: 0.75rem;
    font-size: 0.875rem;
}

@media (min-width: 768px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        padding: 1rem;
        font-size: 0.875rem;
    }
}

@media (min-width: 1024px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 1rem;
    }
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f9fafb;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
}

@media (min-width: 768px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 1rem;
    }
}

/* Table Cells */
.table-cell {
    font-size: 0.875rem;
    color: #6b7280;
}

@media (min-width: 768px) {
    .table-cell {
        font-size: 0.875rem;
    }
}

.table-cell-bold {
    font-weight: 500;
    color: #111827;
}

.table-cell-amount {
    font-weight: 600;
    color: #111827;
}

/* Status Badge */
.status-badge {
    text-transform: capitalize;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
}

@media (min-width: 768px) {
    .status-badge {
        font-size: 0.875rem;
        padding: 0.35rem 0.7rem;
    }
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.action-btn :deep(.p-button) {
    width: 2rem;
    height: 2rem;
    padding: 0;
}

@media (min-width: 768px) {
    .action-btn :deep(.p-button) {
        width: 2.5rem;
        height: 2.5rem;
    }
}

.action-btn :deep(.p-button-icon) {
    font-size: 0.875rem;
}

@media (min-width: 768px) {
    .action-btn :deep(.p-button-icon) {
        font-size: 1rem;
    }
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    text-align: center;
}

@media (min-width: 768px) {
    .empty-state {
        padding: 3rem 1.5rem;
    }
}

.empty-icon {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 9999px;
}

@media (min-width: 768px) {
    .empty-icon {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
}

.empty-icon i {
    font-size: 2.5rem;
}

@media (min-width: 768px) {
    .empty-icon i {
        font-size: 3.75rem;
    }
}

.empty-icon-blue {
    background-color: #dbeafe;
    color: #93c5fd;
}

.empty-icon-green {
    background-color: #dcfce7;
    color: #86efac;
}

.empty-icon-purple {
    background-color: #f3e8ff;
    color: #d8b4fe;
}

.empty-icon-orange {
    background-color: #fed7aa;
    color: #fb923c;
}

.empty-title {
    margin-bottom: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #374151;
}

@media (min-width: 768px) {
    .empty-title {
        font-size: 1.25rem;
    }
}

.empty-desc {
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
    max-width: 28rem;
}

@media (min-width: 768px) {
    .empty-desc {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
}

/* Tips Card */
.tips-card {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    border-left: 4px solid #3b82f6;
}

.tips-content {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

@media (min-width: 768px) {
    .tips-content {
        gap: 1.5rem;
    }
}

.tips-icon-wrapper {
    padding: 0.75rem;
    background-color: #dbeafe;
    border-radius: 9999px;
    flex-shrink: 0;
}

.tips-icon {
    color: #2563eb;
    font-size: 1.25rem;
}

.tips-info {
    flex: 1;
}

.tips-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

@media (min-width: 768px) {
    .tips-title {
        font-size: 1.125rem;
    }
}

.tips-list {
    font-size: 0.875rem;
    color: #6b7280;
    list-style: disc;
    list-style-position: inside;
    margin: 0;
    padding: 0;
}

@media (min-width: 768px) {
    .tips-list {
        font-size: 0.875rem;
    }
}

.tips-list li {
    margin-bottom: 0.25rem;
}

.tips-list li:last-child {
    margin-bottom: 0;
}

/* Card Body Overrides */
:deep(.p-card-body) {
    padding: 1rem;
}

@media (min-width: 768px) {
    :deep(.p-card-body) {
        padding: 1.25rem;
    }
}

@media (min-width: 1024px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

/* Mobile Optimization */
@media (max-width: 639px) {
    .claims-tabview :deep(.p-tabview-nav) {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }
    
    .claims-tabview :deep(.p-tabview-nav)::-webkit-scrollbar {
        height: 4px;
    }
    
    .claims-tabview :deep(.p-tabview-nav)::-webkit-scrollbar-track {
        background: #f3f4f6;
    }
    
    .claims-tabview :deep(.p-tabview-nav)::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 2px;
    }
}
</style>