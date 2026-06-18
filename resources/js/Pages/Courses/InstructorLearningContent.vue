<template>
    <Head title="Learning Content Manager" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span class="font-extrabold text-[#0a1f44]">Learning Content Manager</span>
                <button @click="openUploadModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black text-xs px-4 py-2.5 rounded-full shadow transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Upload Material
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">Instructor Console</span>
                    <h3 class="text-2xl font-black leading-tight">Course Learning Materials</h3>
                    <p class="text-sm text-gray-300 max-w-xl">
                        Upload slides, manuals, reading lists, assignments, or video lectures for your assigned course intakes. Enrolled students will automatically gain instant access to download or preview them.
                    </p>
                </div>
            </div>

            <!-- Learning Content Grid -->
            <div v-if="learningContents.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h4 class="font-bold text-brand-blue text-lg">No Learning Content Uploaded Yet</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">Get started by uploading files such as course documents, PDF slides, or videos for your assigned intakes.</p>
                <button @click="openUploadModal" class="mt-4 bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black text-xs px-5 py-2.5 rounded-full transition shadow">
                    Upload Your First File
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="content in learningContents" :key="content.id" class="bg-white rounded-2xl border border-gray-100 overflow-hidden flex flex-col shadow-sm hover:shadow-md transition">
                    <div class="p-6 flex-grow space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold bg-[#0a1f44]/10 text-brand-blue rounded-full uppercase tracking-wider">
                                {{ content.intake?.course?.code }}
                            </span>
                            <span class="text-xs text-gray-400 font-semibold">{{ formatSize(content.file_size) }}</span>
                        </div>

                        <div>
                            <h3 class="text-base font-black text-brand-blue leading-snug line-clamp-1" :title="content.title">{{ content.title }}</h3>
                            <p class="text-xs text-gray-400 font-bold mt-1">Intake: {{ content.intake?.name }}</p>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed line-clamp-3 min-h-[4.5rem]">
                            {{ content.description || 'No additional description provided.' }}
                        </p>

                        <!-- File metadata widget -->
                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center gap-3 text-xs">
                            <div class="w-8 h-8 rounded-lg bg-[#0a1f44]/5 flex items-center justify-center text-brand-blue">
                                <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getFileIcon(content.mime_type)"></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-gray-700 truncate" :title="content.file_name">{{ content.file_name }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5 uppercase font-black tracking-wider">{{ content.mime_type.split('/').pop() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions panel -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-1.5">
                            <button @click="triggerPreview(content)" class="px-3 py-1.5 bg-[#0a1f44]/5 text-[#0a1f44] hover:bg-[#0a1f44] hover:text-white rounded-lg text-xs font-bold transition">
                                Preview
                            </button>
                            <a :href="route('files.download', { type: 'learning_content', id: content.id })" class="px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-700 hover:text-white rounded-lg text-xs font-bold transition flex items-center gap-1">
                                Download
                            </a>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="openEditModal(content)" class="p-1.5 hover:bg-amber-100 text-amber-700 rounded-lg transition" title="Edit Metadata">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button @click="deleteContent(content.id)" class="p-1.5 hover:bg-red-100 text-red-700 rounded-lg transition" title="Delete Material">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Upload Modal -->
        <div v-if="uploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeUploadModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-100">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold text-[#f5c242] uppercase tracking-wide">Upload Learning Material</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Publish reference guides or coursework content.</p>
                    </div>
                    <button @click="closeUploadModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitUpload" class="p-6 space-y-4">
                    <div v-if="flashError" class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-xs font-semibold">
                        {{ flashError }}
                    </div>

                    <!-- Intake Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Course Intake *</label>
                        <select v-model="form.course_intake_id" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="">Select Course Intake...</option>
                            <option v-for="intake in intakes" :key="intake.id" :value="intake.id">
                                {{ intake.course?.title }} ({{ intake.name }})
                            </option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Material Title *</label>
                        <input v-model="form.title" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Swahili Grammar Lecture 1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Short Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm resize-none" placeholder="Provide description or usage guidelines..."></textarea>
                    </div>

                    <!-- File Input -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Choose Document / Material *</label>
                        <input type="file" @change="handleFileUpload" required class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/20" />
                        <span class="text-[10px] text-gray-400 block mt-1.5">Supported formats: PDF, DOC, DOCX, PPT, PPTX, MP4, PNG, JPG (Max 50MB)</span>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeUploadModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="processing" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-xs disabled:opacity-50">
                            {{ processing ? 'Uploading Content...' : 'Publish Content' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Metadata Modal -->
        <div v-if="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeEditModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-100">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold text-[#f5c242] uppercase tracking-wide">Edit Learning Material</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Update details or replace files.</p>
                    </div>
                    <button @click="closeEditModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitUpdate" class="p-6 space-y-4">
                    <div v-if="flashError" class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-xs font-semibold">
                        {{ flashError }}
                    </div>

                    <!-- Course Intake (ReadOnly) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Course Intake</label>
                        <input type="text" :value="selectedContent?.intake?.course?.title + ' (' + selectedContent?.intake?.name + ')'" disabled class="w-full text-xs rounded-xl border-gray-200 bg-gray-50 text-gray-500 font-semibold" />
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Material Title *</label>
                        <input v-model="editForm.title" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Swahili Grammar Lecture 1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Short Description</label>
                        <textarea v-model="editForm.description" rows="3" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm resize-none" placeholder="Provide description or usage guidelines..."></textarea>
                    </div>

                    <!-- File Upload (Optional replacement) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Replace Material (Optional)</label>
                        <input type="file" @change="handleFileUpload" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/20" />
                        <span class="text-[10px] text-gray-400 block mt-1.5">Leave blank to keep existing file: <strong>{{ selectedContent?.file_name }}</strong></span>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="processing" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-xs disabled:opacity-50">
                            {{ processing ? 'Updating Content...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Universal Document Preview Modal -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-sm" @click.self="closePreviewModal">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden border border-gray-100 h-[80vh] flex flex-col">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold text-[#f5c242] uppercase tracking-wide">Preview Material: {{ previewTitle }}</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Document Viewer ({{ previewFileName }})</p>
                    </div>
                    <button @click="closePreviewModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-grow p-4 bg-gray-100 flex items-center justify-center overflow-auto relative">
                    <!-- Loading spinner -->
                    <div v-if="previewLoading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-[#0a1f44] border-t-transparent"></div>
                    </div>

                    <!-- PDF Preview -->
                    <iframe v-if="isPdf(previewMime)" :src="previewSrc" class="w-full h-full rounded border-0" @load="previewLoading = false"></iframe>

                    <!-- Image Preview -->
                    <img v-else-if="isImage(previewMime)" :src="previewSrc" class="max-w-full max-h-full object-contain rounded shadow" @load="previewLoading = false" />

                    <!-- Video Preview -->
                    <video v-else-if="isVideo(previewMime)" :src="previewSrc" controls autoplay class="max-w-full max-h-full rounded shadow" @loadeddata="previewLoading = false"></video>

                    <!-- Office Fallback Warning -->
                    <div v-else class="text-center p-8 bg-white rounded-xl shadow-sm border border-gray-150 max-w-md">
                        <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-extrabold text-brand-blue text-sm">Preview Not Supported Inline</h4>
                        <p class="text-xs text-gray-500 mt-1">Word Documents, Excel, and PowerPoints cannot be previewed natively in the web browser.</p>
                        <div class="mt-4 flex gap-2 justify-center">
                            <a :href="previewSrc" target="_blank" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition">
                                Open in New Tab
                            </a>
                            <a :href="previewDownloadSrc" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded-xl transition shadow">
                                Download File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { ref, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    intakes: Array,
    learningContents: Array,
});

// Create/Upload state
const uploadModalOpen = ref(false);
const processing = ref(false);
const flashError = ref('');
const fileUpload = ref(null);

const form = reactive({
    course_intake_id: '',
    title: '',
    description: '',
});

// Edit state
const editModalOpen = ref(false);
const selectedContent = ref(null);
const editForm = reactive({
    title: '',
    description: '',
});

// Preview state
const previewModalOpen = ref(false);
const previewTitle = ref('');
const previewFileName = ref('');
const previewMime = ref('');
const previewSrc = ref('');
const previewDownloadSrc = ref('');
const previewLoading = ref(false);

const openUploadModal = () => {
    uploadModalOpen.value = true;
};

const closeUploadModal = () => {
    uploadModalOpen.value = false;
    form.course_intake_id = '';
    form.title = '';
    form.description = '';
    fileUpload.value = null;
    flashError.value = '';
};

const openEditModal = (content) => {
    selectedContent.value = content;
    editForm.title = content.title;
    editForm.description = content.description;
    editModalOpen.value = true;
};

const closeEditModal = () => {
    editModalOpen.value = false;
    selectedContent.value = null;
    fileUpload.value = null;
    flashError.value = '';
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 50 * 1024 * 1024) {
        flashError.value = 'Validation Error: File size exceeds the maximum 50MB limit.';
        e.target.value = '';
        fileUpload.value = null;
        return;
    }
    fileUpload.value = file;
    flashError.value = '';
};

const submitUpload = () => {
    if (!form.course_intake_id) {
        flashError.value = 'Please select a course intake.';
        return;
    }
    if (!form.title) {
        flashError.value = 'Please provide a title.';
        return;
    }
    if (!fileUpload.value) {
        flashError.value = 'Please choose a file to upload.';
        return;
    }

    processing.value = true;
    flashError.value = '';

    const formData = new FormData();
    formData.append('course_intake_id', form.course_intake_id);
    formData.append('title', form.title);
    formData.append('description', form.description || '');
    formData.append('file', fileUpload.value);

    Inertia.post(route('instructor.learning-content.store'), formData, {
        onSuccess: () => {
            processing.value = false;
            closeUploadModal();
        },
        onError: (err) => {
            processing.value = false;
            flashError.value = Object.values(err).join(' ');
        }
    });
};

const submitUpdate = () => {
    if (!editForm.title) {
        flashError.value = 'Title is required.';
        return;
    }

    processing.value = true;
    flashError.value = '';

    const formData = new FormData();
    formData.append('_method', 'PUT'); // Spoof PUT method for multipart form-data
    formData.append('title', editForm.title);
    formData.append('description', editForm.description || '');
    if (fileUpload.value) {
        formData.append('file', fileUpload.value);
    }

    Inertia.post(route('instructor.learning-content.update', selectedContent.value.id), formData, {
        onSuccess: () => {
            processing.value = false;
            closeEditModal();
        },
        onError: (err) => {
            processing.value = false;
            flashError.value = Object.values(err).join(' ');
        }
    });
};

const deleteContent = (id) => {
    if (confirm('Are you sure you want to permanently delete this learning content material?')) {
        Inertia.delete(route('instructor.learning-content.destroy', id));
    }
};

// Preview functionality
const triggerPreview = (content) => {
    previewTitle.value = content.title;
    previewFileName.value = content.file_name;
    previewMime.value = content.mime_type;
    previewSrc.value = route('files.preview', { type: 'learning_content', id: content.id });
    previewDownloadSrc.value = route('files.download', { type: 'learning_content', id: content.id });
    
    const isInline = isPdf(content.mime_type) || isImage(content.mime_type) || isVideo(content.mime_type);
    previewLoading.value = isInline;
    previewModalOpen.value = true;
};

const closePreviewModal = () => {
    previewModalOpen.value = false;
    previewTitle.value = '';
    previewFileName.value = '';
    previewMime.value = '';
    previewSrc.value = '';
    previewDownloadSrc.value = '';
};

// File type helpers
const isPdf = (mime) => mime === 'application/pdf';
const isImage = (mime) => mime.startsWith('image/');
const isVideo = (mime) => mime.startsWith('video/');

const formatSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileIcon = (mime) => {
    if (mime === 'application/pdf') {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />';
    }
    if (mime.startsWith('image/')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />';
    }
    if (mime.startsWith('video/')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />';
    }
    return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />';
};
</script>
