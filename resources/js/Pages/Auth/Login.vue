<script setup>
import { useForm } from "@inertiajs/vue3";
import Message from 'primevue/message';
import Password from 'primevue/password';
import Button from 'primevue/button';
import { Head } from "@inertiajs/vue3";
import Checkbox from 'primevue/checkbox';
import { Link } from "@inertiajs/vue3";
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import { ref } from 'vue';

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

const passwordVisible = ref(false);
</script>

<template>
    <Head title="Log in" />
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-emerald-900 flex items-center justify-center p-4">
        <!-- Background Decorations -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute top-40 left-40 w-80 h-80 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>

        <div class="relative w-full max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <!-- Left Side - Branding -->
                <div class="text-center lg:text-left space-y-8">
                    <div class="space-y-4">
                        <div class="flex items-center justify-center lg:justify-start space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-4xl lg:text-5xl font-bold bg-gradient-to-r from-emerald-600 to-teal-700 bg-clip-text text-transparent">
                                    ART Inventory System
                                </h1>
                                <p class="text-lg text-gray-600 dark:text-gray-300 mt-2">Amanah Raya Trustees Berhad</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Staff Inventory Management System</p>
                            </div>
                        </div>
                        
                        <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed max-w-md mx-auto lg:mx-0">
                            Streamline your stationary requests and inventory management with our trusted solution.
                        </p>
                    </div>

                    <!-- Features List -->
                    <div class="space-y-4 max-w-md mx-auto lg:mx-0">
                        <div class="flex items-center space-x-3 p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg backdrop-blur-sm">
                            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-shopping-cart text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Easy Stationary Requests</span>
                        </div>
                        <div class="flex items-center space-x-3 p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg backdrop-blur-sm">
                            <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-clock text-teal-600 dark:text-teal-400 text-sm"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Quick Approval Process</span>
                        </div>
                        <div class="flex items-center space-x-3 p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg backdrop-blur-sm">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-chart-line text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Real-time Stock Tracking</span>
                        </div>
                        <div class="flex items-center space-x-3 p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg backdrop-blur-sm">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-inbox text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Department-wise Management</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Login Form -->
                <Card class="shadow-2xl border-0 backdrop-blur-sm bg-white/80 dark:bg-gray-800/80">
                    <template #content>
                        <div class="p-6 sm:p-8">
                            <!-- Header -->
                            <div class="text-center mb-8">
                                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg mx-auto mb-4">
                                    <i class="pi pi-user text-white text-2xl"></i>
                                </div>
                                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    Staff Portal
                                </h2>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Access inventory management system
                                </p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Email Field -->
                                <div class="space-y-2">
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Corporate Email
                                    </label>
                                    <div class="relative email-wrapper">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                            <i class="pi pi-envelope text-gray-400"></i>
                                        </div>
                                        <InputText 
                                            id="email" 
                                            type="email" 
                                            placeholder="your.name@amanahraya.com"
                                            v-model="form.email"
                                            :class="{ 'border-red-500': form.errors.email }"
                                        />
                                    </div>
                                    <Message v-if="form.errors.email" severity="error" class="mt-2">
                                        {{ form.errors.email }}
                                    </Message>
                                </div>

                                <!-- Password Field -->
                                <div class="space-y-2">
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Password
                                    </label>
                                    <div class="relative password-wrapper">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                            <i class="pi pi-lock text-gray-400"></i>
                                        </div>
                                        <Password 
                                            id="password"
                                            v-model="form.password" 
                                            placeholder="Enter your password" 
                                            :toggleMask="true"
                                            :feedback="false"
                                            class="w-full"
                                            :inputClass="'w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white transition-colors' + (form.errors.password ? ' border-red-500' : '')"
                                        />
                                    </div>
                                    <Message v-if="form.errors.password" severity="error" class="mt-2">
                                        {{ form.errors.password }}
                                    </Message>
                                </div>

                                <!-- Remember Me & Forgot Password -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <Checkbox 
                                            v-model="form.remember" 
                                            id="remember" 
                                            binary 
                                            class="mr-3"
                                            :class="form.remember ? 'bg-emerald-600 border-emerald-600' : ''"
                                        />
                                        <label for="remember" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                            Remember this device
                                        </label>
                                    </div>
                                    <Link 
                                        href="/forgot-password"
                                        class="text-sm font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                <!-- Submit Button -->
                                <Button 
                                    type="submit" 
                                    label="Access Portal" 
                                    class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 border-0 text-white font-semibold shadow-lg transition-all duration-200"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                >
                                    <template #icon>
                                        <i class="pi pi-sign-in mr-2"></i>
                                    </template>
                                </Button>

                                <!-- Support Info -->
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                    <div class="flex items-start space-x-3">
                                        <i class="pi pi-info-circle text-amber-500 dark:text-amber-400 mt-0.5"></i>
                                        <div>
                                            <p class="text-sm text-amber-700 dark:text-amber-300 font-medium">
                                                First time user?
                                            </p>
                                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                                Contact IT Helpdesk at <strong>digidept@artrustees.com.my</strong> or extension <strong>5099</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="mt-8 text-center space-y-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Amanah Raya Trustees Berhad - Internal Use Only
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    © {{ new Date().getFullYear() }} Amanah Raya Trustees Berhad. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Custom styles for PrimeVue components */
:deep(.email-wrapper) {
    position: relative;
    width: 100%;
}

:deep(.email-wrapper .p-inputtext) {
    width: 100% !important;
    padding-left: 2.5rem !important;
    padding-right: 1rem !important;
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
}

:deep(.email-wrapper .p-inputtext:focus) {
    outline: none;
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    ring: 2px;
    ring-color: #059669;
}

:deep(.password-wrapper) {
    position: relative;
    width: 100%;
}

:deep(.password-wrapper .p-password) {
    width: 100%;
    display: block;
}

:deep(.password-wrapper .p-password-input) {
    width: 100% !important;
    padding-left: 2.5rem !important;
    padding-right: 3rem !important;
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
}

:deep(.password-wrapper .p-icon-wrapper) {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
}

:deep(.p-checkbox .p-checkbox-box) {
    border-radius: 0.375rem;
    border: 2px solid #d1d5db;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background: #059669;
    border-color: #059669;
}

:deep(.p-button) {
    border-radius: 0.75rem;
    transition: all 0.2s ease-in-out;
}

:deep(.p-button:not(:disabled):hover) {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);
}

:deep(.p-card) {
    border-radius: 1.5rem;
    backdrop-filter: blur(16px);
}

:deep(.p-inputtext) {
    border-radius: 0.75rem;
    transition: all 0.2s ease-in-out;
}

:deep(.p-inputtext:focus) {
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

:deep(.p-divider .p-divider-content) {
    background: transparent;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    :deep(.p-card) {
        background: rgba(31, 41, 55, 0.8);
    }
    
    :deep(.p-inputtext) {
        background: #374151;
        border-color: #4b5563;
        color: white;
    }
    
    :deep(.p-inputtext:focus) {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
    }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .text-center.lg\:text-left {
        text-align: center;
    }
    
    .max-w-md.mx-auto.lg\:mx-0 {
        margin-left: auto;
        margin-right: auto;
    }
}

/* Loading state */
:deep(.p-button.p-button-loading) {
    opacity: 0.8;
    cursor: not-allowed;
}
</style>