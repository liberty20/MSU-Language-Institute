<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[#0a1f44] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#0c2859] active:bg-[#081836] focus:outline-none focus:border-[#081836] focus:shadow-outline-gray transition ease-in-out duration-150">
        <slot />
    </button>
</template>
