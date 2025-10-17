<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppMenuItem from './AppMenuItem.vue';

const page = usePage();

const hasPermission = (permission) => {
    const auth = page.props.auth;
    const allPermissions = auth?.permissions || {};
    const superAdmin = auth?.super || false;

    if (superAdmin) return true;
    return !!allPermissions[permission];
};

const baseMenu = ref([
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', icon: 'pi pi-home', to: '/dashboard' }
        ]
    },
    {
        label: 'Inventory',
        items: [
            { label: 'Assets', icon: 'pi pi-desktop', to: '/admin/assets', permission: 'can.view.assets' },
            { label: 'Models', icon: 'pi pi-table', to: '/admin/models', permission: 'can.view.models' },
            { label: 'Categories', icon: 'pi pi-tags', to: '/admin/categories', permission: 'can.view.categories' },
            { label: 'Licenses', icon: 'pi pi-id-card', to: '/admin/licenses', permission: 'can.view.licenses' },
            { label: 'Stationery Stock', icon: 'pi pi-briefcase', to: '/admin/stationary-items', permission: 'can.view.stationary-items' }
        ]
    },
    {
        label: 'Requests',
        items: [
            { 
                label: 'Manage Requests', 
                icon: 'pi pi-folder-open', 
                to: '/admin/manage-request-items',
                permission: 'can.manage.request' 
            },
            { 
                label: 'Request Items', 
                icon: 'pi pi-send',
                to: '/user/request-items',
                permission: 'can.view.request'
            },
        ]
    },
    {
        label: 'Administration',
        items: [
            { 
                label: 'Users', 
                icon: 'pi pi-users', 
                to: '/admin/users',
                permission: 'can.view.users'
            },
            { 
                label: 'Permissions', 
                icon: 'pi pi-key', 
                to: '/admin/permissions',
                permission: 'can.manage.permissions'
            },
            { 
                label: 'Audit Logs', 
                icon: 'pi pi-clock', 
                to: '/admin/audit-logs',
                permission: 'can.view.audit'
            },
            { 
                label: 'Reports', 
                icon: 'pi pi-chart-line', 
                to: '/admin/reports',
                permission: 'can.view.reports'
            }
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

// Filter menu items based on permissions
const model = computed(() => {
    return baseMenu.value.map(section => ({
        ...section,
        items: section.items?.filter(item => {
            // If no permission required, show item
            if (!item.permission) return true;
            
            // Check if user has the required permission
            return hasPermission(item.permission);
        }) || []
    })).filter(section => {
        // Remove empty sections
        return section.items.length > 0;
    });
});
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