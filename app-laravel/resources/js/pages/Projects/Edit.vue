<script setup>
import { useRoute } from 'vue-router';
import ProjectForm from '@/components/Projects/ProjectForm.vue';

const route = useRoute();
</script>

<template>
    <ProjectForm :project-id="route.params.id" />
</template>
