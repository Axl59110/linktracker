<script setup>
import { onMounted, computed } from 'vue';
import { useBacklinksStore } from '@/stores/backlinks';
import { useRouter } from 'vue-router';

const props = defineProps({
    projectId: {
        type: [Number, String],
        required: true
    }
});

const store = useBacklinksStore();
const router = useRouter();

onMounted(() => {
    store.fetchBacklinks(props.projectId);
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

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
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

const activeBacklinks = computed(() => store.backlinks.filter(b => b.status === 'active'));
const lostBacklinks = computed(() => store.backlinks.filter(b => b.status === 'lost'));
const changedBacklinks = computed(() => store.backlinks.filter(b => b.status === 'changed'));
</script>

<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Backlinks</h2>
            <button
                @click="router.push(`/projects/${projectId}/backlinks/create`)"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
            >
                + Ajouter un backlink
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Actifs</p>
                        <p class="text-2xl font-bold text-green-600">{{ activeBacklinks.length }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl">✓</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Perdus</p>
                        <p class="text-2xl font-bold text-red-600">{{ lostBacklinks.length }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl">✗</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Modifiés</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ changedBacklinks.length }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl">⚠</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="store.loading" class="text-center py-12 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Chargement des backlinks...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="store.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ store.error }}
        </div>

        <!-- Empty State -->
        <div v-else-if="!store.hasBacklinks" class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun backlink</h3>
            <p class="mt-1 text-gray-500">Commencez par ajouter votre premier backlink à surveiller</p>
            <button
                @click="router.push(`/projects/${projectId}/backlinks/create`)"
                class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
            >
                Ajouter un backlink
            </button>
        </div>

        <!-- Backlinks Table -->
        <div v-else class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Source URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Texte d'ancre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dernier check
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Présence
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="backlink in store.backlinks"
                        :key="backlink.id"
                        class="hover:bg-gray-50 transition"
                    >
                        <td class="px-6 py-4">
                            <a
                                :href="backlink.source_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-blue-600 hover:text-blue-800 underline text-sm max-w-xs truncate block"
                            >
                                {{ backlink.source_url }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                            {{ backlink.anchor_text || 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="getStatusColor(backlink.status)"
                                class="px-3 py-1 text-xs font-medium rounded-full border"
                            >
                                {{ getStatusLabel(backlink.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ formatDateTime(backlink.last_checked_at) }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="getCheckStatusIcon(backlink).color"
                                class="text-lg font-bold"
                                :title="getCheckStatusIcon(backlink).label"
                            >
                                {{ getCheckStatusIcon(backlink).icon }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button
                                    @click="router.push(`/projects/${projectId}/backlinks/${backlink.id}`)"
                                    class="text-gray-600 hover:text-gray-900 transition"
                                    title="Voir les détails"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button
                                    @click="router.push(`/projects/${projectId}/backlinks/${backlink.id}/edit`)"
                                    class="text-blue-600 hover:text-blue-800 transition"
                                    title="Modifier"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
