<script setup>
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import { Head, useForm, usePage } from '@inertiajs/inertia-vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.value.auth?.user);

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm.post'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <BreezeGuestLayout>
        <Head title="Confirm Identity" />

        <div class="mb-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900">Confirm Your Identity</h2>
            <p class="text-sm text-gray-500 mt-1">
                This area requires a recent password confirmation for security.
            </p>
        </div>

        <!-- User indicator -->
        <div v-if="user" class="mb-4 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-600 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
            <span>Confirming as <strong>{{ user.name }}</strong> ({{ user.email }})</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                    Password
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full px-3 py-2.5 border rounded-lg text-sm transition focus:outline-none focus:ring-2"
                    :class="form.errors.password
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-gray-300 focus:ring-blue-300 focus:border-blue-400'"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    required
                    autofocus
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                id="btn-confirm-identity"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-blue-700 hover:bg-blue-800 transition disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
                <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span v-if="form.processing" class="loading-dot"></span>
                {{ form.processing ? 'Verifying…' : 'Confirm Identity' }}
            </button>
        </form>
    </BreezeGuestLayout>
</template>

<style scoped>
.loading-dot {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
