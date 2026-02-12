<script setup>
import { useRoute } from 'vue-router';
import BacklinksList from '@/components/Backlinks/BacklinksList.vue';

const route = useRoute();
const projectId = route.params.projectId;
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <BacklinksList :project-id="projectId" />
    </div>
</template>
