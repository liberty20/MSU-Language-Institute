<template>
    <Head title="Welcome to MSUNLI" />
    
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <!-- Header -->
        <header class="bg-[#0a1f44] shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#f5c242]">MSUNLI</h1>
                </div>
                <div v-if="canLogin" class="flex gap-4">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold text-white hover:text-[#f5c242] transition-colors">
                        Go to Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-semibold text-white hover:text-[#f5c242] transition-colors">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="text-sm font-semibold bg-[#f5c242] text-[#0a1f44] px-4 py-2 rounded-lg hover:bg-yellow-400 transition-colors shadow-md">
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-grow flex items-center justify-center py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <div class="absolute inset-0 z-0 opacity-10">
                <svg width="100%" height="100%" xmlns="http://www.w300.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#0a1f44" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
            
            <div class="z-10 max-w-4xl text-center space-y-8">
                <h2 class="text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight">
                    Midlands State University <br/>
                    <span class="text-[#0a1f44]">National Language Institute</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed">
                    A centralized digital platform automating language services, client interactions, procurement, and workflow management with rich, modern aesthetics.
                </p>
                <div class="flex justify-center gap-6 pt-4">
                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="bg-[#0a1f44] text-white px-8 py-4 rounded-xl text-lg font-bold shadow-xl hover:bg-[#152a4d] hover:-translate-y-1 transition-all">
                        Access Portal
                    </Link>
                    <a href="#services" class="bg-white text-[#0a1f44] border-2 border-[#0a1f44] px-8 py-4 rounded-xl text-lg font-bold shadow-sm hover:bg-gray-50 transition-all">
                        Our Services
                    </a>
                </div>
            </div>
        </main>
        
        <footer class="bg-white border-t border-gray-200 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
                &copy; {{ new Date().getFullYear() }} Midlands State University National Language Institute. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>
