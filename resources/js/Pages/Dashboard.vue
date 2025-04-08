<script setup>
    import AppLayout from "@/sakai/layout/AppLayout.vue";
    import { Head } from "@inertiajs/vue3";

    const props = defineProps({
        users: Number,
        assets: Number,
    });
</script>

<template>
    <Head title="Dashboard" />
    <app-layout>
        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="mb-0 card">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block mb-4 font-medium text-muted-color">User</span>
                            <div class="text-xl font-medium text-surface-900 dark:text-surface-0">{{ props.users }}</div>
                        </div>
                        <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-users text-blue-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="mb-0 card">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block mb-4 font-medium text-muted-color">Asset</span>
                            <div class="text-xl font-medium text-surface-900 dark:text-surface-0">{{ props.assets }}</div>
                        </div>
                        <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-unlock text-orange-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="mb-0 card">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block mb-4 font-medium text-muted-color">Permission</span>
                            <div class="text-xl font-medium text-surface-900 dark:text-surface-0">{{ props.users }}</div>
                        </div>
                        <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-key text-cyan-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>
    
<style scoped lang="scss">

</style>
    