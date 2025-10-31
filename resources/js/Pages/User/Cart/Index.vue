<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Badge from 'primevue/badge';
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Checkbox from "primevue/checkbox";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const confirm = useConfirm();
const toast = useToast();

// Mock data for stationary items in cart
// In a real application, this would come from the backend
const cartItems = ref([
    {
        id: 1,
        description: "A4 Paper 80gsm",
        unit: "Ream",
        unit_cost: 12.50,
        quantity: 2,
        image: "https://placehold.co/300x200?text=A4+Paper",
        category: "Office Supplies",
        stock_available: 120
    },
    {
        id: 2,
        description: "Ballpoint Pen Blue",
        unit: "Box",
        unit_cost: 5.75,
        quantity: 3,
        image: "https://placehold.co/300x200?text=Blue+Pen",
        category: "Office Supplies",
        stock_available: 240
    },
    {
        id: 3,
        description: "Projector HDMI",
        unit: "Unit",
        unit_cost: 1200.00,
        quantity: 1,
        image: "https://placehold.co/300x200?text=Projector",
        category: "Electronics",
        stock_available: 5
    }
]);

// Calculate total items
const totalItems = computed(() => {
    return cartItems.value.reduce((total, item) => {
        return total + item.quantity;
    }, 0);
});

// Update item quantity
const updateQuantity = (item, newQuantity) => {
    if (newQuantity <= 0) {
        confirm.require({
            message: 'Do you want to remove this item from your cart?',
            header: 'Remove Confirmation',
            icon: 'pi pi-exclamation-triangle',
            acceptClass: 'p-button-danger',
            accept: () => {
                removeItem(item);
            },
            reject: () => {
                item.quantity = 1;
            }
        });
    } else if (newQuantity > item.stock_available) {
        toast.add({
            severity: 'warn',
            summary: 'Quantity Limit',
            detail: `Only ${item.stock_available} units available in stock`,
            life: 3000
        });
        item.quantity = item.stock_available;
    } else {
        item.quantity = newQuantity;
    }
};

// Remove item from cart
const removeItem = (item) => {
    const index = cartItems.value.findIndex(i => i.id === item.id);
    if (index !== -1) {
        cartItems.value.splice(index, 1);
        toast.add({
            severity: 'success',
            summary: 'Item Removed',
            detail: 'Item has been removed from your cart',
            life: 3000
        });
    }
};

// Request form data
const requestForm = ref({
    reason: "",
    acknowledge: false,
    request_date: new Date().toISOString().split("T")[0] // Today's date
});

// Form validation errors
const errors = ref({});

// Submit request function
const submitRequest = () => {
    if (cartItems.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Empty Cart',
            detail: 'Your cart is empty. Please add items before submitting a request.',
            life: 3000
        });
        return;
    }
    
    if (!requestForm.value.acknowledge) {
        toast.add({
            severity: 'error',
            summary: 'Agreement Required',
            detail: 'You must read and agree to the terms before submitting.',
            life: 3000
        });
        errors.value.acknowledge = 'You must agree to the terms and conditions';
        return;
    }
    
    if (!requestForm.value.reason.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Reason Required',
            detail: 'Please provide a reason for your request.',
            life: 3000
        });
        errors.value.reason = 'Please provide a reason for your request';
        return;
    }
    
    // In a real application, this would send the request data to the backend
    toast.add({
        severity: 'info',
        summary: 'Processing',
        detail: 'Processing your request...',
        life: 3000
    });
    
    // Simulate API call
    setTimeout(() => {
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Your request has been submitted to admin successfully!',
            life: 3000
        });
        cartItems.value = [];
        requestForm.value = {
            reason: "",
            acknowledge: false,
            request_date: new Date().toISOString().split("T")[0]
        };
        errors.value = {};
    }, 2000);
};

// Breadcrumb items
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = [{ label: 'User' }, { label: 'My Cart' }];
</script>

