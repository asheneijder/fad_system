<script setup>
import { ref, onMounted, computed } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataView from "primevue/dataview";
import Select from "primevue/select";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import InputText from "primevue/inputtext";
import Badge from "primevue/badge";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { router, Head } from "@inertiajs/vue3";

const toast = useToast();
const products = ref([]);
const loading = ref(true);
const searchTerm = ref("");
const layout = ref('grid');

// Sample data
onMounted(() => {
    setTimeout(() => {
        products.value = [
            {
                id: 1,
                name: "A4 Paper (500 sheets)",
                category: "Paper Supplies",
                image: "https://placehold.co/300x200?text=A4+Paper",
                price: 15.0,
                inventoryStatus: "INSTOCK",
                count: 0,
                stock: 120,
                unit: "Ream"
            },
            {
                id: 2,
                name: "Ballpoint Pen - Blue",
                category: "Writing Tools",
                image: "https://placehold.co/300x200?text=Blue+Pen",
                price: 1.5,
                inventoryStatus: "INSTOCK",
                count: 0,
                stock: 240,
                unit: "Box"
            },
            {
                id: 3,
                name: "Whiteboard Marker - Red",
                category: "Markers",
                image: "https://placehold.co/300x200?text=Red+Marker",
                price: 3.0,
                inventoryStatus: "LOWSTOCK",
                count: 0,
                stock: 15,
                unit: "Piece"
            },
            {
                id: 4,
                name: "Stapler Heavy Duty",
                category: "Office Equipment",
                image: "https://placehold.co/300x200?text=Stapler",
                price: 25.0,
                inventoryStatus: "INSTOCK",
                count: 0,
                stock: 45,
                unit: "Unit"
            },
            {
                id: 5,
                name: "Correction Tape",
                category: "Writing Tools",
                image: "https://placehold.co/300x200?text=Correction+Tape",
                price: 2.5,
                inventoryStatus: "INSTOCK",
                count: 0,
                stock: 80,
                unit: "Piece"
            },
            {
                id: 6,
                name: "Sticky Notes Pack",
                category: "Paper Supplies",
                image: "https://placehold.co/300x200?text=Sticky+Notes",
                price: 4.0,
                inventoryStatus: "OUTOFSTOCK",
                count: 0,
                stock: 0,
                unit: "Pack"
            }
        ];
        loading.value = false;
    }, 1000);
});

// Sort options
const sortKey = ref();
const sortOrder = ref();
const sortField = ref();
const sortOptions = ref([
    { label: "Price High to Low", value: "!price" },
    { label: "Price Low to High", value: "price" },
    { label: "Name A to Z", value: "name" },
    { label: "Name Z to A", value: "!name" },
]);

const onSortChange = (event) => {
    const value = event.value.value;
    sortKey.value = event.value;
    sortOrder.value = value.startsWith("!") ? -1 : 1;
    sortField.value = value.replace("!", "");
};

// Status styling
const getSeverity = (product) => {
    switch (product.inventoryStatus) {
        case "INSTOCK":
            return "success";
        case "LOWSTOCK":
            return "warn";
        case "OUTOFSTOCK":
            return "danger";
        default:
            return null;
    }
};

const getStockText = (product) => {
    switch (product.inventoryStatus) {
        case "INSTOCK":
            return "In Stock";
        case "LOWSTOCK":
            return "Low Stock";
        case "OUTOFSTOCK":
            return "Out of Stock";
        default:
            return "Unknown";
    }
};

// Quantity Controls
const increaseQty = (item) => {
    if (item.inventoryStatus === 'OUTOFSTOCK') return;
    if (item.count < item.stock) {
        item.count++;
    } else {
        toast.add({
            severity: 'warn',
            summary: 'Stock Limit',
            detail: `Only ${item.stock} units available`,
            life: 3000
        });
    }
};

const decreaseQty = (item) => {
    if (item.count > 0) item.count--;
};

// Add to cart
const addToCart = (item) => {
    if (item.count === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Quantity',
            detail: 'Please select quantity before adding to cart',
            life: 3000
        });
        return;
    }
    
    toast.add({
        severity: 'success',
        summary: 'Added to Cart',
        detail: `${item.count} ${item.unit}(s) of ${item.name} added to cart`,
        life: 3000
    });
    
    // Reset count after adding to cart
    item.count = 0;
};

