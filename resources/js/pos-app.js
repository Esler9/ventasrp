import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { startGlobalLoader, stopGlobalLoader } from './composables/useGlobalLoader';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});

router.on('start', () => startGlobalLoader());
router.on('finish', () => stopGlobalLoader());
router.on('error', () => stopGlobalLoader());
