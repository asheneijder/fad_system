<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import { router, Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const logs = ref(props.logs);

watch(() => props.logs, (newLogs) => {
    logs.value = newLogs;
});

watch(search, (newSearch) => {
    router.get(route("admin.audit-logs.index"), { search: newSearch }, { preserveState: true, replace: true });
});

const onPageChange = (event) => {
    router.get(route("admin.audit-logs.index"), { search: search.value, page: event.page + 1 }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Audit Logs" />
    <AppLayout>
        <div class="p-6 card">
            <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <h2 class="text-xl font-semibold">Audit Logs</h2>
                <InputText v-model="search" placeholder="Search logs..." class="w-full p-inputtext-sm sm:w-64" />
            </div>

            <DataTable 
                :value="logs.data"
                showGridlines 
                :rowHover="true" 
                paginator 
                lazy 
                :rows="logs.per_page"
                :totalRecords="logs.total"
                :first="(logs.current_page - 1) * logs.per_page"
                @page="onPageChange"
                responsiveLayout="scroll" 
                tableStyle="min-width: 70rem"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-6">
                        <p class="mt-2 text-lg font-medium text-gray-500">No logs found</p>
                        <p class="text-sm text-gray-400">Try adjusting your filters.</p>
                    </div>
                </template>

                <!-- Index number -->
                <Column header="#" style="width: 50px">
                    <template #body="slotProps">
                        {{ (logs.current_page - 1) * logs.per_page + slotProps.index + 1 }}
                    </template>
                </Column>

                <Column field="log_name" header="Log Name" />
                <Column field="description" header="Description" />
                <Column field="event" header="Event" />
                <Column field="subject_type" header="Subject Type" />
                <Column field="subject_id" header="Subject ID" />
                <Column header="Causer">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.causer">
                            {{ slotProps.data.causer.name }} ({{ slotProps.data.causer.email }})
                        </span>
                        <span v-else class="text-gray-400 italic">System</span>
                    </template>
                </Column>
                <Column field="created_at" header="Created At" />
            </DataTable>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-inputtext-sm {
    width: 250px;
}
</style>
