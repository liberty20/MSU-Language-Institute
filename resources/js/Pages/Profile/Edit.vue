<template>
    <Head title="Edit Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <span class="text-gray-900 font-bold">Edit Profile</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Card -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-[#0a1f44] mb-4 border-b border-gray-100 pb-3">Account Details</h3>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                   placeholder="Your full name" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email Address -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input v-model="form.email" type="email" required
                                   :disabled="!['executive_director', 'deputy_director', 'ict_administrator'].some(role => $page.props.auth.roles.includes(role))"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm disabled:opacity-70 disabled:bg-gray-50 disabled:cursor-not-allowed"
                                   placeholder="name@example.com" />
                            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            <p v-if="!['executive_director', 'deputy_director', 'ict_administrator'].some(role => $page.props.auth.roles.includes(role))" class="text-gray-400 text-xs mt-1.5">
                                Email editing is restricted to Executive Director, Deputy Director, and ICT Administrator roles only.
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-150 pt-5">
                        <h4 class="text-sm font-bold text-[#0a1f44] mb-3 uppercase tracking-wider">Change Password</h4>
                        <p class="text-xs text-gray-500 mb-4">Leave these fields blank if you do not wish to update your password.</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- New Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">New Password</label>
                                <input v-model="form.password" type="password"
                                       class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                       placeholder="••••••••" />
                                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm New Password</label>
                                <input v-model="form.password_confirmation" type="password"
                                       class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                       placeholder="••••••••" />
                                <p v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="form.processing"
                                class="px-6 py-2.5 bg-[#0a1f44] hover:bg-[#0a1f44]/90 text-white rounded-xl text-sm font-bold transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Save Updates
                        </button>
                    </div>
                </form>
            </div>

            <!-- Details Sidebar -->
            <div class="space-y-6">
                <!-- User Profile Card Summary -->
                <div class="bg-gradient-to-br from-[#0a1f44] to-[#143163] text-white rounded-2xl p-6 shadow-sm border border-[#f5c242]/20 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-[#f5c242] text-[#0a1f44] flex items-center justify-center font-black text-3xl shadow-lg border-4 border-white/10 mb-4">
                        {{ user.name.charAt(0) }}
                    </div>
                    <h4 class="text-white font-extrabold text-lg truncate w-full">{{ user.name }}</h4>
                    <p class="text-xs text-gray-300 truncate w-full mb-1">{{ user.email }}</p>
                    <div class="mt-3 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold uppercase tracking-wider text-[#f5c242] border border-white/5">
                        {{ $page.props.auth.roles[0] ? $page.props.auth.roles[0].replace('_', ' ') : 'User' }}
                    </div>
                </div>

                <!-- Info Guide -->
                <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-sm">
                    <h5 class="text-[#0a1f44] font-bold text-sm mb-3 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-5 h-5 text-[#d4af37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Security Notice
                    </h5>
                    <ul class="space-y-2 text-xs text-gray-500 list-disc list-inside leading-relaxed">
                        <li>Updating your email requires using the new address for future sign-ins.</li>
                        <li>Passwords must be at least 8 characters long and contain proper complexity.</li>
                        <li>Always keep your login credentials private and secure.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        }
    });
};
</script>
