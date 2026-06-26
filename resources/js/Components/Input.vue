<script setup>
import { onMounted, ref } from 'vue';

defineProps(['modelValue']);

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});
</script>

<template>
    <input class="border-gray-250 rounded-xl shadow-sm focus:border-brand-blue focus:ring focus:ring-brand-blue/15 transition-all duration-200" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
</template>
