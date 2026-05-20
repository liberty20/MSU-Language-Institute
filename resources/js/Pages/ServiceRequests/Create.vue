<template>
    <Head title="Create Service Request" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('service-requests.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <span>Create Service Request</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <form @submit.prevent="submit" class="p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                        <select v-model="form.client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="" disabled>Select a client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.organization || client.contact_person }}
                            </option>
                        </select>
                        <div v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Title *</label>
                        <input type="text" v-model="form.title" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="e.g. Legal Document Translation" />
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service Category *</label>
                        <select v-model="form.service_category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="" disabled>Select category</option>
                            <option value="translation">Translation</option>
                            <option value="editing">Editing & Proofreading</option>
                            <option value="brailling">Brailling</option>
                            <option value="sign_language">Sign Language Interpretation</option>
                            <option value="consultancy">Consultancy</option>
                        </select>
                        <div v-if="form.errors.service_category" class="text-red-500 text-xs mt-1">{{ form.errors.service_category }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority Level *</label>
                        <select v-model="form.priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <div v-if="form.errors.priority" class="text-red-500 text-xs mt-1">{{ form.errors.priority }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Language</label>
                        <input type="text" v-model="form.source_language" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="e.g. English" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Language(s)</label>
                        <input type="text" v-model="form.target_language" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="e.g. Shona, Ndebele" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Requested Deadline</label>
                        <input type="date" v-model="form.deadline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Description *</label>
                        <textarea v-model="form.description" rows="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="Provide full details of the required services..."></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 pt-6 gap-4">
                    <Link :href="route('service-requests.index')" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing" class="bg-[#0a1f44] text-white px-6 py-2 rounded-lg font-medium text-sm hover:bg-[#152a4d] transition-colors disabled:opacity-50">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    clients: Array
});

const form = useForm({
    client_id: '',
    service_category: '',
    title: '',
    description: '',
    source_language: '',
    target_language: '',
    priority: 'medium',
    deadline: '',
});

const submit = () => {
    form.post(route('service-requests.store'));
};
</script>
