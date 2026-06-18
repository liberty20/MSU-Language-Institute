<template>
    <Head title="My Course Materials" />

    <AuthenticatedLayout>
        <template #header>
            My Learning Content
        </template>

        <div class="space-y-6">
            <!-- Summary Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">Student Desk</span>
                    <h3 class="text-2xl font-black leading-tight">Short Course Learning Materials</h3>
                    <p class="text-sm text-gray-300 max-w-xl">
                        Access, preview, or download course slides, worksheets, study guides, and multimedia contents published by your course experts.
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="learningContents.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <h4 class="font-bold text-brand-blue text-lg">No Learning Materials Found</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">Materials will appear here once your instructors publish coursework contents for your enrolled courses.</p>
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
                            <p class="text-xs text-gray-400 font-bold mt-1">Course: {{ content.intake?.course?.title }}</p>
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
                                <p class="text-[10px] text-gray-400 mt-0.5 uppercase font-black tracking-wider">Uploaded by {{ content.uploader?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions panel -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                        <button @click="triggerPreview(content)" class="px-4 py-2 bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] rounded-xl text-xs font-bold transition shadow-sm">
                            Preview Content
                        </button>
                        <a :href="route('files.download', { type: 'learning_content', id: content.id })" class="px-4 py-2 bg-green-50 text-green-700 hover:bg-green-700 hover:text-white rounded-xl text-xs font-bold transition flex items-center gap-1 border border-green-250">
                            Download File
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Universal Document Preview Modal -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closePreviewModal">
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
import { ref } from 'vue';

const props = defineProps({
    enrollments: Array,
    learningContents: Array,
});

// Preview state
const previewModalOpen = ref(false);
const previewTitle = ref('');
const previewFileName = ref('');
const previewMime = ref('');
const previewSrc = ref('');
const previewDownloadSrc = ref('');
const previewLoading = ref(false);

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
