<template>
    <div class="relative w-full min-h-screen pb-12">
        <!-- Reusable content slot, background is handled globally in AuthenticatedLayout.vue -->
        <div class="relative z-10 w-full h-full">
            <slot />
        </div>
    </div>
</template>

<script setup>
// Wrapper component for reports layout context.
// Global background and legibility overlay is now unified under AuthenticatedLayout.vue
</script>

<style scoped>
</style>
