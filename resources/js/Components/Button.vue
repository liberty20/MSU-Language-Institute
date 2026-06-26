<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-5 py-2.5 bg-brand-blue border border-transparent rounded-xl font-extrabold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-light hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-all duration-200 cursor-pointer">
        <slot />
    </button>
</template>
