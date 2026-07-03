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
                        
                        <!-- Selected languages pills -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span v-for="(lang, idx) in form.target_language" :key="lang" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-[#0a1f44]/10 text-[#0a1f44] border border-[#0a1f44]/20 transition-all duration-200">
                                {{ lang }}
                                <button type="button" @click="removeTargetLanguage(idx)" class="hover:text-red-600 transition-colors focus:outline-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </span>
                            <span v-if="!form.target_language.length" class="text-xs text-gray-400 italic">No target languages selected yet. Please select at least one below.</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Custom checkable multi-select dropdown -->
                            <div class="relative">
                                <!-- Trigger Button -->
                                <button type="button" @click="showTargetDropdown = !showTargetDropdown" class="w-full text-left bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 focus:outline-none focus:ring-1 focus:ring-[#0a1f44] focus:border-[#0a1f44] text-sm cursor-pointer flex justify-between items-center h-[38px] relative z-30">
                                    <span class="block truncate text-gray-700">
                                        {{ form.target_language.length ? form.target_language.join(', ') : '-- Choose Target Language(s) --' }}
                                    </span>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 z-30">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>

                                <!-- Click-Outside Guard Overlay -->
                                <div v-if="showTargetDropdown" class="fixed inset-0 z-20 cursor-default" @click="showTargetDropdown = false"></div>

                                <!-- Dropdown panel -->
                                <div v-if="showTargetDropdown" class="absolute z-30 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm border border-gray-150">
                                    <!-- Search filter -->
                                    <div class="px-3 py-2 border-b border-gray-100 sticky top-0 bg-white z-10">
                                        <input type="text" v-model="searchLanguageQuery" placeholder="Search language..." class="w-full text-xs rounded-md border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] py-1 px-2" />
                                    </div>
                                    <!-- Language options with checkboxes -->
                                    <div class="divide-y divide-gray-50 max-h-48 overflow-y-auto">
                                        <label v-for="lang in filteredLanguages" :key="lang" class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer select-none text-gray-700 text-sm font-medium">
                                            <input type="checkbox" :checked="form.target_language.includes(lang)" @change="toggleTargetLanguage(lang)" class="h-4 w-4 text-[#0a1f44] focus:ring-[#0a1f44] border-gray-300 rounded mr-3" />
                                            <span>{{ lang }}</span>
                                        </label>
                                        <div v-if="filteredLanguages.length === 0" class="text-xs text-gray-400 italic p-3 text-center">
                                            No languages match search.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom target language text input -->
                            <div class="flex gap-2">
                                <input type="text" v-model="newCustomTarget" placeholder="Specify other language..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" @keydown.enter.prevent="addCustomTarget" />
                                <button type="button" @click="addCustomTarget" class="px-4 py-2 bg-[#0a1f44] text-white rounded-md text-xs font-bold hover:bg-[#152a4d] transition-colors">
                                    Add
                                </button>
                            </div>
                        </div>
                        <div v-if="form.errors.target_language" class="text-red-500 text-xs mt-1">{{ form.errors.target_language }}</div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Attach Files / Source Documents</label>

                        <!-- Dynamic file rows -->
                        <div class="space-y-2 mb-3">
                            <div v-for="(row, idx) in fileRows" :key="row.id"
                                class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 hover:border-[#0a1f44]/30 transition group"
                                :class="{'border-dashed border-2 border-gray-300 bg-blue-50/10': idx === 0 && isDragOver}"
                                @dragover.prevent="idx === 0 && (isDragOver = true)"
                                @dragleave="isDragOver = false"
                                @drop.prevent="idx === 0 ? handleFileDrop($event, idx) : null"
                            >
                                <!-- File icon -->
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :class="row.file ? 'bg-[#0a1f44]/10' : 'bg-gray-100'">
                                    <svg class="w-4 h-4" :class="row.file ? 'text-[#0a1f44]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </div>

                                <!-- File picker -->
                                <div class="flex-grow min-w-0">
                                    <div v-if="row.file" class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-800 truncate">{{ row.file.name }}</span>
                                        <span class="text-xs text-gray-400 flex-shrink-0">({{ (row.file.size / 1024).toFixed(0) }} KB)</span>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <label :for="`file-row-${row.id}`" class="text-sm text-[#0a1f44] font-semibold cursor-pointer hover:underline truncate">
                                            {{ idx === 0 ? 'Click to select or drag & drop' : 'Click to select file' }}
                                        </label>
                                        <span class="text-xs text-gray-400 flex-shrink-0">PDF, DOC, ZIP, PNG, JPG</span>
                                    </div>
                                    <input :id="`file-row-${row.id}`" type="file"
                                        accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg"
                                        class="sr-only"
                                        @change="handleRowFileChange(idx, $event)" />
                                </div>

                                <!-- Remove button -->
                                <button v-if="fileRows.length > 1" type="button" @click="removeFileRow(idx)"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition opacity-0 group-hover:opacity-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Add another file button -->
                        <div class="flex items-center justify-between">
                            <button type="button" @click="addFileRow"
                                :disabled="fileRows.length >= 10"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-[#0a1f44] hover:text-white bg-[#0a1f44]/5 hover:bg-[#0a1f44] border border-[#0a1f44]/15 hover:border-[#0a1f44] px-4 py-2 rounded-lg transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Another File
                            </button>
                            <span class="text-xs text-gray-400 font-medium">{{ fileRows.filter(r => r.file).length }}/10 files · Max 10MB each</span>
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
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    clients: Array,
    default_client_id: [String, Number]
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

const isDragOver = ref(false);

// --- Dynamic multi-file row system ---
let _rowCounter = 0;
const fileRows = ref([{ id: _rowCounter++, file: null }]);

const ALLOWED_EXT = /\.(pdf|doc|docx|zip|png|jpg|jpeg)$/i;

const validateFile = (file) => {
    if (file.size > 10 * 1024 * 1024) return 'File "' + file.name + '" exceeds 10MB limit.';
    if (!ALLOWED_EXT.test(file.name)) return 'File "' + file.name + '" has an unsupported type. Use PDF, DOC, DOCX, ZIP, PNG or JPG.';
    return null;
};

const syncFormFiles = () => {
    form.files = fileRows.value.filter(r => r.file).map(r => r.file);
};

const addFileRow = () => {
    if (fileRows.value.length < 10) {
        fileRows.value.push({ id: _rowCounter++, file: null });
    }
};

const removeFileRow = (idx) => {
    fileRows.value.splice(idx, 1);
    syncFormFiles();
};

const handleRowFileChange = (idx, e) => {
    const file = e.target.files[0];
    if (!file) { fileRows.value[idx].file = null; syncFormFiles(); return; }
    const err = validateFile(file);
    if (err) { alert(err); e.target.value = ''; fileRows.value[idx].file = null; return; }
    fileRows.value[idx].file = file;
    syncFormFiles();
};

const handleFileDrop = (e, idx) => {
    isDragOver.value = false;
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const err = validateFile(file);
    if (err) { alert(err); return; }
    fileRows.value[idx].file = file;
    syncFormFiles();
};

const form = useForm({
    client_id: props.default_client_id || '',
    service_category: '',
    title: '',
    description: '',
    source_language: '',
    target_language: [],
    priority: 'medium',
    deadline: '',
    files: [],
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
    form.post(route('service-requests.store'));
};

</script>
