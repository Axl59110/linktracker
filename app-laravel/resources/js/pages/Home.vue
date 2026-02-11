<template>
  <div class="container mx-auto px-4 py-16">
    <div class="text-center">
      <!-- Message de bienvenue si connecté -->
      <div v-if="authStore.isAuthenticated" class="mb-4 p-4 bg-green-100 border border-green-400 rounded-lg">
        <p class="text-green-800">
          Bienvenue, <strong>{{ authStore.user?.name }}</strong> ! Vous êtes connecté.
        </p>
      </div>

      <h1 class="text-5xl font-bold text-gray-900 mb-4">
        Link Tracker
      </h1>
      <p class="text-xl text-gray-600 mb-8">
        Application de monitoring de backlinks pour SEO
      </p>
      <div class="flex justify-center gap-4">
        <button
          v-if="!authStore.isAuthenticated"
          @click="router.push({ name: 'login' })"
          class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition"
        >
          Connexion
        </button>
        <button
          v-else
          @click="handleLogout"
          :disabled="authStore.loading"
          class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition disabled:opacity-50"
        >
          {{ authStore.loading ? 'Déconnexion...' : 'Déconnexion' }}
        </button>
        <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition">
          En savoir plus
        </button>
      </div>

      <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="text-4xl mb-4">🔍</div>
          <h3 class="text-xl font-semibold mb-2">Monitoring Automatique</h3>
          <p class="text-gray-600">Vérification automatique de vos backlinks toutes les 4 heures</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="text-4xl mb-4">🚨</div>
          <h3 class="text-xl font-semibold mb-2">Alertes en Temps Réel</h3>
          <p class="text-gray-600">Notifications instantanées en cas de liens perdus ou modifiés</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="text-4xl mb-4">📊</div>
          <h3 class="text-xl font-semibold mb-2">Métriques SEO</h3>
          <p class="text-gray-600">Suivi des DA, PA, Trust Flow de vos backlinks</p>
        </div>
      </div>

      <div class="mt-12 text-sm text-gray-500">
        <p>Sprint 1 - Foundation & Infrastructure 🚀</p>
        <p class="mt-2">Laravel 10 + Vue.js 3 + Tailwind CSS 4</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  const result = await authStore.logout();
  if (result.success) {
    router.push({ name: 'login' });
  }
};
</script>
