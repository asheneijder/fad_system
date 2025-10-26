<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import { useToast } from "primevue/usetoast";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, nextTick } from "vue";

const toast = useToast();

const props = defineProps({
    dailyAllowance: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Daily Allowance Details' }
];

const loading = ref(false);
const showAcknowledgmentDialog = ref(false);
const hasScrolledToBottom = ref(false);
const agreedToTerms = ref(false);

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

// FIXED: Ensure amount is treated as number
const formatCurrency = (amount, currency = 'MYR') => {
    const currencySymbols = {
        MYR: 'RM',
        USD: '$'
    };
    
    // Convert to number and handle null/undefined
    const numericAmount = Number(amount) || 0;
    
    return `${currencySymbols[currency] || ''}${numericAmount.toFixed(2)}`;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const editClaim = () => {
    router.get(route('user.daily-allowances.edit', { dailyAllowance: props.dailyAllowance.id }));
};

const goBack = () => {
    router.get(route('user.request-claim.index'));
};

const deleteClaim = () => {
    if (confirm('Are you sure you want to delete this daily allowance claim? This action cannot be undone.')) {
        loading.value = true;
        router.delete(route('user.daily-allowances.destroy', { dailyAllowance: props.dailyAllowance.id }), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Daily allowance claim deleted successfully',
                    life: 3000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete daily allowance claim',
                    life: 3000
                });
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    }
};

const openAcknowledgmentDialog = () => {
    showAcknowledgmentDialog.value = true;
    hasScrolledToBottom.value = false;
    agreedToTerms.value = false;
    
    nextTick(() => {
        const content = document.getElementById('acknowledgment-content');
        if (content) {
            content.scrollTop = 0;
        }
    });
};

const onAcknowledgmentScroll = (event) => {
    const element = event.target;
    const scrollPosition = element.scrollTop + element.clientHeight;
    const totalHeight = element.scrollHeight;
    const isAtBottom = Math.abs(scrollPosition - totalHeight) < 5;
    
    if (isAtBottom) {
        hasScrolledToBottom.value = true;
    }
};

const submitForApproval = () => {
    if (!agreedToTerms.value) {
        toast.add({
            severity: 'warn',
            summary: 'Acknowledgement Required',
            detail: 'Please agree to the terms before submitting',
            life: 3000
        });
        return;
    }

    loading.value = true;
    showAcknowledgmentDialog.value = false;
    
    router.post(route('user.daily-allowances.submit', { dailyAllowance: props.dailyAllowance.id }), {}, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Daily allowance claim submitted for approval',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to submit daily allowance claim',
                life: 3000
            });
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

const printClaim = () => {
    const printWindow = window.open('', '_blank');
    
    // FIXED: Ensure all amounts are properly formatted
    const dailyRate = Number(props.dailyAllowance.daily_rate) || 0;
    const claimAmount = Number(props.dailyAllowance.claim_amount) || 0;
    
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Daily Allowance Claim #${props.dailyAllowance.id}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .section { margin-bottom: 25px; }
                .section-title { background: #f5f5f5; padding: 10px; font-weight: bold; border-left: 4px solid #3b82f6; }
                .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
                .field { margin-bottom: 10px; }
                .field-label { font-weight: bold; color: #666; }
                .field-value { margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .total-row { background-color: #f8f9fa; font-weight: bold; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Daily Allowance Claim #${props.dailyAllowance.id}</h1>
                <p>Generated on ${new Date().toLocaleDateString('en-MY')}</p>
                <p><strong>Status:</strong> ${getStatusText(props.dailyAllowance.status)}</p>
            </div>

            <div class="section">
                <div class="section-title">Requester Information</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Submitted By</div>
                        <div class="field-value">${props.dailyAllowance.user?.name || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Department</div>
                        <div class="field-value">${props.dailyAllowance.user?.department || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Position</div>
                        <div class="field-value">${props.dailyAllowance.user?.position || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Employee ID</div>
                        <div class="field-value">${props.dailyAllowance.user?.employee_id || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Allowance Details</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Claim Date</div>
                        <div class="field-value">${formatDate(props.dailyAllowance.claim_date)}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Allowance Type</div>
                        <div class="field-value">${props.dailyAllowance.allowance_type_display}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Currency</div>
                        <div class="field-value">${props.dailyAllowance.currency_display}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Daily Rate</div>
                        <div class="field-value">${formatCurrency(dailyRate, props.dailyAllowance.currency)}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Claim Percentage</div>
                        <div class="field-value">${props.dailyAllowance.claim_percentage}%</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Destination</div>
                        <div class="field-value">${props.dailyAllowance.destination || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Purpose of Travel</div>
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
                    ${props.dailyAllowance.purpose || 'No purpose specified'}
                </div>
            </div>

            <div class="section">
                <div class="section-title">Cost Summary</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div>
                        <div class="field">
                            <div class="field-label">Daily Rate</div>
                            <div class="field-value">${formatCurrency(dailyRate, props.dailyAllowance.currency)}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">Claim Percentage</div>
                            <div class="field-value">${props.dailyAllowance.claim_percentage}%</div>
                        </div>
                        <div class="field" style="margin-top: 20px; padding-top: 10px; border-top: 2px solid #333;">
                            <div class="field-label" style="font-size: 18px;">Total Claim Amount</div>
                            <div class="field-value" style="font-size: 20px; color: #3b82f6;">${formatCurrency(claimAmount, props.dailyAllowance.currency)}</div>
                        </div>
                    </div>
                    <div style="background: #f0f9ff; padding: 15px; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #0369a1;">Calculation</h4>
                        <p>${formatCurrency(dailyRate, props.dailyAllowance.currency)} × ${props.dailyAllowance.claim_percentage}% = ${formatCurrency(claimAmount, props.dailyAllowance.currency)}</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Claim Information</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Created Date</div>
                        <div class="field-value">${formatDateTime(props.dailyAllowance.created_at)}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Claim Date</div>
                        <div class="field-value">${formatDate(props.dailyAllowance.claim_date)}</div>
                    </div>
                    ${props.dailyAllowance.approval_date ? `
                    <div class="field">
                        <div class="field-label">Approval Date</div>
                        <div class="field-value">${formatDateTime(props.dailyAllowance.approval_date)}</div>
                    </div>
                    ` : ''}
                    ${props.dailyAllowance.approver ? `
                    <div class="field">
                        <div class="field-label">Approved By</div>
                        <div class="field-value">${props.dailyAllowance.approver.name}</div>
                    </div>
                    ` : ''}
                </div>
            </div>

            <div class="footer">
                <p>This is an automatically generated daily allowance claim document.</p>
                <p>Document ID: ${props.dailyAllowance.id} | Generated on: ${new Date().toLocaleString('en-MY')}</p>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
};

const downloadDocument = (document) => {
    if (document.url) {
        window.open(document.url, '_blank');
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Download Unavailable',
            detail: 'Document download is not available',
            life: 3000
        });
    }
};

const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const allowanceTypeLabel = computed(() => {
    const types = {
        full_day: 'Full Day',
        breakfast: 'Breakfast Only',
        lunch: 'Lunch Only',
        dinner: 'Dinner Only'
    };
    return types[props.dailyAllowance.allowance_type] || props.dailyAllowance.allowance_type;
});

// FIXED: Safe computed properties for amounts
const dailyRate = computed(() => {
    return Number(props.dailyAllowance.daily_rate) || 0;
});

const claimAmount = computed(() => {
    return Number(props.dailyAllowance.claim_amount) || 0;
});
</script>

<template>
    <Head :title="`Daily Allowance #${dailyAllowance.id}`" />
    <AppLayout>
        <Toast />

        <!-- Acknowledgment Dialog -->
        <Dialog v-model:visible="showAcknowledgmentDialog" modal header="Declaration and Acknowledgment" 
                :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <div class="space-y-4">
                <div class="text-sm text-gray-600">
                    <p>Please read the following declaration carefully before submitting your claim.</p>
                </div>

                <div id="acknowledgment-content" 
                     class="border border-gray-200 rounded-lg p-4 max-h-60 overflow-y-auto bg-gray-50"
                     @scroll="onAcknowledgmentScroll">
                    <div class="space-y-4 text-sm text-gray-700">
                        <p class="font-semibold text-center text-gray-800 mb-4">
                            PERAKUAN DAN PENGAKUAN
                        </p>
                        
                        <p>
                            Saya dengan ini mengaku bahawa:
                        </p>
                        
                        <ol class="list-decimal list-inside space-y-2 ml-4">
                            <li class="pl-2">
                                Perjalanan pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(claimAmount, dailyAllowance.currency) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(claimAmount, dailyAllowance.currency) }}</p>
                            <p class="mt-2">Tarikh: {{ currentDate }}</p>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-xs text-yellow-700">
                                <strong>Perhatian:</strong> Pengesahan ini adalah mengikat. Sebarang maklumat palsu boleh menyebabkan tindakan tatatertib.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <Checkbox v-model="agreedToTerms" inputId="agreeTerms" :binary="true" 
                             :disabled="!hasScrolledToBottom" />
                    <label for="agreeTerms" class="text-sm text-gray-700">
                        Saya telah membaca dan memahami semua pernyataan di atas dan bersetuju dengan syarat-syarat yang dinyatakan.
                        <span v-if="!hasScrolledToBottom" class="text-orange-600 text-xs block mt-1">
                            (Sila baca sehingga ke akhir untuk membolehkan kotak semak)
                        </span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
                    <Button label="Cancel" severity="secondary" text
                            @click="showAcknowledgmentDialog = false" />
                    <Button label="Agree & Submit" severity="primary" 
                            @click="submitForApproval" 
                            :disabled="!agreedToTerms || !hasScrolledToBottom"
                            :loading="loading" />
                </div>
            </div>
        </Dialog>

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Breadcrumb -->
            <Breadcrumb :home="home" :model="items" class="mb-4">
                <template #item="{ item }">
                    <span v-if="item.url" class="text-gray-600 hover:text-gray-900 text-sm sm:text-base cursor-pointer" @click="router.get(item.url)">
                        {{ item.label }}
                    </span>
                    <span v-else class="font-semibold text-gray-700 text-sm sm:text-base">{{ item.label }}</span>
                </template>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Button icon="pi pi-arrow-left" text rounded @click="goBack" 
                        class="responsive-icon-button" v-tooltip="'Back to Claims'" />
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Daily Allowance #{{ dailyAllowance.id }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :value="getStatusText(dailyAllowance.status)"
                                :severity="getStatusSeverity(dailyAllowance.status)"
                                class="capitalize responsive-badge" />
                            <span class="text-sm text-gray-500">Created on {{ formatDateTime(dailyAllowance.created_at) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button v-if="dailyAllowance.status === 'draft'" 
                        label="Edit Claim" icon="pi pi-pencil" severity="warning"
                        @click="editClaim" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="dailyAllowance.status === 'draft'"
                        label="Submit for Approval" icon="pi pi-send" severity="primary"
                        @click="openAcknowledgmentDialog" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="dailyAllowance.status === 'draft'"
                        label="Delete" icon="pi pi-trash" severity="danger" outlined
                        @click="deleteClaim" :loading="loading"
                        class="responsive-button" />
                </div>
            </div>

            <!-- Claim Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Requester Information -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-user text-blue-500"></i>
                                <span class="text-lg font-semibold">Requester Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Submitted By</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.user?.name || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.user?.department || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Position</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.user?.position || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Approver Name</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.user?.approver?.name || 'No Approver Assigned' }}</p>
                                    <p v-if="dailyAllowance.user?.approver?.email" class="text-sm text-gray-500">
                                        {{ dailyAllowance.user.approver.email }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Allowance Details -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-money-bill text-green-500"></i>
                                <span class="text-lg font-semibold">Allowance Details</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Claim Date</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ formatDate(dailyAllowance.claim_date) }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Allowance Type</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ allowanceTypeLabel }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Currency</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.currency_display }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Daily Rate</label>
                                    <p class="text-lg font-semibold text-blue-600">{{ formatCurrency(dailyRate, dailyAllowance.currency) }}</p>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Claim Percentage</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.claim_percentage }}%</p>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Destination</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ dailyAllowance.destination || '—' }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Purpose & Notes -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-file text-purple-500"></i>
                                <span class="text-lg font-semibold">Purpose & Additional Information</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Purpose of Travel</label>
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ dailyAllowance.purpose || 'No purpose specified' }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Cost Summary -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-calculator text-red-500"></i>
                                <span class="text-lg font-semibold">Cost Summary</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Daily Rate:</span>
                                        <span class="font-semibold">{{ formatCurrency(dailyRate, dailyAllowance.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Claim Percentage:</span>
                                        <span class="font-semibold">{{ dailyAllowance.claim_percentage }}%</span>
                                    </div>
                                    <div class="flex justify-between items-center border-t pt-2">
                                        <span class="text-lg font-bold text-gray-800">Total Claim Amount:</span>
                                        <span class="text-lg font-bold text-blue-600">{{ formatCurrency(claimAmount, dailyAllowance.currency) }}</span>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="pi pi-calculator text-green-600"></i>
                                        <span class="font-semibold text-green-800">Calculation</span>
                                    </div>
                                    <p class="text-sm text-green-700">
                                        {{ formatCurrency(dailyRate, dailyAllowance.currency) }} × {{ dailyAllowance.claim_percentage }}% = {{ formatCurrency(claimAmount, dailyAllowance.currency) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Status & Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-orange-500"></i>
                                <span class="text-lg font-semibold">Claim Status</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <Badge :value="getStatusText(dailyAllowance.status)"
                                        :severity="getStatusSeverity(dailyAllowance.status)"
                                        class="capitalize text-lg px-4 py-2" />
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Created:</span>
                                        <span class="font-medium">{{ formatDateTime(dailyAllowance.created_at) }}</span>
                                    </div>
                                    <div v-if="dailyAllowance.updated_at && dailyAllowance.updated_at !== dailyAllowance.created_at" class="flex justify-between">
                                        <span class="text-gray-500">Last Updated:</span>
                                        <span class="font-medium">{{ formatDateTime(dailyAllowance.updated_at) }}</span>
                                    </div>
                                    <div v-if="dailyAllowance.claim_date" class="flex justify-between">
                                        <span class="text-gray-500">Claim Date:</span>
                                        <span class="font-medium">{{ formatDate(dailyAllowance.claim_date) }}</span>
                                    </div>
                                    <div v-if="dailyAllowance.approval_date" class="flex justify-between">
                                        <span class="text-gray-500">Approval Date:</span>
                                        <span class="font-medium">{{ formatDateTime(dailyAllowance.approval_date) }}</span>
                                    </div>
                                    <div v-if="dailyAllowance.approver" class="flex justify-between">
                                        <span class="text-gray-500">Approved By:</span>
                                        <span class="font-medium">{{ dailyAllowance.approver.name }}</span>
                                    </div>
                                    <div v-if="dailyAllowance.rejection_reason" class="flex justify-between">
                                        <span class="text-gray-500">Rejection Reason:</span>
                                        <span class="font-medium text-red-600">{{ dailyAllowance.rejection_reason }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Supporting Documents -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-paperclip text-green-500"></i>
                                <span class="text-lg font-semibold">Supporting Documents</span>
                                <Badge v-if="dailyAllowance.documents && dailyAllowance.documents.length > 0" 
                                    :value="dailyAllowance.documents.length" 
                                    class="ml-2 responsive-badge" />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="dailyAllowance.documents && dailyAllowance.documents.length > 0" class="space-y-2">
                                <div v-for="(doc, index) in dailyAllowance.documents" :key="index" 
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <i class="pi pi-file text-gray-500 flex-shrink-0"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ doc.name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ (doc.size / 1024).toFixed(1) }} KB • {{ doc.type }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button icon="pi pi-download" text rounded severity="secondary"
                                        v-tooltip="'Download document'" 
                                        @click="downloadDocument(doc)"
                                        class="flex-shrink-0" />
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No documents attached</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-bolt text-red-500"></i>
                                <span class="text-lg font-semibold">Quick Actions</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-2">
                                <Button v-if="dailyAllowance.status === 'draft'" 
                                    label="Submit for Approval" icon="pi pi-send" severity="primary"
                                    @click="openAcknowledgmentDialog" :loading="loading"
                                    class="w-full responsive-button" />
                                
                                <Button v-if="dailyAllowance.status === 'approved'"
                                    label="Print Claim" icon="pi pi-print" severity="secondary" outlined
                                    @click="printClaim" :loading="loading"
                                    class="w-full responsive-button" />
                                
                                <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary" text
                                    @click="goBack"
                                    class="w-full responsive-button" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-card-body) {
        padding: 1.5rem;
    }
}

:deep(.p-card-content) {
    padding: 0;
}

.responsive-button :deep(.p-button-label) {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 0.875rem;
    }
}

@media (min-width: 1024px) {
    .responsive-button :deep(.p-button-label) {
        font-size: 1rem;
    }
}

.responsive-icon-button :deep(.p-button) {
    width: 2rem;
    height: 2rem;
}

@media (min-width: 640px) {
    .responsive-icon-button :deep(.p-button) {
        width: 2.5rem;
        height: 2.5rem;
    }
}

.responsive-badge :deep(.p-badge) {
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
}

@media (min-width: 640px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
}

@media (min-width: 1024px) {
    .responsive-badge :deep(.p-badge) {
        font-size: 0.875rem;
        padding: 0.35rem 0.7rem;
    }
}
</style>