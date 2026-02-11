import Home from '../pages/Home.vue';
import Login from '../pages/Auth/Login.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
        meta: { title: 'Link Tracker - Accueil', requiresAuth: false }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { title: 'Link Tracker - Connexion', requiresAuth: false }
    },
    // Routes à ajouter:
    // - /projects (requiresAuth: true)
    // - /projects/:id (requiresAuth: true)
    // - /backlinks (requiresAuth: true)
    // etc.
];

export default routes;
