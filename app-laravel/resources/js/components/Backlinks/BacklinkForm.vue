<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useBacklinksStore } from '@/stores/backlinks';

const props = defineProps({
    projectId: {
        type: [Number, String],
        required: true
    },
    backlinkId: {
        type: [Number, String],
        default: null
    }
});

const router = useRouter();
const store = useBacklinksStore();

const formData = ref({
    source_url: '',
    target_url: '',
    anchor_text: '',
    status: 'active'
});

const errors = ref({});
const loading = ref(false);
const isEditMode = computed(() => !!props.backlinkId);

onMounted(async () => {
    if (isEditMode.value) {
        loading.value = true;
        try {
            const backlink = await store.getBacklink(props.projectId, props.backlinkId);
            formData.value = {
                source_url: backlink.source_url,
                target_url: backlink.target_url,
                anchor_text: backlink.anchor_text || '',
                status: backlink.status
            };
        } catch (err) {
            console.error('Error loading backlink:', err);
        } finally {
            loading.value = false;
        }
    }
});

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    if (!formData.value.source_url) {
        errors.value.source_url = 'L\'URL source est requise';
        isValid = false;
    } else if (!isValidUrl(formData.value.source_url)) {
        errors.value.source_url = 'L\'URL source n\'est pas valide';
        isValid = false;
    }

    if (!formData.value.target_url) {
        errors.value.target_url = 'L\'URL cible est requise';
        isValid = false;
    } else if (!isValidUrl(formData.value.target_url)) {
        errors.value.target_url = 'L\'URL cible n\'est pas valide';
        isValid = false;
    }

    return isValid;
};

const isValidUrl = (url) => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }

    loading.value = true;
    errors.value = {};

    try {
        if (isEditMode.value) {
            await store.updateBacklink(props.projectId, props.backlinkId, formData.value);
        } else {
            await store.createBacklink(props.projectId, formData.value);
        }
        router.push(`/projects/${props.projectId}/backlinks`);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            errors.value.general = err.response?.data?.message || 'Une erreur est survenue';
        }
    } finally {
        loading.value = false;
    }
};

const handleCancel = () => {
    router.push(`/projects/${props.projectId}/backlinks`);
};
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                {{ isEditMode ? 'Modifier le backlink' : 'Ajouter un backlink' }}
            </h2>

            <!-- Loading State -->
            <div v-if="loading && isEditMode" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Chargement...</p>
            </div>

            <!-- Form -->
            <form v-else @submit.prevent="handleSubmit" class="space-y-6">
                <!-- General Error -->
                <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ errors.general }}
                </div>

                <!-- Source URL -->
                <div>
                    <label for="source_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL Source *
                    </label>
                    <input
                        id="source_url"
                        v-model="formData.source_url"
                        type="url"
                        placeholder="https://example.com/article"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': errors.source_url }"
                        :disabled="loading"
                    />
                    <p v-if="errors.source_url" class="mt-1 text-sm text-red-600">
                        {{ Array.isArray(errors.source_url) ? errors.source_url[0] : errors.source_url }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        L'URL de la page contenant le lien vers votre site
                    </p>
                </div>

                <!-- Target URL -->
                <div>
                    <label for="target_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL Cible *
                    </label>
                    <input
                        id="target_url"
                        v-model="formData.target_url"
                        type="url"
                        placeholder="https://mysite.com"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': errors.target_url }"
                        :disabled="loading"
                    />
                    <p v-if="errors.target_url" class="mt-1 text-sm text-red-600">
                        {{ Array.isArray(errors.target_url) ? errors.target_url[0] : errors.target_url }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        L'URL de votre site vers laquelle pointe le backlink
                    </p>
                </div>

                <!-- Anchor Text -->
                <div>
                    <label for="anchor_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Texte d'ancre
                    </label>
                    <input
                        id="anchor_text"
                        v-model="formData.anchor_text"
                        type="text"
                        placeholder="Mon super site"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': errors.anchor_text }"
                        :disabled="loading"
                    />
                    <p v-if="errors.anchor_text" class="mt-1 text-sm text-red-600">
                        {{ Array.isArray(errors.anchor_text) ? errors.anchor_text[0] : errors.anchor_text }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Le texte cliquable du lien (optionnel)
                    </p>
                </div>

                <!-- Status (only in edit mode) -->
                <div v-if="isEditMode">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Statut
                    </label>
                    <select
                        id="status"
                        v-model="formData.status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :disabled="loading"
                    >
                        <option value="active">Actif</option>
                        <option value="lost">Perdu</option>
                        <option value="changed">Modifié</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Le statut du backlink
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 pt-4">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading" class="inline-block animate-spin mr-2">⏳</span>
                        {{ isEditMode ? 'Mettre à jour' : 'Créer le backlink' }}
                    </button>
                    <button
                        type="button"
                        @click="handleCancel"
                        :disabled="loading"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
