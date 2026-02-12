import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';

export const useProjectsStore = defineStore('projects', () => {
    const projects = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const hasProjects = computed(() => projects.value.length > 0);

    async function fetchProjects() {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/v1/projects');
            projects.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des projets';
            console.error('Error fetching projects:', err);
        } finally {
            loading.value = false;
        }
    }

    function clearError() {
        error.value = null;
    }

    return {
        projects,
        loading,
        error,
        hasProjects,
        fetchProjects,
        clearError,
    };
});
