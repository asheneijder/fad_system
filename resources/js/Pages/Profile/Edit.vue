<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile');

const tabs = [
    { id: 'profile', label: 'Profile Information', icon: 'pi pi-user', component: UpdateProfileInformationForm },
    { id: 'password', label: 'Update Password', icon: 'pi pi-lock', component: UpdatePasswordForm },
];
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Profile Settings
                </h2>
                <p class="text-gray-600">
                    Manage your account settings and preferences
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <!-- Desktop Layout -->
                <div class="hidden lg:flex gap-8">
                    <!-- Sidebar Navigation -->
                    <Card class="w-80 h-fit sticky top-6 shadow-lg">
                        <template #content>
                            <div class="space-y-2 p-2">
                                <div 
                                    v-for="tab in tabs" 
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                        'flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-all duration-200',
                                        activeTab === tab.id 
                                            ? 'bg-blue-50 border border-blue-200 text-blue-700' 
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
                                    ]"
                                >
                                    <i :class="tab.icon" class="text-lg"></i>
                                    <span class="font-medium">{{ tab.label }}</span>
                                    <Badge 
                                        v-if="tab.id === 'delete'" 
                                        value="Danger" 
                                        severity="danger" 
                                        class="ml-auto text-xs"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Main Content -->
                    <div class="flex-1 space-y-6">
                        <Card class="shadow-lg border-0">
                            <template #content>
                                <div class="p-6">
                                    <component 
                                        :is="tabs.find(tab => tab.id === activeTab)?.component"
                                        :must-verify-email="mustVerifyEmail"
                                        :status="status"
                                        class="w-full"
                                    />
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <!-- Mobile Layout -->
                <div class="lg:hidden space-y-6">
                    <!-- Mobile Tabs -->
                    <Card class="shadow-lg">
                        <template #content>
                            <div class="p-4">
                                <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        @click="activeTab = tab.id"
                                        :class="[
                                            'flex-1 flex items-center justify-center gap-2 py-3 px-4 text-sm font-medium rounded-md transition-all duration-200',
                                            activeTab === tab.id
                                                ? 'bg-white text-blue-700 shadow-sm'
                                                : 'text-gray-600 hover:text-gray-800'
                                        ]"
                                    >
                                        <i :class="tab.icon"></i>
                                        <span class="hidden sm:inline">{{ tab.label.split(' ')[0] }}</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Mobile Content -->
                    <Card class="shadow-lg border-0">
                        <template #content>
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
                                    <i :class="tabs.find(tab => tab.id === activeTab)?.icon" class="text-xl text-blue-600"></i>
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ tabs.find(tab => tab.id === activeTab)?.label }}
                                    </h3>
                                    <Badge 
                                        v-if="activeTab === 'delete'" 
                                        value="Danger" 
                                        severity="danger" 
                                        class="ml-auto"
                                    />
                                </div>
                                
                                <component 
                                    :is="tabs.find(tab => tab.id === activeTab)?.component"
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                    class="w-full"
                                />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Security Tips -->
                <Card class="mt-8 shadow-lg bg-gradient-to-r from-blue-50 to-indigo-50 border-0">
                    <template #content>
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-blue-100 rounded-full mt-1">
                                    <i class="pi pi-info-circle text-xl text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Security Tips</h4>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check-circle text-green-500"></i>
                                            Use a strong, unique password
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check-circle text-green-500"></i>
                                            Enable two-factor authentication if available
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check-circle text-green-500"></i>
                                            Keep your email address updated
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="pi pi-check-circle text-green-500"></i>
                                            Regularly review your account activity
                                        </li>
                                    </ul>
                                </div>
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
    padding: 0;
}

:deep(.p-card-content) {
    padding: 0;
}

:deep(.p-badge) {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Smooth transitions for tab content */
.tab-content-enter-active,
.tab-content-leave-active {
    transition: all 0.3s ease;
}

.tab-content-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.tab-content-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>