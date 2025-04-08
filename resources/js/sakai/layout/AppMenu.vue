<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-chart-bar', to: '/dashboard' }]
    },
    {
        items: [{ label: 'Asset', icon: 'pi pi-fw pi-box', to: '/admin/asset/view-asset' }]
    },
    {
        items: [{ label: 'License', icon: 'pi pi-fw pi-id-card', to: '/admin/license/view-license' }]
    },
    {
        items: [{ label: 'Stock Stationary', icon: 'pi pi-fw pi-cog', to: '/admin/stationary-items' }]
    },
    {
        items: [{ label: 'Users', icon: 'pi pi-fw pi-users', to: '/admin/users' }]
    },
    {
        items: [{ label: 'Audit Log', icon: 'pi pi-fw pi-history', to: '/admin/settings/general' }]
    },
    {
        items: [{ label: 'Requestable Items', icon: 'pi pi-fw pi-file', to: '/admin/request/view-request-items' }]
    },
    {
        items: [{ label: 'Report', icon: 'pi pi-fw pi-print', to: '/admin/settings/general' }]
    },
    {
        items: [
            {
                label: 'Settings',
                icon: 'pi pi-fw pi-sliders-h',
                items: [
                    { label: 'Import', icon: 'pi pi-fw pi-download', to: '/admin/settings/import' },
                    { label: 'Profile', icon: 'pi pi-fw pi-user', to: '/admin/settings/profile' },
                    { label: 'Security', icon: 'pi pi-fw pi-lock', to: '/admin/settings/security' }
                ]
            }
        ]
    },
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="i">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
