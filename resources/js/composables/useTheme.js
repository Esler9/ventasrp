import { ref } from 'vue';

const theme = ref('dark');

const applyTheme = (value) => {
    const normalized = value === 'light' ? 'light' : 'dark';
    theme.value = normalized;
    if (typeof document !== 'undefined') {
        document.documentElement.dataset.theme = normalized;
    }
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem('theme', normalized);
    }
};

const initTheme = () => {
    if (typeof window === 'undefined') return;
    const saved = localStorage.getItem('theme');
    const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
    applyTheme(saved || (prefersLight ? 'light' : 'dark'));
};

initTheme();

export const useTheme = () => ({
    theme,
    setTheme: applyTheme,
    toggleTheme: () => applyTheme(theme.value === 'dark' ? 'light' : 'dark'),
});
