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
    accommodationClaim: {
        type: Object,
        default: () => ({})
    }
});

const route = window.route || (() => {});

const home = { icon: 'pi pi-home', url: route('dashboard') };
const items = [
    { label: 'Claim Requests', url: route('user.request-claim.index') },
    { label: 'Accommodation Claim Details' }
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

const formatCurrency = (amount, currency = 'MYR') => {
    const currencySymbols = {
        MYR: 'RM',
        USD: '$',
        SGD: 'S$'
    };
    
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
    router.get(route('user.accommodation-claims.edit', { accommodationClaim: props.accommodationClaim.id }));
};

const goBack = () => {
    router.get(route('user.request-claim.index'));
};

const deleteClaim = () => {
    if (confirm('Are you sure you want to delete this accommodation claim? This action cannot be undone.')) {
        loading.value = true;
        router.delete(route('user.accommodation-claims.destroy', { accommodationClaim: props.accommodationClaim.id }), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Accommodation claim deleted successfully',
                    life: 3000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete accommodation claim',
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
    
    router.post(route('user.accommodation-claims.submit', { accommodationClaim: props.accommodationClaim.id }), {}, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Accommodation claim submitted for approval',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to submit accommodation claim',
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
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Accommodation Claim #${props.accommodationClaim.id}</title>
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
                <h1>Accommodation Claim #${props.accommodationClaim.id}</h1>
                <p>Generated on ${new Date().toLocaleDateString('en-MY')}</p>
                <p><strong>Status:</strong> ${getStatusText(props.accommodationClaim.status)}</p>
            </div>

            <div class="section">
                <div class="section-title">Requester Information</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Submitted By</div>
                        <div class="field-value">${props.accommodationClaim.user?.name || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Department</div>
                        <div class="field-value">${props.accommodationClaim.user?.department || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Position</div>
                        <div class="field-value">${props.accommodationClaim.user?.position || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Employee ID</div>
                        <div class="field-value">${props.accommodationClaim.user?.employee_id || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Accommodation Details</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Hotel Name</div>
                        <div class="field-value">${props.accommodationClaim.hotel_name || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Stay Period</div>
                        <div class="field-value">${formatDate(props.accommodationClaim.check_in_date)} - ${formatDate(props.accommodationClaim.check_out_date)}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Number of Nights</div>
                        <div class="field-value">${props.accommodationClaim.number_of_nights || 0}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Currency</div>
                        <div class="field-value">${props.accommodationClaim.currency_display || '—'}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Destination</div>
                        <div class="field-value">${props.accommodationClaim.destination_city || '—'}, ${props.accommodationClaim.destination_country || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Cost Breakdown</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Rate per Night (${props.accommodationClaim.number_of_nights} nights × ${formatCurrency(props.accommodationClaim.rate_per_night, props.accommodationClaim.currency)})</td>
                            <td>${formatCurrency(props.accommodationClaim.subtotal_amount, props.accommodationClaim.currency)}</td>
                        </tr>
                        <tr>
                            <td>Tax (${props.accommodationClaim.tax_percentage}%)</td>
                            <td>${formatCurrency(props.accommodationClaim.tax_amount, props.accommodationClaim.currency)}</td>
                        </tr>
                        <tr>
                            <td>Service Charge (${props.accommodationClaim.service_charge_percentage}%)</td>
                            <td>${formatCurrency(props.accommodationClaim.service_charge_amount, props.accommodationClaim.currency)}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td><strong>Total Claim Amount</strong></td>
                            <td><strong>${formatCurrency(props.accommodationClaim.total_amount, props.accommodationClaim.currency)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="section">
                <div class="section-title">Purpose of Travel</div>
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
                    ${props.accommodationClaim.purpose || 'No purpose specified'}
                </div>
            </div>

            ${props.accommodationClaim.remarks ? `
            <div class="section">
                <div class="section-title">Additional Remarks</div>
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
                    ${props.accommodationClaim.remarks}
                </div>
            </div>
            ` : ''}

            <div class="section">
                <div class="section-title">Claim Information</div>
                <div class="grid">
                    <div class="field">
                        <div class="field-label">Created Date</div>
                        <div class="field-value">${formatDateTime(props.accommodationClaim.created_at)}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Check-in Date</div>
                        <div class="field-value">${formatDate(props.accommodationClaim.check_in_date)}</div>
                    </div>
                    ${props.accommodationClaim.approval_date ? `
                    <div class="field">
                        <div class="field-label">Approval Date</div>
                        <div class="field-value">${formatDateTime(props.accommodationClaim.approval_date)}</div>
                    </div>
                    ` : ''}
                    ${props.accommodationClaim.approver ? `
                    <div class="field">
                        <div class="field-label">Approved By</div>
                        <div class="field-value">${props.accommodationClaim.approver.name}</div>
                    </div>
                    ` : ''}
                </div>
            </div>

            <div class="footer">
                <p>This is an automatically generated accommodation claim document.</p>
                <p>Document ID: ${props.accommodationClaim.id} | Generated on: ${new Date().toLocaleString('en-MY')}</p>
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

// Format stay period for display
const stayPeriod = computed(() => {
    if (!props.accommodationClaim.check_in_date || !props.accommodationClaim.check_out_date) return '—';
    
    const checkIn = new Date(props.accommodationClaim.check_in_date);
    const checkOut = new Date(props.accommodationClaim.check_out_date);
    
    return `${checkIn.toLocaleDateString('en-MY')} - ${checkOut.toLocaleDateString('en-MY')}`;
});

// Combine all documents
const allDocuments = computed(() => {
    const hotelReceipts = props.accommodationClaim.hotel_receipts || [];
    const supportingDocuments = props.accommodationClaim.supporting_documents || [];
    return [...hotelReceipts, ...supportingDocuments];
});
</script>

<template>
    <Head :title="`Accommodation Claim #${accommodationClaim.id}`" />
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
                                Penginapan pada tarikh tersebut adalah benar dan telah dibuat atas urusan rasmi;
                            </li>
                            <li class="pl-2">
                                Tuntutan ini dibuat mengikut kadar dan syarat-syarat yang dinyatakan dalam peraturan-peraturan bagi pegawai-pegawai rasmi dan/atau pegawai yang berkuasa kuasa semasa;
                            </li>
                            <li class="pl-2">
                                Perbelanjaan yang berjumlah sebanyak <strong class="text-blue-600">{{ formatCurrency(accommodationClaim.total_amount, accommodationClaim.currency) }}</strong> telah dilakukan dan dibayar oleh saya;
                            </li>
                            <li class="pl-2">
                                Butir-butir seperti yang dinyatakan di atas adalah benar dan saya bertanggungjawab terhadapnya.
                            </li>
                        </ol>

                        <div class="mt-6 pt-4 border-t border-gray-300">
                            <p class="font-semibold">Jumlah Tuntutan: {{ formatCurrency(accommodationClaim.total_amount, accommodationClaim.currency) }}</p>
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
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Accommodation Claim #{{ accommodationClaim.id }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :value="getStatusText(accommodationClaim.status)"
                                :severity="getStatusSeverity(accommodationClaim.status)"
                                class="capitalize responsive-badge" />
                            <span class="text-sm text-gray-500">Created on {{ formatDateTime(accommodationClaim.created_at) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button v-if="accommodationClaim.status === 'draft'" 
                        label="Edit Claim" icon="pi pi-pencil" severity="warning"
                        @click="editClaim" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="accommodationClaim.status === 'draft'"
                        label="Submit for Approval" icon="pi pi-send" severity="primary"
                        @click="openAcknowledgmentDialog" :loading="loading"
                        class="responsive-button" />
                    
                    <Button v-if="accommodationClaim.status === 'draft'"
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
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.user?.name || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Department</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.user?.department || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Position</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.user?.position || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Approver Name</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.user?.approver?.name || 'No Approver Assigned' }}</p>
                                    <p v-if="accommodationClaim.user?.approver?.email" class="text-sm text-gray-500">
                                        {{ accommodationClaim.user.approver.email }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Accommodation Details -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-building text-green-500"></i>
                                <span class="text-lg font-semibold">Accommodation Details</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Hotel Name</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.hotel_name || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Stay Period</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ stayPeriod }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Number of Nights</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.number_of_nights || 0 }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Currency</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.currency_display || '—' }}</p>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Destination</label>
                                    <p class="text-lg font-semibold text-gray-900">{{ accommodationClaim.destination_city || '—' }}, {{ accommodationClaim.destination_country || '—' }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Cost Breakdown -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-money-bill text-orange-500"></i>
                                <span class="text-lg font-semibold">Cost Breakdown</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <!-- Cost Details Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3">Description</th>
                                                <th class="px-4 py-3 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    Rate per Night ({{ accommodationClaim.number_of_nights }} nights × {{ formatCurrency(accommodationClaim.rate_per_night, accommodationClaim.currency) }})
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold">
                                                    {{ formatCurrency(accommodationClaim.subtotal_amount, accommodationClaim.currency) }}
                                                </td>
                                            </tr>
                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    Tax ({{ accommodationClaim.tax_percentage }}%)
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold">
                                                    {{ formatCurrency(accommodationClaim.tax_amount, accommodationClaim.currency) }}
                                                </td>
                                            </tr>
                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    Service Charge ({{ accommodationClaim.service_charge_percentage }}%)
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold">
                                                    {{ formatCurrency(accommodationClaim.service_charge_amount, accommodationClaim.currency) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 font-semibold">
                                            <tr>
                                                <td class="px-4 py-3 text-gray-900">Total Claim Amount</td>
                                                <td class="px-4 py-3 text-right text-blue-600">
                                                    {{ formatCurrency(accommodationClaim.total_amount, accommodationClaim.currency) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Calculation Summary -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700">Rate Calculation</label>
                                        <p class="text-sm text-gray-900">
                                            {{ accommodationClaim.number_of_nights }} nights × {{ formatCurrency(accommodationClaim.rate_per_night, accommodationClaim.currency) }}
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700">Additional Charges</label>
                                        <p class="text-sm text-gray-900">
                                            Tax: {{ accommodationClaim.tax_percentage }}% + Service: {{ accommodationClaim.service_charge_percentage }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Purpose & Remarks -->
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
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ accommodationClaim.purpose || 'No purpose specified' }}</p>
                                </div>
                                
                                <div v-if="accommodationClaim.remarks" class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-500">Additional Remarks</label>
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ accommodationClaim.remarks }}</p>
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
                                    <Badge :value="getStatusText(accommodationClaim.status)"
                                        :severity="getStatusSeverity(accommodationClaim.status)"
                                        class="capitalize text-lg px-4 py-2" />
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Created:</span>
                                        <span class="font-medium">{{ formatDateTime(accommodationClaim.created_at) }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.updated_at && accommodationClaim.updated_at !== accommodationClaim.created_at" class="flex justify-between">
                                        <span class="text-gray-500">Last Updated:</span>
                                        <span class="font-medium">{{ formatDateTime(accommodationClaim.updated_at) }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.check_in_date" class="flex justify-between">
                                        <span class="text-gray-500">Check-in Date:</span>
                                        <span class="font-medium">{{ formatDate(accommodationClaim.check_in_date) }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.check_out_date" class="flex justify-between">
                                        <span class="text-gray-500">Check-out Date:</span>
                                        <span class="font-medium">{{ formatDate(accommodationClaim.check_out_date) }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.approval_date" class="flex justify-between">
                                        <span class="text-gray-500">Approval Date:</span>
                                        <span class="font-medium">{{ formatDateTime(accommodationClaim.approval_date) }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.approver" class="flex justify-between">
                                        <span class="text-gray-500">Approved By:</span>
                                        <span class="font-medium">{{ accommodationClaim.approver.name }}</span>
                                    </div>
                                    <div v-if="accommodationClaim.rejection_reason" class="flex justify-between">
                                        <span class="text-gray-500">Rejection Reason:</span>
                                        <span class="font-medium text-red-600">{{ accommodationClaim.rejection_reason }}</span>
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
                                <Badge v-if="allDocuments.length > 0" 
                                    :value="allDocuments.length" 
                                    class="ml-2 responsive-badge" />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="allDocuments.length > 0" class="space-y-2">
                                <!-- Hotel Receipts -->
                                <div v-if="accommodationClaim.hotel_receipts && accommodationClaim.hotel_receipts.length > 0" class="space-y-2">
                                    <h4 class="font-medium text-gray-700 text-sm">Hotel Receipts</h4>
                                    <div v-for="(doc, index) in accommodationClaim.hotel_receipts" :key="`hotel-${index}`" 
                                        class="flex items-center justify-between p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <i class="pi pi-file text-blue-500 flex-shrink-0"></i>
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

                                <!-- Supporting Documents -->
                                <div v-if="accommodationClaim.supporting_documents && accommodationClaim.supporting_documents.length > 0" class="space-y-2">
                                    <h4 class="font-medium text-gray-700 text-sm">Other Documents</h4>
                                    <div v-for="(doc, index) in accommodationClaim.supporting_documents" :key="`support-${index}`" 
                                        class="flex items-center justify-between p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <i class="pi pi-file text-green-500 flex-shrink-0"></i>
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
                            </div>
                            <div v-else class="text-center py-6">
                                <i class="pi pi-file text-gray-400 text-4xl mb-3"></i>
                                <p class="text-gray-500">No supporting documents uploaded</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Actions -->
                    <Card class="shadow-lg">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-bolt text-yellow-500"></i>
                                <span class="text-lg font-semibold">Quick Actions</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-3">
                                
                                <Button v-if="accommodationClaim.status === 'draft'"
                                    label="Submit for Approval" icon="pi pi-send" severity="primary"
                                    class="w-full justify-start" @click="openAcknowledgmentDialog" />
                                
                                <Button v-if="accommodationClaim.status === 'approved'"
                                    label="Print Claim" icon="pi pi-print" severity="secondary"
                                    outlined class="w-full justify-start" @click="printClaim" />
                                
                                <Button label="Back to Claims" icon="pi pi-arrow-left" severity="secondary"
                                    text class="w-full justify-start" @click="goBack" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.responsive-button {
    @apply text-xs sm:text-sm;
}

.responsive-icon-button {
    @apply w-8 h-8 sm:w-10 sm:h-10;
}

.responsive-badge {
    @apply text-xs;
}

@media (max-width: 640px) {
    .grid-cols-1 {
        grid-template-columns: 1fr;
    }
    
    .md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>