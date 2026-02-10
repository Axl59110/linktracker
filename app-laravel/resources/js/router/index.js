import Home from '../pages/Home.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
        meta: { title: 'Link Tracker - Accueil' }
    },
    // Routes à ajouter:
    // - /login
    // - /projects
    // - /projects/:id
    // - /backlinks
    // etc.
];

export default routes;
