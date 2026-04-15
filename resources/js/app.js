import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { createPinia } from "pinia";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from 'primevue/config';
import { useUserStore } from "@/stores/userData"; // ✅ added
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

import ToastPlugin from "vue-toast-notification";
import "vue-toast-notification/dist/theme-sugar.css";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () => h(App, props),
        });

        // ✅ Initialize Pinia first
        const pinia = createPinia();

        pinia.use(piniaPluginPersistedstate)

        vueApp.use(pinia);
        vueApp.use(plugin);
        vueApp.use(ZiggyVue);
        vueApp.use(ToastPlugin);
        vueApp.use(PrimeVue, {
            unstyled: true
        });

        // ✅ Fetch user once on boot — never refetches until page reload
        const userStore = useUserStore(pinia);
        userStore.fetchUser();

        vueApp.mount(el);
    },

    progress: {
        color: "#4B5563",
    },
});
