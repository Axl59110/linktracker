import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Getters
    const isAuthenticated = computed(() => user.value !== null);

    // Actions
    const getCsrfCookie = async () => {
        await window.axios.get('/sanctum/csrf-cookie');
    };

    const login = async (email, password) => {
        loading.value = true;
        error.value = null;

        try {
            // Obtenir le cookie CSRF
            await getCsrfCookie();

            // Tentative de connexion
            const response = await window.axios.post('/api/v1/auth/login', {
                email,
                password,
            });

            user.value = response.data.user;
            return { success: true, message: response.data.message };
        } catch (err) {
            const errorMessage = err.response?.data?.message ||
                                 err.response?.data?.errors?.email?.[0] ||
                                 'Une erreur est survenue lors de la connexion.';
            error.value = errorMessage;
            return { success: false, error: errorMessage };
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        loading.value = true;
        error.value = null;

        try {
            await window.axios.post('/api/v1/auth/logout');
            user.value = null;
            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la déconnexion.';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    const fetchUser = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await window.axios.get('/api/v1/auth/user');
            user.value = response.data.user;
            return { success: true };
        } catch (err) {
            user.value = null;
            if (err.response?.status !== 401) {
                error.value = 'Erreur lors de la récupération de l\'utilisateur.';
            }
            return { success: false };
        } finally {
            loading.value = false;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        // State
        user,
        loading,
        error,
        // Getters
        isAuthenticated,
        // Actions
        login,
        logout,
        fetchUser,
        clearError,
    };
});
