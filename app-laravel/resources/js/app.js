import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import '../css/app.css';

// Import components
import App from './App.vue';

// Import routes
import routes from './router/index.js';

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Create and mount Vue app
const app = createApp(App);
app.use(router);
app.mount('#app');
