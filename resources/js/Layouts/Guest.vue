<script setup>
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    wide: {
        type: Boolean,
        default: false,
    }
});
</script>

<template>
    <div class="min-h-screen flex w-full">
        <!-- Left Side: Branding (Background Image Only) -->
        <div class="hidden lg:flex flex-col justify-center items-center w-1/2 bg-[url('/msuli-build.jpg')] bg-cover bg-center relative shadow-2xl z-10">
            <!-- Deep blue gradient from bottom right up to MSUNLI text -->
            <div class="absolute inset-0 bg-gradient-to-tl from-[#0a1f44] via-[#0a1f44]/60 to-transparent"></div>

            <div class="relative z-20 flex flex-col items-center px-12 text-center">
                <Link href="/">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="w-56 h-auto bg-white p-6 rounded-[2rem] shadow-2xl mb-10 transform transition duration-500 hover:scale-105" />
                </Link>
                <h1 class="text-4xl lg:text-5xl font-black text-white tracking-wide mb-4 drop-shadow-xl">MSUNLI</h1>
                <p class="text-xl text-[#f5c242] font-bold tracking-wider drop-shadow-xl">Midlands State University</p>
                <p class="text-sm text-white uppercase tracking-[0.3em] mt-3 font-bold drop-shadow-lg">National Language Institute</p>
            </div>
        </div>

        <!-- Right Side: Login Form Box (Deep Blue Pane) -->
        <div class="flex-1 flex flex-col justify-center items-center px-6 sm:px-12 relative bg-[#0a1f44]">
            <!-- Mobile Logo (shows only on small screens) -->
            <div class="lg:hidden mb-10 text-center">
                <Link href="/">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="w-32 h-auto bg-white p-3 rounded-2xl shadow-xl mx-auto mb-4" />
                </Link>
                <h1 class="text-2xl font-black text-white drop-shadow-md">MSUNLI</h1>
            </div>

            <!-- Login Box (White to keep form inputs easily readable) -->
            <div class="w-full bg-white p-8 sm:p-12 shadow-2xl rounded-3xl border border-gray-100" :class="wide ? 'max-w-2xl' : 'max-w-md'">
                <!-- The actual form will be injected here -->
                <slot />
            </div>
            
            <div class="absolute bottom-6 text-center w-full">
                <p class="text-xs text-gray-400 font-medium">&copy; {{ new Date().getFullYear() }} MSUNLI. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>
