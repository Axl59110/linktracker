<script setup>
import { useRoute } from 'vue-router';
import BacklinkForm from '@/components/Backlinks/BacklinkForm.vue';

const route = useRoute();
const projectId = route.params.projectId;
const backlinkId = route.params.id;
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <BacklinkForm
            :project-id="projectId"
            :backlink-id="backlinkId"
        />
    </div>
</template>
