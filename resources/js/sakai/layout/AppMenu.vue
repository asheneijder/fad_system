<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', icon: 'pi pi-home', to: '/dashboard' }
        ]
    },
    {
        label: 'Inventory',
        items: [
            { label: 'Assets', icon: 'pi pi-desktop', to: '/admin/assets' },
            { label: 'Models', icon: 'pi pi-table', to: '/admin/models' },
            { label: 'Categories', icon: 'pi pi-tags', to: '/admin/categories' },
            { label: 'Licenses', icon: 'pi pi-id-card', to: '/admin/licenses' },
            { label: 'Stationery Stock', icon: 'pi pi-briefcase', to: '/admin/stationary-items' }
        ]
    },
    {
        label: 'Requests',
        items: [
            { label: 'Manage Requests', icon: 'pi pi-folder-open', to: '/admin/manage-request-items' }, 
            { label: 'Available Items', icon: 'pi pi-list', to: '/user/request-items' },
        ]
    },
    {
        label: 'Administration',
        items: [
            { label: 'Users', icon: 'pi pi-users', to: '/admin/users' },
            { label: 'Audit Logs', icon: 'pi pi-clock', to: '/admin/audit-logs' },
            { label: 'Reports', icon: 'pi pi-chart-line', to: '/admin/reports' }
        ]
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        items: [
            { label: 'Profile', icon: 'pi pi-user', to: '/profile' },
        ]
    }
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
