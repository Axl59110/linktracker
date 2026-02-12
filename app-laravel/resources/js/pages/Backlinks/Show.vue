<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBacklinksStore } from '@/stores/backlinks';

const route = useRoute();
const router = useRouter();
const store = useBacklinksStore();

const projectId = route.params.projectId;
const backlinkId = route.params.id;

const backlink = ref(null);
const loading = ref(false);
const error = ref(null);

onMounted(async () => {
    loading.value = true;
    error.value = null;
    try {
        backlink.value = await store.getBacklink(projectId, backlinkId);
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors du chargement du backlink';
        console.error('Error loading backlink:', err);
    } finally {
        loading.value = false;
    }
});

const getStatusColor = (status) => {
    return {
        active: 'bg-green-100 text-green-800 border-green-200',
        lost: 'bg-red-100 text-red-800 border-red-200',
        changed: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    }[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    return {
        active: 'Actif',
        lost: 'Perdu',
        changed: 'Modifié',
    }[status] || status;
};

const getCheckStatusIcon = (backlink) => {
    if (!backlink.latest_check) {
        return { icon: '⏳', color: 'text-gray-400', label: 'Jamais vérifié' };
    }
    if (backlink.latest_check.is_present) {
        return { icon: '✓', color: 'text-green-600', label: 'Présent' };
    }
    return { icon: '✗', color: 'text-red-600', label: 'Non trouvé' };
};

const formatDateTime = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleEdit = () => {
    router.push(`/projects/${projectId}/backlinks/${backlinkId}/edit`);
};

const handleDelete = async () => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce backlink ?')) {
        return;
    }

    loading.value = true;
    try {
        await store.deleteBacklink(projectId, backlinkId);
        router.push(`/projects/${projectId}/backlinks`);
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors de la suppression';
    } finally {
        loading.value = false;
    }
};

const handleBack = () => {
    router.push(`/projects/${projectId}/backlinks`);
};
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Chargement du backlink...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ error }}
            <button @click="handleBack" class="mt-4 text-blue-600 hover:text-blue-800 underline">
                Retour à la liste
            </button>
        </div>

        <!-- Backlink Details -->
        <div v-else-if="backlink" class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Détails du backlink</h1>
                <div class="flex gap-2">
                    <button
                        @click="handleEdit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
                    >
                        Modifier
                    </button>
                    <button
                        @click="handleDelete"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition"
                    >
                        Supprimer
                    </button>
                    <button
                        @click="handleBack"
                        class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition"
                    >
                        Retour
                    </button>
                </div>
            </div>

            <!-- Main Info Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                <div class="space-y-6">
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Statut</label>
                        <span
                            :class="getStatusColor(backlink.status)"
                            class="inline-block px-4 py-2 text-sm font-medium rounded-full border"
                        >
                            {{ getStatusLabel(backlink.status) }}
                        </span>
                    </div>

                    <!-- Source URL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">URL Source</label>
                        <a
                            :href="backlink.source_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-600 hover:text-blue-800 underline break-all"
                        >
                            {{ backlink.source_url }}
                        </a>
                    </div>

                    <!-- Target URL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">URL Cible</label>
                        <a
                            :href="backlink.target_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-600 hover:text-blue-800 underline break-all"
                        >
                            {{ backlink.target_url }}
                        </a>
                    </div>

                    <!-- Anchor Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Texte d'ancre</label>
                        <p class="text-gray-900">{{ backlink.anchor_text || 'N/A' }}</p>
                    </div>

                    <!-- Check Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">État de vérification</label>
                        <div class="flex items-center gap-2">
                            <span
                                :class="getCheckStatusIcon(backlink).color"
                                class="text-2xl font-bold"
                            >
                                {{ getCheckStatusIcon(backlink).icon }}
                            </span>
                            <span class="text-gray-900">{{ getCheckStatusIcon(backlink).label }}</span>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Première détection</label>
                            <p class="text-gray-900">{{ formatDateTime(backlink.first_seen_at) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Dernière vérification</label>
                            <p class="text-gray-900">{{ formatDateTime(backlink.last_checked_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check History Card -->
            <div v-if="backlink.checks && backlink.checks.length > 0" class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Historique des vérifications</h2>

                <div class="space-y-4">
                    <div
                        v-for="check in backlink.checks"
                        :key="check.id"
                        class="border-l-4 pl-4 py-2"
                        :class="check.is_present ? 'border-green-500' : 'border-red-500'"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium" :class="check.is_present ? 'text-green-700' : 'text-red-700'">
                                    {{ check.is_present ? '✓ Backlink trouvé' : '✗ Backlink non trouvé' }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ formatDateTime(check.checked_at) }}
                                </p>
                                <p v-if="check.http_status" class="text-sm text-gray-500 mt-1">
                                    Code HTTP: {{ check.http_status }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty Check History -->
            <div v-else class="bg-gray-50 rounded-lg border border-gray-200 p-6 text-center">
                <p class="text-gray-600">Aucune vérification n'a encore été effectuée pour ce backlink.</p>
            </div>
        </div>
    </div>
</template>
