import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';

export const useBacklinksStore = defineStore('backlinks', () => {
    const backlinks = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const hasBacklinks = computed(() => backlinks.value.length > 0);

    async function fetchBacklinks(projectId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/projects/${projectId}/backlinks`);
            backlinks.value = response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des backlinks';
            console.error('Error fetching backlinks:', err);
        } finally {
            loading.value = false;
        }
    }

    async function getBacklink(projectId, backlinkId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/projects/${projectId}/backlinks/${backlinkId}`);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createBacklink(projectId, backlinkData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/projects/${projectId}/backlinks`, backlinkData);
            backlinks.value.push(response.data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.errors || err.response?.data?.message || 'Erreur lors de la création';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateBacklink(projectId, backlinkId, backlinkData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(`/api/v1/projects/${projectId}/backlinks/${backlinkId}`, backlinkData);
            const index = backlinks.value.findIndex(b => b.id === backlinkId);
            if (index !== -1) {
                backlinks.value[index] = response.data;
            }
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.errors || err.response?.data?.message || 'Erreur lors de la modification';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteBacklink(projectId, backlinkId) {
        loading.value = true;
        error.value = null;
        try {
            await axios.delete(`/api/v1/projects/${projectId}/backlinks/${backlinkId}`);
            backlinks.value = backlinks.value.filter(b => b.id !== backlinkId);
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    function clearError() {
        error.value = null;
    }

    return {
        backlinks,
        loading,
        error,
        hasBacklinks,
        fetchBacklinks,
        getBacklink,
        createBacklink,
        updateBacklink,
        deleteBacklink,
        clearError,
    };
});
