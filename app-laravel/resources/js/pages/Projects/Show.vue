<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useProjectsStore } from '@/stores/projects';

const router = useRouter();
const route = useRoute();
const store = useProjectsStore();
const project = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        project.value = await store.getProject(route.params.id);
    } catch (error) {
        console.error('Erreur lors du chargement du projet:', error);
    } finally {
        loading.value = false;
    }
});

const getStatusColor = (status) => {
    return {
        active: 'bg-green-100 text-green-800 border-green-200',
        paused: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        archived: 'bg-gray-100 text-gray-800 border-gray-200',
    }[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    return {
        active: 'Actif',
        paused: 'En pause',
        archived: 'Archivé',
    }[status] || status;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Chargement du projet...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="store.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ store.error }}
            <button
                @click="router.push('/projects')"
                class="mt-4 block bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
            >
                Retour à la liste
            </button>
        </div>

        <!-- Project Details -->
        <div v-else-if="project" class="max-w-4xl mx-auto">
            <!-- Header with back button -->
            <div class="mb-6">
                <button
                    @click="router.push('/projects')"
                    class="text-blue-600 hover:text-blue-700 flex items-center gap-2 mb-4"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour à la liste
                </button>

                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ project.name }}</h1>
                        <span
                            :class="getStatusColor(project.status)"
                            class="inline-block px-3 py-1 text-sm font-medium rounded border"
                        >
                            {{ getStatusLabel(project.status) }}
                        </span>
                    </div>
                    <button
                        @click="router.push(`/projects/${project.id}/edit`)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </button>
                </div>
            </div>

            <!-- Main Info Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations du projet</h2>

                    <div class="space-y-4">
                        <!-- URL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL du projet</label>
                            <a
                                :href="project.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-2"
                            >
                                {{ project.url }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <span
                                :class="getStatusColor(project.status)"
                                class="inline-block px-3 py-1 text-sm font-medium rounded border"
                            >
                                {{ getStatusLabel(project.status) }}
                            </span>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Créé le</label>
                                <p class="text-gray-900">{{ formatDate(project.created_at) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dernière modification</label>
                                <p class="text-gray-900">{{ formatDate(project.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backlinks Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Backlinks</h2>
                    <button
                        @click="router.push(`/projects/${project.id}/backlinks`)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium"
                    >
                        Voir tous les backlinks
                    </button>
                </div>
                <p class="text-gray-600 text-sm">
                    Gérez et surveillez les backlinks pointant vers ce projet.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    @click="router.push(`/projects/${project.id}/edit`)"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition font-medium"
                >
                    Modifier le projet
                </button>
                <button
                    @click="router.push('/projects')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-lg transition font-medium"
                >
                    Retour à la liste
                </button>
            </div>
        </div>
    </div>
</template>
