import Home from '../pages/Home.vue';
import Login from '../pages/Auth/Login.vue';
import ProjectsIndex from '../pages/Projects/Index.vue';

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
    {
        path: '/projects',
        name: 'projects.index',
        component: ProjectsIndex,
        meta: { title: 'Mes Projets', requiresAuth: true }
    },
    // Routes à ajouter:
    // - /projects/:id (requiresAuth: true)
    // - /backlinks (requiresAuth: true)
    // etc.
];

export default routes;
