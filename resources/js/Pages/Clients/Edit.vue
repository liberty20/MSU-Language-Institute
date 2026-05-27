<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('clients.index')" class="text-gray-400 hover:text-brand-blue transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <span>Edit Client Details</span>
            </div>
        </template>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-3xl">
            <form @submit.prevent="submit" class="p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Client Type</label>
                        <select v-model="form.client_type" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                            <option value="organization">Organization</option>
                            <option value="individual">Individual</option>
                        </select>
                        <div v-if="form.errors.client_type" class="text-red-500 text-xs mt-1">{{ form.errors.client_type }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select v-model="form.status" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                    </div>
                </div>

                <div v-if="form.client_type === 'organization'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                    <input v-model="form.organization" type="text" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <div v-if="form.errors.organization" class="text-red-500 text-xs mt-1">{{ form.errors.organization }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person Full Name</label>
                    <input v-model="form.contact_person" type="text" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <div v-if="form.errors.contact_person" class="text-red-500 text-xs mt-1">{{ form.errors.contact_person }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input v-model="form.email" type="email" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input v-model="form.phone" type="text" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                        <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Physical Address</label>
                    <textarea v-model="form.address" rows="3" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue"></textarea>
                    <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <Link :href="route('clients.index')" class="px-6 py-2.5 rounded-lg border border-gray-200 text-gray-600 font-medium hover:bg-gray-50 transition">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="bg-brand-blue hover:bg-brand-blue-light text-white px-8 py-2.5 rounded-lg font-semibold shadow transition disabled:opacity-50">
                        Update Client
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    client: Object
});

const form = useForm({
    client_type: props.client.client_type,
    organization: props.client.organization || '',
    contact_person: props.client.contact_person,
    email: props.client.email,
    phone: props.client.phone || '',
    address: props.client.address || '',
    status: props.client.status,
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};
</script>
