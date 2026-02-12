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
            projects.value = response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des projets';
            console.error('Error fetching projects:', err);
        } finally {
            loading.value = false;
        }
    }

    async function getProject(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/projects/${id}`);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createProject(projectData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post('/api/v1/projects', projectData);
            projects.value.push(response.data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.errors || err.response?.data?.message || 'Erreur lors de la création';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateProject(id, projectData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(`/api/v1/projects/${id}`, projectData);
            const index = projects.value.findIndex(p => p.id === id);
            if (index !== -1) {
                projects.value[index] = response.data;
            }
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.errors || err.response?.data?.message || 'Erreur lors de la modification';
            throw err;
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
        getProject,
        createProject,
        updateProject,
        clearError,
    };
});
