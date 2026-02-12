import Home from '../pages/Home.vue';
import Login from '../pages/Auth/Login.vue';
import ProjectsIndex from '../pages/Projects/Index.vue';
import ProjectsShow from '../pages/Projects/Show.vue';
import ProjectsCreate from '../pages/Projects/Create.vue';
import ProjectsEdit from '../pages/Projects/Edit.vue';

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
    {
        path: '/projects/create',
        name: 'projects.create',
        component: ProjectsCreate,
        meta: { title: 'Créer un projet', requiresAuth: true }
    },
    {
        path: '/projects/:id',
        name: 'projects.show',
        component: ProjectsShow,
        meta: { title: 'Détails du projet', requiresAuth: true }
    },
    {
        path: '/projects/:id/edit',
        name: 'projects.edit',
        component: ProjectsEdit,
        meta: { title: 'Modifier le projet', requiresAuth: true }
    },
    // Routes à ajouter:
    // - /backlinks (requiresAuth: true)
    // etc.
];

export default routes;
