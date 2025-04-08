import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import InputText from 'primevue/inputtext'; 
import StyleClass from 'primevue/styleclass';
import Tooltip from 'primevue/tooltip';

import '@/sakai/assets/styles.scss';
import '@/sakai/assets/tailwind.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} | ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        prefix: 'p',
                        darkModeSelector: 'none',
                        cssLayer: false
                    }
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .directive('tooltip', Tooltip)
            .mixin({
                methods: {
                    can: function (permissions) {
                        var allPermissions = this.$page.props.auth.can;
                        var hasPermission = false;
                        // permissions.forEach(function (item) {
                        //     if (allPermissions[item]) hasPermission = true;
                        // });
                        return hasPermission;
                    },
                },
            })
            .directive('styleclass', StyleClass)
            .component('InputText', InputText)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
