import Home from '../pages/Home.vue';
import Login from '../pages/Auth/Login.vue';
import ProjectsIndex from '../pages/Projects/Index.vue';
import ProjectsShow from '../pages/Projects/Show.vue';
import ProjectsCreate from '../pages/Projects/Create.vue';
import ProjectsEdit from '../pages/Projects/Edit.vue';
import BacklinksIndex from '../pages/Backlinks/Index.vue';
import BacklinksCreate from '../pages/Backlinks/Create.vue';
import BacklinksEdit from '../pages/Backlinks/Edit.vue';
import BacklinksShow from '../pages/Backlinks/Show.vue';

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
    {
        path: '/projects/:projectId/backlinks',
        name: 'backlinks.index',
        component: BacklinksIndex,
        meta: { title: 'Backlinks du projet', requiresAuth: true }
    },
    {
        path: '/projects/:projectId/backlinks/create',
        name: 'backlinks.create',
        component: BacklinksCreate,
        meta: { title: 'Ajouter un backlink', requiresAuth: true }
    },
    {
        path: '/projects/:projectId/backlinks/:id',
        name: 'backlinks.show',
        component: BacklinksShow,
        meta: { title: 'Détails du backlink', requiresAuth: true }
    },
    {
        path: '/projects/:projectId/backlinks/:id/edit',
        name: 'backlinks.edit',
        component: BacklinksEdit,
        meta: { title: 'Modifier le backlink', requiresAuth: true }
    },
];

export default routes;
