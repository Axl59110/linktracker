<script setup>
import { onMounted } from 'vue';
import { useProjectsStore } from '@/stores/projects';
import { useRouter } from 'vue-router';

const store = useProjectsStore();
const router = useRouter();

onMounted(() => {
    store.fetchProjects();
});

const getStatusColor = (status) => {
    return {
        active: 'bg-green-100 text-green-800',
        paused: 'bg-yellow-100 text-yellow-800',
        archived: 'bg-gray-100 text-gray-800',
    }[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Mes Projets</h1>
            <button
                @click="router.push('/projects/create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
            >
                + Créer un projet
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="store.loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Chargement des projets...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="store.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ store.error }}
        </div>

        <!-- Empty State -->
        <div v-else-if="!store.hasProjects" class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun projet</h3>
            <p class="mt-1 text-gray-500">Commencez par créer votre premier projet</p>
            <button
                @click="router.push('/projects/create')"
                class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
            >
                Créer un projet
            </button>
        </div>

        <!-- Projects List -->
        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="project in store.projects"
                :key="project.id"
                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition"
            >
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-xl font-semibold text-gray-900">{{ project.name }}</h3>
                    <span
                        :class="getStatusColor(project.status)"
                        class="px-2 py-1 text-xs font-medium rounded"
                    >
                        {{ project.status }}
                    </span>
                </div>

                <p class="text-gray-600 text-sm mb-4 truncate">{{ project.url }}</p>

                <div class="text-xs text-gray-500 mb-4">
                    Créé le {{ formatDate(project.created_at) }}
                </div>

                <button
                    @click="router.push(`/projects/${project.id}`)"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded transition"
                >
                    Voir le projet
                </button>
            </div>
        </div>
    </div>
</template>
