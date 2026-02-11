<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Link Tracker</h1>
        <p class="mt-2 text-gray-600">Application de monitoring de backlinks pour SEO</p>
      </div>

      <LoginForm />
    </div>
  </div>
</template>

<script setup>
import LoginForm from '../../components/Auth/LoginForm.vue';
</script>
