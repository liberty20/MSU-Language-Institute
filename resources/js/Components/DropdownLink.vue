<script setup>
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    as: {
        type: String,
        default: 'a',
    },
    method: {
        type: String,
        default: 'get',
    },
});
</script>

<template>
    <Link :href="href" :as="as" :method="method" class="block w-full px-4 py-2.5 text-left text-sm leading-5 text-gray-700 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition-colors duration-150 rounded-xl">
        <slot />
    </Link>
</template>
