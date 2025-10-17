import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export default function usePermissions() {
    const page = usePage();
    
    const hasAnyPermission = (permissions) => {
        const auth = page.props.auth;
        const allPermissions = auth?.permissions || {};
        const superAdmin = auth?.super || false;

        // If super admin, return true
        if (superAdmin) return true;

        // Check if any of the requested permissions exist
        return permissions.some(permission => allPermissions[permission]);
    };

    const hasPermission = (permission) => {
        const auth = page.props.auth;
        const allPermissions = auth?.permissions || {};
        const superAdmin = auth?.super || false;

        // If super admin, return true
        if (superAdmin) return true;

        // Check if the specific permission exists
        return !!allPermissions[permission];
    };

    const getPermissions = computed(() => {
        return page.props.auth?.permissions || {};
    });

    const isSuperAdmin = computed(() => {
        return page.props.auth?.super || false;
    });

    return {
        hasAnyPermission,
        hasPermission,
        getPermissions,
        isSuperAdmin
    };
}