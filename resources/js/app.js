import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'SmileWorks Dental OS';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                },
                ripple: true,
            })
            .use(ToastService)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#0f766e',
    },
});