// Filtered Products
const filteredProducts = computed(() => {
    if (!searchTerm.value) return products.value;
    return products.value.filter((item) =>
        item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        item.category.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

// Total cart items
const totalCartItems = computed(() => {
    return products.value.reduce((total, item) => total + item.count, 0);
});
</script>

<template>
    <Head title="Browse Items" />
    <AppLayout>
        <Toast />
        
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                        Office Supply Catalog
                    </h1>
                    <p class="text-gray-600 text-lg">Browse and add items to your request cart</p>
                </div>

                <!-- Filters and Cart Button -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                    <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                            <InputText 
                                v-model="searchTerm" 
                                placeholder="Search items..." 
                                class="w-full sm:w-80 h-12 rounded-xl border border-gray-200 px-4"
                            />
                            <Select 
                                v-model="sortKey" 
                                :options="sortOptions" 
                                optionLabel="label" 
                                placeholder="Sort by..."
                                @change="onSortChange" 
                                class="w-full sm:w-48 h-12"
                            />
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <Button 
                                icon="pi pi-shopping-cart" 
                                label="View Cart"
                                class="relative bg-gradient-to-r from-blue-500 to-indigo-600 border-0 px-6 py-3 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300"
                                @click="router.get(route('user.cart.index'))"
                            >
                                <Badge v-if="totalCartItems > 0" 
                                    :value="totalCartItems" 
                                    severity="danger" 
                                    class="absolute -top-2 -right-2 min-w-[1.5rem] h-6 flex items-center justify-center text-xs">
                                </Badge>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Skeleton Loader -->
                <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="n in 6" :key="n" class="bg-white rounded-2xl shadow-lg p-6">
                        <Skeleton shape="rectangle" class="w-full h-48 mb-4 rounded-xl" />
                        <Skeleton width="70%" height="1.5rem" class="mb-2" />
                        <Skeleton width="50%" height="1rem" class="mb-4" />
                        <Skeleton width="30%" height="2rem" />
                    </div>
                </div>

                <!-- Product Grid -->
                <div v-else>
                    <DataView 
                        :value="filteredProducts" 
                        :sortOrder="sortOrder" 
                        :sortField="sortField" 
                        layout="grid"
                        paginator 
                        :rows="12"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                        class="custom-dataview"
                    >
                        <template #grid="slotProps">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="(item, index) in slotProps.items" :key="index" 
                                    class="group bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                                    
                                    <!-- Product Image -->
                                    <div class="relative h-48 bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">
                                        <img :src="item.image" :alt="item.name" 
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                        
                                        <!-- Stock Status Badge -->
                                        <div class="absolute top-4 right-4">
                                            <Tag :value="getStockText(item)" :severity="getSeverity(item)" 
                                                class="px-3 py-1 rounded-full text-xs font-medium shadow-sm" />
                                        </div>
                                        
                                        <!-- Category Badge -->
                                        <div class="absolute bottom-4 left-4">
                                            <span class="bg-white/90 text-gray-700 px-3 py-1 rounded-full text-xs font-medium shadow-sm">
                                                {{ item.category }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="p-6">
                                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">
                                            {{ item.name }}
                                        </h3>
                                        
                                        <div class="flex items-center justify-center mb-4">
                                            <span class="text-sm text-gray-500 bg-gray-50 px-4 py-2 rounded-full border border-gray-200">
                                                <i class="pi pi-box mr-1"></i>
                                                {{ item.stock }} {{ item.unit }}(s) available
                                            </span>
                                        </div>

                                        <!-- Quantity Controls -->
                                        <div v-if="item.inventoryStatus !== 'OUTOFSTOCK'" class="space-y-4">
                                            <div class="flex items-center justify-center space-x-4">
                                                <Button 
                                                    icon="pi pi-minus" 
                                                    class="w-10 h-10 rounded-xl border-2 border-gray-200 bg-white text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-all duration-300"
                                                    @click="decreaseQty(item)" 
                                                    :disabled="item.count === 0" 
                                                />
                                                <span class="w-12 text-center text-xl font-bold text-gray-800">
                                                    {{ item.count }}
                                                </span>
                                                <Button 
                                                    icon="pi pi-plus" 
                                                    class="w-10 h-10 rounded-xl border-2 border-gray-200 bg-white text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-all duration-300"
                                                    @click="increaseQty(item)" 
                                                    :disabled="item.count >= item.stock"
                                                />
                                            </div>
                                            
                                            <Button 
                                                label="Add to Cart" 
                                                icon="pi pi-shopping-cart"
                                                class="w-full h-12 bg-gradient-to-r from-green-500 to-emerald-600 border-0 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300"
                                                @click="addToCart(item)"
                                                :disabled="item.count === 0"
                                            />
                                        </div>
                                        
                                        <!-- Out of Stock -->
                                        <div v-else class="text-center py-4">
                                            <Button 
                                                label="Out of Stock" 
                                                icon="pi pi-times-circle"
                                                class="w-full h-12 bg-gray-300 border-0 text-gray-500 font-semibold rounded-xl cursor-not-allowed"
                                                disabled
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Empty State -->
                        <template #empty>
                            <div class="text-center py-16">
                                <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center">
                                    <i class="pi pi-search text-5xl text-gray-400"></i>
                                </div>
                                <h3 class="text-2xl font-semibold text-gray-600 mb-4">No items found</h3>
                                <p class="text-gray-500 mb-6">Try adjusting your search terms or filters</p>
                                <Button 
                                    label="Clear Filters" 
                                    icon="pi pi-refresh"
                                    class="bg-gradient-to-r from-blue-500 to-indigo-600 border-0 px-6 py-3 text-white font-semibold rounded-xl"
                                    @click="searchTerm = ''; sortKey = null;"
                                />
                            </div>
                        </template>
                    </DataView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom DataView styling */
:deep(.custom-dataview .p-paginator) {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-top: 2rem;
}

:deep(.custom-dataview .p-paginator .p-paginator-pages .p-paginator-page) {
    border-radius: 0.5rem;
    margin: 0 0.25rem;
    min-width: 2.5rem;
    height: 2.5rem;
}

:deep(.custom-dataview .p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border-color: transparent;
}

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

.group {
    animation: fadeInUp 0.6s ease-out;
}

/* Smooth transitions */
* {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom focus styles */
:deep(.p-inputtext:focus) {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

/* Custom button hover effects */
.p-button:hover:not(:disabled) {
    transform: translateY(-1px);
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

/* Badge positioning */
.relative .p-badge {
    position: absolute;
    top: -8px;
    right: -8px;
}
</style>