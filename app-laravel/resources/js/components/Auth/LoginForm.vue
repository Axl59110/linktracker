<template>
  <div class="w-full max-w-md mx-auto">
    <div class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8">
      <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Connexion</h2>

      <!-- Formulaire de connexion -->
      <form @submit.prevent="handleLogin">
        <!-- Champ Email -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
            Adresse Email
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            :disabled="authStore.loading"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline disabled:bg-gray-100 disabled:cursor-not-allowed"
            :class="{ 'border-red-500': authStore.error }"
            placeholder="exemple@domain.com"
          />
        </div>

        <!-- Champ Mot de passe -->
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
            Mot de passe
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            :disabled="authStore.loading"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline disabled:bg-gray-100 disabled:cursor-not-allowed"
            :class="{ 'border-red-500': authStore.error }"
            placeholder="••••••••"
          />
        </div>

        <!-- Message d'erreur -->
        <div v-if="authStore.error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
          {{ authStore.error }}
        </div>

        <!-- Message de succès -->
        <div v-if="successMessage" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
          {{ successMessage }}
        </div>

        <!-- Bouton de soumission -->
        <div class="flex items-center justify-between">
          <button
            type="submit"
            :disabled="authStore.loading"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50 disabled:cursor-not-allowed w-full"
          >
            <span v-if="!authStore.loading">Se connecter</span>
            <span v-else>Connexion en cours...</span>
          </button>
        </div>
      </form>

      <!-- Lien vers inscription ou mot de passe oublié -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Identifiants de test : <br />
          <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs">test@example.com</span> /
          <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs">password</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  email: '',
  password: '',
});

const successMessage = ref('');

const handleLogin = async () => {
  authStore.clearError();
  successMessage.value = '';

  const result = await authStore.login(form.value.email, form.value.password);

  if (result.success) {
    successMessage.value = result.message;

    // Redirection après 500ms
    setTimeout(() => {
      router.push({ name: 'home' });
    }, 500);
  }
};
</script>
