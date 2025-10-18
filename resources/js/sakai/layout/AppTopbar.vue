<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import NavLink from "@/Components/NavLink.vue";
import DropdownLink from "@/Components/DropdownLink.vue";


const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
                <i class="pi pi-bars"></i>
            </button>
            <nav-link href="/dashboard" class="layout-topbar-logo">
                <!-- <img src="/images/icon-system.jpg" alt="logo" /> -->
                <span>FAD Inventory System</span>
            </nav-link>
        </div>

        <div class="layout-topbar-actions">

            <button class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }">
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="hidden layout-topbar-menu lg:block">
                <div class="layout-topbar-menu-content">
                    <div class="relative">
                        <button type="button" class="layout-topbar-action"
                            v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }">
                            <i class="pi pi-user"></i>
                            <span>Profile</span>
                        </button>
                        <div class="absolute right-0 hidden w-48 py-2 mt-2 bg-white rounded-md shadow-md">
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
