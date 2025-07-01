<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/sakai/layout/AppLayout.vue'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast();

const props = defineProps({
    asset: Object,
})

const showHistory = ref(false)
const showStatusDialog = ref(false)

const selectedStatus = ref(null)
const statusRemarks = ref('')
const returnedAt = ref(null)

const statuses = [
    { label: 'Available', value: 'available' },
    { label: 'In Maintenance', value: 'in_maintenance' },
    { label: 'Damaged', value: 'damaged' },
    { label: 'Lost', value: 'lost' },
    { label: 'Retired', value: 'retired' },
    { label: 'Disposed', value: 'disposed' },
]

onMounted(() => {
    selectedStatus.value = props.asset.status
})

const updateStatus = () => {
    const statusesThatRequireReturnDate = ['retired', 'disposed', 'lost', 'damaged', 'available'];

    if (!selectedStatus.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validation Error',
            detail: 'Please select a new status.',
            life: 3000
        });
        return;
    }

    if (statusesThatRequireReturnDate.includes(selectedStatus.value) && !returnedAt.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validation Error',
            detail: 'Return date is required for this status.',
            life: 3000
        });
        return;
    }

    router.put(route('admin.assets.updateStatus', props.asset.id), {
        status: selectedStatus.value,
        remarks: statusRemarks.value,
        returned_at: returnedAt.value,
    }, {
        onSuccess: () => {
            showStatusDialog.value = false;
        }
    });
};


</script>

<template>

    <Head title="Asset Details" />
    <AppLayout>
        <div class="p-6 bg-white rounded shadow-md">
            <h2 class="mb-6 text-2xl font-bold text-gray-800">Asset Information</h2>

            <!-- Asset Image -->
            <div v-if="asset.media?.length" class="flex justify-center mb-6">
                <div class="w-full max-w-md overflow-hidden border border-gray-200 shadow-md rounded-xl">
                    <img :src="asset.media[0].original_url" alt="Asset Image"
                        class="object-contain w-full h-64 bg-white" />
                </div>
            </div>

            <!-- Asset Details Grid -->
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Asset Name</p>
                    <p class="font-semibold text-gray-800">{{ asset.asset_name }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Asset Tag No</p>
                    <p class="font-semibold text-gray-800">{{ asset.asset_tag_no }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Serial Number</p>
                    <p class="font-semibold text-gray-800">{{ asset.serial_no }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Category</p>
                    <p class="font-semibold text-gray-800">{{ asset.model_type?.category_type?.category_name || '-' }}
                    </p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Model</p>
                    <p class="font-semibold text-gray-800">{{ asset.model_type?.model_name || '-' }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Location</p>
                    <p class="font-semibold text-gray-800">{{ asset.location || '-' }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Quantity</p>
                    <p class="font-semibold text-gray-800">{{ asset.qty }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Status</p>
                    <p class="font-semibold" :class="asset.status === 'active' ? 'text-green-600' : 'text-red-600'">
                        {{ asset.status.charAt(0).toUpperCase() + asset.status.slice(1) }}
                    </p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Purchase Date</p>
                    <p class="font-semibold text-gray-800">{{ asset.purchase_date || '-' }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Purchase Cost (MYR)</p>
                    <p class="font-semibold text-gray-800">RM {{ Number(asset.purchase_cost || 0).toFixed(2) }}</p>
                </div>
                <div class="p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Current Value (MYR)</p>
                    <p class="font-semibold text-gray-800">RM {{ Number(asset.current_value || 0).toFixed(2) }}</p>
                </div>
                <div class="col-span-2 p-4 border rounded bg-gray-50">
                    <p class="text-gray-500">Assigned To</p>
                    <p class="font-semibold text-gray-800">{{ asset.current_assignment?.user?.name || 'Unassigned' }}
                    </p>
                </div>

                <div class="flex flex-col col-span-2 gap-2 sm:flex-row">
                    <Button label="View Assignment History" icon="pi pi-history" @click="showHistory = true"
                        severity="danger" class="w-full sm:w-auto" variant="outlined" />

                    <Button label="Change Status" icon="pi pi-cog" @click="showStatusDialog = true" severity="info"
                        class="w-full sm:w-auto" variant="outlined" />
                </div>

            </div>
        </div>

        <Dialog v-model:visible="showHistory" modal header="Assignment History" :style="{ width: '50vw' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">

            <div class="overflow-x-auto">
                <table class="w-full text-sm border">
                    <thead class="text-left bg-gray-100">
                        <tr>
                            <th class="p-2 border">User</th>
                            <th class="p-2 border">Assigned At</th>
                            <th class="p-2 border">Returned At</th>
                            <th class="p-2 border">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="assignment in asset.assignments" :key="assignment.id">
                            <td class="p-2 border">{{ assignment.user?.name || '-' }}</td>
                            <td class="p-2 border">{{ assignment.assigned_at || '-' }}</td>
                            <td class="p-2 border">{{ assignment.returned_at || '-' }}</td>
                            <td class="p-2 border">{{ assignment.remarks || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Dialog>

        <Dialog v-model:visible="showStatusDialog" modal header="Change Asset Status" :style="{ width: '30vw' }"
            :breakpoints="{ '1199px': '50vw', '575px': '80vw' }">
            <div class="space-y-4">
                <!-- Status Dropdown -->
                <label for="status" class="block text-sm text-gray-600">Select New Status</label>
                <Select v-model="selectedStatus" :options="statuses" optionLabel="label" optionValue="value"
                    placeholder="Select Status" class="w-full" />

                <div v-if="['retired', 'disposed', 'lost', 'damaged', 'available'].includes(selectedStatus)">
                    <label for="returned_at" class="block text-sm text-gray-600">Return Date</label>
                    <DatePicker v-model="returnedAt" inputId="returned_at" dateFormat="yy-mm-dd" class="w-full"
                        showIcon />
                </div>

                <label for="remarks" class="block text-sm text-gray-600">Remarks</label>
                <Textarea v-model="statusRemarks" autoResize rows="3" class="w-full"
                    placeholder="Enter remarks (e.g. sent to repair center, retired due to damage, etc.)" />

                <!-- Submit -->
                <div class="text-right">
                    <Button label="Update Status" icon="pi pi-check" @click="updateStatus" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
