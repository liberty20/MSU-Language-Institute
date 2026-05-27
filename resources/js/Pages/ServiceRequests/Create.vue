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
                        <select v-if="!$page.props.auth.roles.includes('client')" v-model="form.client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="" disabled>Select a client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.organization || client.contact_person }}
                            </option>
                        </select>
                        <input v-else type="text" :value="clients[0]?.organization || clients[0]?.contact_person" disabled class="w-full rounded-md border-gray-300 bg-gray-50 text-gray-550 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
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

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Attach Files / Source Documents</label>
                        <div class="relative border-2 border-dashed border-gray-300 hover:border-[#0a1f44] rounded-xl p-6 text-center cursor-pointer transition-colors"
                             :class="{ 'border-[#0a1f44] bg-blue-50/10': isDragOver }"
                             @dragover.prevent="isDragOver = true"
                             @dragleave="isDragOver = false"
                             @drop.prevent="handleFileDrop">
                            <input type="file" multiple ref="fileInput" @change="handleFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            
                            <div class="space-y-2">
                                <svg class="w-10 h-10 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <div class="text-sm font-semibold text-gray-700">
                                    Click to select or drag & drop files
                                </div>
                                <div class="text-xs text-gray-400">
                                    Supported file types: PDF, DOC, DOCX, ZIP, PNG, JPG (Max 10MB per file)
                                </div>
                                <div v-if="form.files && form.files.length > 0" class="text-xs text-[#0a1f44] font-semibold mt-2 text-left bg-gray-50 p-3 rounded-lg border border-gray-150">
                                    Selected files ({{ form.files.length }}):
                                    <ul class="mt-1 space-y-1 text-gray-700 list-disc list-inside">
                                        <li v-for="(file, index) in form.files" :key="index">
                                            {{ file.name }} ({{ (file.size / 1024).toFixed(1) }} KB)
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.files" class="text-red-500 text-xs mt-1">{{ form.errors.files }}</div>
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
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    clients: Array,
    default_client_id: [String, Number]
});

const isDragOver = ref(false);

const form = useForm({
    client_id: props.default_client_id || '',
    service_category: '',
    title: '',
    description: '',
    source_language: '',
    target_language: '',
    priority: 'medium',
    deadline: '',
    files: [],
});

const handleFileChange = (e) => {
    form.files = Array.from(e.target.files);
};

const handleFileDrop = (e) => {
    isDragOver.value = false;
    form.files = Array.from(e.dataTransfer.files);
};

const submit = () => {
    form.post(route('service-requests.store'));
};
</script>