<template>
    <Head title="Request Items" />
    <AppLayout>
        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 p-4 md:p-8">
            <!-- Breadcrumb navigation -->
            <!-- <div class="mb-6">
                <Breadcrumb :model="breadcrumbItems" :home="home" 
                    class="bg-white/80 backdrop-blur-sm rounded-xl px-4 py-2 shadow-sm border border-white/20" />
            </div> -->

            <!-- Main Container -->
            <div class="max-w-6xl mx-auto">
                <!-- Header Section -->
                <div class="text-center mb-8">
                    <div class="relative inline-block">
                        <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                            My Request Cart
                        </h1>
                        <Badge v-if="totalItems > 0" 
                            :value="totalItems" 
                            severity="info" 
                            class="absolute -top-2 -right-8 text-sm px-3 py-1 animate-pulse">
                        </Badge>
                    </div>
                    <p class="text-gray-600 text-lg">Review and submit your office supply requests</p>
                </div>

                <!-- Empty cart state -->
                <div v-if="cartItems.length === 0" 
                    class="bg-white rounded-3xl shadow-xl border border-gray-100 p-12 text-center">
                    <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
                        <i class="pi pi-shopping-cart text-5xl text-blue-500"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Your cart is empty</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">
                        Start building your request by browsing our available office supplies and equipment
                    </p>
                    <Button 
                        label="Browse Items" 
                        icon="pi pi-search" 
                        class="p-button-lg bg-gradient-to-r from-blue-500 to-indigo-600 border-0 px-8 py-3 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300"
                        @click="router.get(route('user.request-items.index'))" />
                </div>

                <!-- Cart items -->
                <div v-else class="space-y-6">
                    <!-- Items Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <div v-for="item in cartItems" :key="item.id" 
                            class="group bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                            
                            <!-- Item Image -->
                            <div class="relative h-48 bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">
                                <img :src="item.image" :alt="item.description" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <div class="absolute top-4 right-4">
                                    <Badge :value="item.category" 
                                        class="bg-white/90 text-gray-700 px-3 py-1 rounded-full text-xs font-medium shadow-sm" />
                                </div>
                                <div class="absolute bottom-4 left-4">
                                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                                        {{ item.unit }}
                                    </span>
                                </div>
                            </div>

                            <!-- Item Details -->
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">
                                    {{ item.description }}
                                </h3>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded-full">
                                        <i class="pi pi-box mr-1"></i>
                                        {{ item.stock_available }} available
                                    </span>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <label class="text-sm font-medium text-gray-600">Qty:</label>
                                        <div class="flex items-center bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                                            <button @click="updateQuantity(item, item.quantity - 1)"
                                                class="px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition-colors">
                                                <i class="pi pi-minus text-sm"></i>
                                            </button>
                                            <span class="px-4 py-2 font-semibold text-gray-800 min-w-[3rem] text-center">
                                                {{ item.quantity }}
                                            </span>
                                            <button @click="updateQuantity(item, item.quantity + 1)"
                                                class="px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition-colors">
                                                <i class="pi pi-plus text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <Button 
                                        icon="pi pi-trash" 
                                        severity="danger" 
                                        class="p-2 w-10 h-10 rounded-xl border-0 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-300"
                                        @click="removeItem(item)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Form -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                        <!-- Form Header -->
                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <i class="pi pi-file-edit mr-3 text-3xl"></i>
                                Request Details
                            </h2>
                        </div>

                        <div class="p-8 space-y-6">
                            <!-- Request Date -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700">Request Date</label>
                                <InputText 
                                    v-model="requestForm.request_date" 
                                    class="w-full h-12 rounded-xl border border-gray-200 bg-gray-50 px-4 font-medium text-gray-600" 
                                    disabled />
                            </div>
                            
                            <!-- Reason for Request -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700">Reason for Request *</label>
                                <Textarea 
                                    v-model="requestForm.reason" 
                                    rows="4" 
                                    class="w-full rounded-xl border border-gray-200 p-4 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-300" 
                                    placeholder="Please explain why you need these items..."
                                    :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': errors.reason}"
                                />
                                <small v-if="errors.reason" class="text-red-500 font-medium">{{ errors.reason }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                        <!-- Terms Header -->
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-6">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <i class="pi pi-shield mr-3 text-3xl"></i>
                                Terms & Conditions
                            </h2>
                        </div>

                        <div class="p-8">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-6 border border-blue-100">
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                                    <i class="pi pi-info-circle mr-2 text-blue-500"></i>
                                    Agreement Requirements
                                </h3>
                                <div class="space-y-3 text-sm text-gray-700">
                                    <div class="flex items-start space-x-3">
                                        <span class="text-blue-500 font-bold">1.</span>
                                        <span>Information provided must be accurate and necessary for office use</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="text-blue-500 font-bold">2.</span>
                                        <span>False information may result in request denial</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="text-blue-500 font-bold">3.</span>
                                        <span>Approval is subject to company policies and stock availability</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="text-blue-500 font-bold">4.</span>
                                        <span>You are responsible for assigned items</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl p-6 mb-6 border border-amber-100">
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                                    <i class="pi pi-exclamation-triangle mr-2 text-amber-500"></i>
                                    Office Guidelines
                                </h3>
                                <div class="space-y-2 text-sm text-gray-700">
                                    <div class="flex items-center space-x-2">
                                        <i class="pi pi-check text-green-500 text-xs"></i>
                                        <span>Electronics require department head approval</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="pi pi-check text-green-500 text-xs"></i>
                                        <span>Office supplies subject to stock availability</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="pi pi-check text-green-500 text-xs"></i>
                                        <span>Items must be used responsibly for official purposes</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="pi pi-check text-green-500 text-xs"></i>
                                        <span>Return items when no longer needed</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Acknowledgment Checkbox -->
                            <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <Checkbox 
                                    v-model="requestForm.acknowledge" 
                                    :class="{'border-red-300': errors.acknowledge}"
                                    class="mt-1" 
                                />
                                <div class="flex-1">
                                    <label class="text-sm font-semibold text-gray-800 cursor-pointer">
                                        I have read and agree to all terms and conditions listed above
                                    </label>
                                    <small v-if="errors.acknowledge" class="block text-red-500 font-medium mt-1">
                                        {{ errors.acknowledge }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row justify-center gap-4 pt-6">
                        <Button 
                            label="Continue Shopping" 
                            icon="pi pi-arrow-left" 
                            class="p-button-lg border-2 border-gray-300 bg-white text-gray-700 px-8 py-3 rounded-xl font-semibold hover:border-gray-400 hover:shadow-md transition-all duration-300"
                            @click="router.get(route('user.request-items.index'))" />
                        
                        <Button 
                            label="Submit Request" 
                            icon="pi pi-send" 
                            class="p-button-lg bg-gradient-to-r from-green-500 to-emerald-600 border-0 px-8 py-3 text-white font-semibold rounded-xl hover:shadow-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-300"
                            @click="submitRequest()" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Custom gradient backgrounds */
.bg-gradient-to-br {
    background: linear-gradient(135deg, var(--tw-gradient-stops));
}

/* Smooth transitions for all interactive elements */
* {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom focus styles */
.p-inputtext:focus,
.p-inputtextarea:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

/* Custom button hover effects */
.p-button:hover {
    transform: translateY(-1px);
}

/* Custom card hover effects */
.group:hover {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

/* Backdrop blur support */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #3b82f6, #1d4ed8);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #1d4ed8, #1e40af);
}
</style>