<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

const props = defineProps(['href', 'active']);

const classes = computed(() => props.active
    ? 'block pl-3 pr-4 py-2.5 border-l-4 border-brand-gold text-base font-bold text-brand-blue bg-amber-500/10 focus:outline-none focus:text-brand-blue focus:bg-amber-500/20 focus:border-brand-gold-dark transition duration-150 ease-in-out'
    : 'block pl-3 pr-4 py-2.5 border-l-4 border-transparent text-base font-semibold text-gray-600 hover:text-brand-blue hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-brand-blue focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
