<script setup>
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const form = useForm({
    client_type: 'organization',
    organization: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    status: 'active',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <BreezeGuestLayout :wide="true">
        <Head title="Client Registration" />

        <div class="mb-6">
            <h2 class="text-2xl font-black text-gray-900">Client Registration</h2>
            <p class="text-sm text-gray-500 mt-1">Create your personalized client portal account</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Client Type</label>
                    <select v-model="form.client_type" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm">
                        <option value="organization">Organization</option>
                        <option value="individual">Individual</option>
                    </select>
                    <div v-if="form.errors.client_type" class="text-red-500 text-xs mt-1">{{ form.errors.client_type }}</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
                    <select v-model="form.status" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                </div>
            </div>

            <div v-if="form.client_type === 'organization'">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Organization Name</label>
                <input v-model="form.organization" type="text" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm" placeholder="Enter company or organization name">
                <div v-if="form.errors.organization" class="text-red-500 text-xs mt-1">{{ form.errors.organization }}</div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Person Full Name <span class="text-red-500">*</span></label>
                <input v-model="form.contact_person" type="text" required class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm" placeholder="e.g. John Doe">
                <div v-if="form.errors.contact_person" class="text-red-500 text-xs mt-1">{{ form.errors.contact_person }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input v-model="form.email" type="email" required class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm" placeholder="name@example.com">
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone Number</label>
                    <input v-model="form.phone" type="text" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm" placeholder="e.g. +263 77 123 4567">
                    <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                    <input v-model="form.password" type="password" required class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm">
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                    <input v-model="form.password_confirmation" type="password" required class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm">
                    <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Physical Address</label>
                <textarea v-model="form.address" rows="2" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue shadow-sm text-sm" placeholder="Enter your office or residential address"></textarea>
                <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <Link :href="route('login')" class="text-sm font-semibold text-brand-blue hover:text-brand-blue-light transition">
                    Already registered? Log in
                </Link>

                <button type="submit" :disabled="form.processing" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-md transition disabled:opacity-50 flex items-center gap-2">
                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Register
                </button>
            </div>
        </form>
    </BreezeGuestLayout>
</template>
