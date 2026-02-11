import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';
import '../css/app.css';

// Import components
import App from './App.vue';

// Import routes
import routes from './router/index.js';

// Import auth store
import { useAuthStore } from './stores/auth';

// Create Pinia store
const pinia = createPinia();

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Create and mount Vue app
const app = createApp(App);
app.use(pinia);
app.use(router);

// Navigation guard pour vérifier l'authentification
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Récupérer l'utilisateur si on ne l'a pas encore
    if (!authStore.user && !authStore.loading) {
        await authStore.fetchUser();
    }

    // Vérifier si la route nécessite l'authentification
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        // Rediriger vers login si non authentifié
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.name === 'login' && authStore.isAuthenticated) {
        // Si déjà connecté et tente d'aller sur login, rediriger vers home
        next({ name: 'home' });
    } else {
        next();
    }
});

app.mount('#app');
