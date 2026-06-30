<template>
    <Head title="System Under Maintenance" />
    <div class="min-h-screen bg-[#0a1f44] text-white flex flex-col items-center justify-center p-6 text-center select-none">
        <div class="max-w-md bg-white/5 backdrop-blur-md border border-[#f5c242]/20 p-8 rounded-3xl shadow-2xl space-y-6 animate-in fade-in zoom-in-95 duration-300">
            <img src="/msu-logo-2.png" alt="MSU Logo" class="h-20 w-auto mx-auto bg-white p-2 rounded-2xl shadow-lg border border-brand-gold/30" />
            <div class="space-y-2">
                <span class="px-3 py-1 bg-red-650/10 text-red-500 border border-red-500/20 rounded-full text-xs font-black uppercase tracking-wider">System Offline</span>
                <h2 class="text-3xl font-black tracking-tight mt-2">Under Maintenance</h2>
                <p class="text-sm text-gray-300 leading-relaxed font-medium">
                    The Midlands State University National Language Institute portal is currently undergoing scheduled system updates. Please try again later.
                </p>
            </div>
            <div class="pt-6 border-t border-white/10 text-xs text-gray-400 space-y-2 font-semibold">
                <p>Administrative Contact: <a :href="'mailto:' + contactInfo.email" class="text-[#f5c242] hover:underline">{{ contactInfo.email }}</a></p>
                <p>Support Desk: {{ contactInfo.phone }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/inertia-vue3';

defineProps({
    contactInfo: Object
});
</script>

<style scoped>
.text-brand-gold {
    color: #f5c242;
}
</style>
