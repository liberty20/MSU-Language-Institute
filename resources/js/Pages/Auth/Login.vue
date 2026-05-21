<script setup>
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import BreezeValidationErrors from '@/Components/ValidationErrors.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <BreezeGuestLayout>
        <Head title="Welcome to MSUNLI" />

        <div class="mb-10 text-center lg:text-left">
            <h2 class="text-3xl font-black text-[#0a1f44] mb-2 tracking-tight">Welcome to MSUNLI</h2>
            <p class="text-gray-500 text-sm font-medium">Securely sign in to the operations management portal.</p>
        </div>

        <BreezeValidationErrors class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm" />

        <div v-if="status" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 font-medium text-sm text-green-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Email Input -->
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                <div class="relative">
                    <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                           class="block w-full pl-4 pr-4 py-3.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-900 placeholder-gray-400 focus:border-[#0a1f44] focus:bg-white focus:ring focus:ring-[#0a1f44]/10 transition-all shadow-sm"
                           placeholder="Enter your email here" />
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input id="password" type="password" v-model="form.password" required autocomplete="current-password"
                           class="block w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-900 placeholder-gray-400 focus:border-[#0a1f44] focus:bg-white focus:ring focus:ring-[#0a1f44]/10 transition-all shadow-sm"
                           placeholder="Enter password" />
                </div>
            </div>

            <!-- Remember & Forgot -->
            <div class="flex items-center justify-between pt-2">
                <label class="flex items-center group cursor-pointer">
                    <input type="checkbox" v-model="form.remember" class="w-5 h-5 rounded border-gray-300 text-[#0a1f44] focus:ring-[#0a1f44] cursor-pointer transition" />
                    <span class="ml-3 text-sm font-medium text-gray-600 group-hover:text-gray-900 transition">Remember my device</span>
                </label>

                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-bold text-[#0a1f44] hover:text-[#1c355d] transition">
                    Forgot password?
                </Link>
            </div>

            <!-- Submit Button (Deep Blue) -->
            <div class="pt-4">
                <button type="submit" :disabled="form.processing"
                        class="w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-[0_8px_20px_rgba(10,31,68,0.3)] text-base font-bold text-white bg-[#0a1f44] hover:bg-[#0c2859] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0a1f44] transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Authenticate</span>
                </button>
            </div>
        </form>
    </BreezeGuestLayout>
</template>
