<template>
    <Head title="Edit Service Request" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('service-requests.show', serviceRequest.id)" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <span>Edit Service Request - {{ serviceRequest.reference_number }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <form @submit.prevent="submit" class="p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                        <input type="text" :value="clients[0]?.organization || clients[0]?.contact_person" disabled class="w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Title *</label>
                        <input type="text" v-model="form.title" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service Category *</label>
                        <select v-model="form.service_category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Language *</label>
                        <select v-model="selectedSource" @change="updateSourceLanguage" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="" disabled>Select Source Language</option>
                            <option v-for="lang in zimbabweanLanguages" :key="lang" :value="lang">{{ lang }}</option>
                        </select>
                        <div v-if="selectedSource === 'Other'" class="mt-2 transition-all duration-300">
                            <input type="text" v-model="customSource" @input="updateSourceLanguage" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="Please specify other source language" />
                        </div>
                        <div v-if="form.errors.source_language" class="text-red-500 text-xs mt-1">{{ form.errors.source_language }}</div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Language(s) *</label>
                        
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span v-for="(lang, idx) in form.target_language" :key="lang" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-[#0a1f44]/10 text-[#0a1f44] border border-[#0a1f44]/20 transition-all duration-200">
                                {{ lang }}
                                <button type="button" @click="removeTargetLanguage(idx)" class="hover:text-red-600 transition-colors focus:outline-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </span>
                            <span v-if="!form.target_language.length" class="text-xs text-gray-400 italic">No target languages selected yet.</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="relative">
                                <button type="button" @click="showTargetDropdown = !showTargetDropdown" class="w-full text-left bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 focus:outline-none focus:ring-1 focus:ring-[#0a1f44] focus:border-[#0a1f44] text-sm cursor-pointer flex justify-between items-center h-[38px] relative z-30">
                                    <span class="block truncate text-gray-700">
                                        {{ form.target_language.length ? form.target_language.join(', ') : '-- Choose Target Language(s) --' }}
                                    </span>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 z-30">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>

                                <div v-if="showTargetDropdown" class="fixed inset-0 z-20 cursor-default" @click="showTargetDropdown = false"></div>

                                <div v-if="showTargetDropdown" class="absolute left-0 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto z-40 p-2 space-y-2">
                                    <input type="text" v-model="searchLanguageQuery" placeholder="Search language..." class="w-full text-xs rounded border-gray-300 mb-1 focus:ring-[#0a1f44] focus:border-[#0a1f44]" />
                                    <div class="divide-y divide-gray-55">
                                        <label v-for="lang in filteredLanguages" :key="lang" class="flex items-center gap-2 py-1.5 px-1 hover:bg-gray-50 cursor-pointer text-xs text-gray-750">
                                            <input type="checkbox" :checked="form.target_language.includes(lang)" @change="toggleTargetLanguage(lang)" class="rounded text-[#0a1f44] focus:ring-[#0a1f44] w-3.5 h-3.5" />
                                            <span>{{ lang }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <input type="text" v-model="newCustomTarget" @keyup.enter="addCustomTarget" placeholder="Specify other language..." class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-xs" />
                                <button type="button" @click="addCustomTarget" class="bg-[#0a1f44] text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-[#152a4d] transition-colors">Add</button>
                            </div>
                        </div>
                        <div v-if="form.errors.target_language" class="text-red-500 text-xs mt-1">{{ form.errors.target_language }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proposed Deadline</label>
                        <input type="date" v-model="form.deadline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                        <div v-if="form.errors.deadline" class="text-red-500 text-xs mt-1">{{ form.errors.deadline }}</div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Requirements / Scope of Work *</label>
                        <textarea v-model="form.description" rows="5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" placeholder="Please specify details..."></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 pt-6 gap-4">
                    <Link :href="route('service-requests.show', serviceRequest.id)" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing" class="bg-[#0a1f44] text-white px-6 py-2 rounded-lg font-medium text-sm hover:bg-[#152a4d] transition-colors disabled:opacity-50">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    serviceRequest: Object,
    clients: Array,
});

const zimbabweanLanguages = [
    'Chewa',
    'ChiBarwe',
    'English',
    'Kalanga',
    'Koisan',
    'Nambya',
    'Ndau',
    'Ndebele',
    'Shangani',
    'Shona',
    'Sign Language',
    'Sotho',
    'Tonga',
    'Tswana',
    'Venda',
    'Xhosa',
    'Other'
];

const form = useForm({
    title: props.serviceRequest.title || '',
    service_category: props.serviceRequest.service_category || '',
    priority: props.serviceRequest.priority || 'medium',
    source_language: props.serviceRequest.source_language || '',
    target_language: props.serviceRequest.target_language || [],
    description: props.serviceRequest.description || '',
    deadline: props.serviceRequest.deadline ? props.serviceRequest.deadline.split('T')[0] : '',
});

const selectedSource = ref(form.source_language && zimbabweanLanguages.includes(form.source_language) ? form.source_language : (form.source_language ? 'Other' : ''));
const customSource = ref(selectedSource.value === 'Other' ? form.source_language : '');

const updateSourceLanguage = () => {
    if (selectedSource.value === 'Other') {
        form.source_language = customSource.value;
    } else {
        form.source_language = selectedSource.value;
    }
};

const showTargetDropdown = ref(false);
const searchLanguageQuery = ref('');

const filteredLanguages = computed(() => {
    return zimbabweanLanguages.filter(lang => 
        lang !== 'Other' && 
        lang.toLowerCase().includes(searchLanguageQuery.value.toLowerCase())
    );
});

const toggleTargetLanguage = (lang) => {
    const index = form.target_language.indexOf(lang);
    if (index > -1) {
        form.target_language.splice(index, 1);
    } else {
        form.target_language.push(lang);
    }
};

const addTargetLanguage = (lang) => {
    if (lang && !form.target_language.includes(lang)) {
        form.target_language.push(lang);
    }
};

const removeTargetLanguage = (index) => {
    form.target_language.splice(index, 1);
};

const newCustomTarget = ref('');
const addCustomTarget = () => {
    const val = newCustomTarget.value.trim();
    if (val) {
        addTargetLanguage(val);
        newCustomTarget.value = '';
    }
};

const submit = () => {
    form.put(route('service-requests.update', props.serviceRequest.id));
};
</script>
