<template>
    <div class="layout-wrapper">
        <InstructorNavBar />
        <router-view />
    </div>
</template>

<script setup>
import InstructorNavBar from '@/components/instructor/InstructorNavBar.vue';
import { useInstructorStore } from '@/stores/profiles/instructorStore';
import { onMounted } from 'vue';

const profileStore = useInstructorStore();

onMounted(async () => {
    await profileStore.fetchProfile();
})
</script>