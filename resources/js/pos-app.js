import './bootstrap';
import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { startGlobalLoader, stopGlobalLoader } from './composables/useGlobalLoader';

const applyBranding = (props) => {
    const settings = props?.app?.settings || {};
    const root = document.documentElement;

    if (settings.primary_color) {
        root.style.setProperty('--brand-primary', settings.primary_color);
    }

    if (settings.secondary_color) {
        root.style.setProperty('--brand-secondary', settings.secondary_color);
    }
};

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        applyBranding(props.initialPage?.props);
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});

router.on('start', () => startGlobalLoader());
router.on('finish', () => stopGlobalLoader());
router.on('error', () => stopGlobalLoader());
router.on('success', (event) => {
    applyBranding(event?.detail?.page?.props);
});
