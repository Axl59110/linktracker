<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useProjectsStore } from '@/stores/projects';

const props = defineProps({
    projectId: {
        type: [Number, String],
        default: null
    }
});

const router = useRouter();
const store = useProjectsStore();

const form = reactive({
    name: '',
    url: '',
    status: 'active'
});

const errors = ref({});
const loading = ref(false);
const isEditMode = ref(!!props.projectId);

onMounted(async () => {
    if (isEditMode.value) {
        try {
            const project = await store.getProject(props.projectId);
            form.name = project.name;
            form.url = project.url;
            form.status = project.status;
        } catch (err) {
            alert('Erreur lors du chargement du projet');
            router.push('/projects');
        }
    }
});

const validateForm = () => {
    errors.value = {};

    if (!form.name) {
        errors.value.name = 'Le nom est requis';
    }

    if (!form.url) {
        errors.value.url = 'L\'URL est requise';
    } else {
        try {
            new URL(form.url);
        } catch {
            errors.value.url = 'L\'URL n\'est pas valide';
        }
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) return;

    loading.value = true;

    try {
        if (isEditMode.value) {
            await store.updateProject(props.projectId, form);
            alert('Projet modifié avec succès!');
        } else {
            await store.createProject(form);
            alert('Projet créé avec succès!');
        }
        router.push('/projects');
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    } finally {
        loading.value = false;
    }
};

const handleCancel = () => {
    router.push('/projects');
};
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">
            {{ isEditMode ? 'Modifier le projet' : 'Nouveau projet' }}
        </h1>

        <form @submit.prevent="handleSubmit" class="bg-white shadow-md rounded-lg p-6">
            <!-- Name Field -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nom du projet <span class="text-red-500">*</span>
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :class="{ 'border-red-500': errors.name }"
                    placeholder="Mon super projet"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                    {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
                </p>
            </div>

            <!-- URL Field -->
            <div class="mb-4">
                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL <span class="text-red-500">*</span>
                </label>
                <input
                    id="url"
                    v-model="form.url"
                    type="url"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :class="{ 'border-red-500': errors.url }"
                    placeholder="https://example.com"
                />
                <p v-if="errors.url" class="mt-1 text-sm text-red-600">
                    {{ Array.isArray(errors.url) ? errors.url[0] : errors.url }}
                </p>
            </div>

            <!-- Status Field -->
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Statut
                </label>
                <select
                    id="status"
                    v-model="form.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="active">Actif</option>
                    <option value="paused">En pause</option>
                    <option value="archived">Archivé</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button
                    type="submit"
                    :disabled="loading"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ loading ? 'Enregistrement...' : (isEditMode ? 'Modifier' : 'Créer') }}
                </button>
                <button
                    type="button"
                    @click="handleCancel"
                    :disabled="loading"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg transition disabled:opacity-50"
                >
                    Annuler
                </button>
            </div>
        </form>
    </div>
</template>
