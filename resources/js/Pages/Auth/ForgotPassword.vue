<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Divider from 'primevue/divider';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />
    
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-emerald-900 flex items-center justify-center p-4">
        <!-- Background Decorations -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute top-40 left-40 w-80 h-80 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>

        <div class="relative w-full max-w-md">
            <Card class="shadow-2xl border-0 backdrop-blur-sm bg-white/80 dark:bg-gray-800/80">
                <template #content>
                    <div class="p-6 sm:p-8">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg mx-auto mb-4">
                                <i class="pi pi-key text-white text-2xl"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                Reset Password
                            </h2>
                            <p class="text-gray-600 dark:text-gray-300">
                                Amanah Raya Trustees Berhad Staff Portal
                            </p>
                        </div>

                        <!-- Success Message -->
                        <Message 
                            v-if="status" 
                            severity="success" 
                            class="mb-6 animate-fade-in"
                        >
                            <div class="flex items-center">
                                <i class="pi pi-check-circle mr-2"></i>
                                <span>{{ status }}</span>
                            </div>
                        </Message>

                        <!-- Information Text -->
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-6">
                            <div class="flex items-start space-x-3">
                                <i class="pi pi-info-circle text-emerald-500 dark:text-emerald-400 mt-0.5"></i>
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                    Enter your corporate email address and we'll send you a password reset link to access the stationary management system.
                                </p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Email Field -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Corporate Email
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="pi pi-envelope text-gray-400"></i>
                                    </div>
                                    <InputText 
                                        id="email" 
                                        type="email" 
                                        placeholder="your.name@amanahraya.com"
                                        class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white transition-colors"
                                        :class="{ 'border-red-500': form.errors.email }"
                                        v-model="form.email"
                                        required
                                        autofocus
                                        autocomplete="email"
                                        :disabled="form.processing"
                                    />
                                </div>
                                <Message 
                                    v-if="form.errors.email" 
                                    severity="error" 
                                    class="mt-2 animate-fade-in"
                                >
                                    <div class="flex items-center">
                                        <i class="pi pi-exclamation-triangle mr-2"></i>
                                        <span>{{ form.errors.email }}</span>
                                    </div>
                                </Message>
                            </div>

                            <!-- Submit Button -->
                            <Button 
                                type="submit" 
                                label="Send Reset Link" 
                                class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 border-0 text-white font-semibold shadow-lg transition-all duration-200"
                                :loading="form.processing"
                                :disabled="form.processing"
                            >
                                <template #icon>
                                    <i class="pi pi-send mr-2"></i>
                                </template>
                            </Button>

                            <!-- Back to Login -->
                            <div class="text-center">
                                <a 
                                    :href="route('login')" 
                                    class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors group"
                                >
                                    <i class="pi pi-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                                    Back to Staff Portal
                                </a>
                            </div>
                        </form>

                        <!-- Help Text -->
                        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <div class="flex items-start space-x-3">
                                <i class="pi pi-headphones text-amber-500 dark:text-amber-400 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-1">
                                        Need immediate assistance?
                                    </p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400">
                                        Contact IT Helpdesk: <strong>digidept@artrustees.com.my</strong> or extension <strong>5099</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-6 text-center space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Amanah Raya Trustees Berhad - Internal System
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

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Custom styles for PrimeVue components */
:deep(.p-button) {
    border-radius: 0.75rem;
    transition: all 0.2s ease-in-out;
}

:deep(.p-button:not(:disabled):hover) {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.4);
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

:deep(.p-message) {
    border-radius: 0.75rem;
    border: none;
}

:deep(.p-message .p-message-content) {
    padding: 0.75rem 1rem;
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

/* Loading state */
:deep(.p-button.p-button-loading) {
    opacity: 0.8;
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    :deep(.p-card .p-card-content) {
        padding: 1.5rem;
    }
}

/* Link hover effects */
a {
    transition: all 0.2s ease-in-out;
}

a:hover {
    text-decoration: none;
}
</style>