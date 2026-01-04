import { reactive } from 'vue';

const state = reactive({
    active: false,
});

let timer = null;

export const useGlobalLoaderState = () => state;

export const startGlobalLoader = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        state.active = true;
    }, 120);
};

export const stopGlobalLoader = () => {
    clearTimeout(timer);
    timer = null;
    state.active = false;
};
