<template>
    <Head :title="'Assignment: ' + assignment.service_request?.reference_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('assignments.index')" class="text-gray-500 hover:text-brand-blue transition-colors">
                    <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </Link>
                <span>Assignment Details</span>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-8 pb-12">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#1a365d] text-white rounded-2xl p-6 md:p-8 shadow-lg border-b-4 border-[#d4af37] relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 w-40 h-40 bg-white/5 rounded-full pointer-events-none"></div>
                <div class="absolute left-1/3 bottom-0 w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/20 text-[#d4af37]">
                                {{ assignment.service_request?.service_category?.replace('_', ' ') }}
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize bg-white/10 text-gray-200">
                                {{ assignment.role_in_task || 'Staff' }}
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            {{ assignment.service_request?.title || 'Language Service Task' }}
                        </h1>
                        <p class="text-gray-300 text-sm mt-1">
                            Reference: <span class="font-mono text-white">{{ assignment.service_request?.reference_number }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-4 py-2 rounded-xl text-sm font-semibold capitalize shadow-inner flex items-center gap-2"
                            :class="{
                                'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30': assignment.status === 'assigned' || assignment.status === 'pending',
                                'bg-blue-500/20 text-blue-300 border border-blue-500/30': assignment.status === 'accepted' || assignment.status === 'in_progress',
                                'bg-green-500/20 text-green-300 border border-green-500/30': assignment.status === 'completed',
                            }">
                            <span class="w-2.5 h-2.5 rounded-full" 
                                  :class="{
                                      'bg-yellow-400 animate-pulse': assignment.status === 'assigned' || assignment.status === 'pending',
                                      'bg-blue-400 animate-pulse': assignment.status === 'accepted' || assignment.status === 'in_progress',
                                      'bg-green-400': assignment.status === 'completed',
                                  }"></span>
                            {{ assignment.status?.replace('_', ' ') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Details Grid (2 Cols) -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Project Details Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Service Request Description
                        </h2>
                        
                        <div class="text-gray-700 whitespace-pre-line text-sm leading-relaxed">
                            {{ assignment.service_request?.description }}
                        </div>

                        <!-- Technical Meta Fields -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-100 text-xs">
                            <div>
                                <span class="block text-gray-400 uppercase tracking-wider font-semibold">Source Language</span>
                                <span class="text-gray-900 font-medium text-sm mt-0.5 block">{{ assignment.service_request?.source_language || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-400 uppercase tracking-wider font-semibold">Target Language</span>
                                <span class="text-gray-900 font-medium text-sm mt-0.5 block">{{ assignment.service_request?.target_language || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-400 uppercase tracking-wider font-semibold">Priority</span>
                                <span class="px-2.5 py-0.5 rounded-full inline-block font-semibold capitalize mt-0.5"
                                    :class="{
                                        'bg-red-100 text-red-800': assignment.service_request?.priority === 'urgent' || assignment.service_request?.priority === 'high',
                                        'bg-blue-100 text-blue-800': assignment.service_request?.priority === 'medium',
                                        'bg-gray-100 text-gray-700': assignment.service_request?.priority === 'low'
                                    }">
                                    {{ assignment.service_request?.priority }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Client Documents Board -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                            Client Attachments & Source Files
                        </h2>

                        <div v-if="!assignment.service_request?.documents?.length" class="text-center py-8 text-gray-400 text-sm">
                            No source files attached by the client.
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-for="doc in assignment.service_request.documents" :key="doc.id" class="flex items-center justify-between p-4 rounded-xl border border-gray-150 hover:bg-gray-50/50 transition duration-150">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-brand-blue flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate" :title="doc.filename">
                                            {{ doc.filename }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ (doc.file_size / 1024).toFixed(1) }} KB • Uploaded by client
                                        </p>
                                    </div>
                                </div>
                                <a :href="'/storage/' + doc.file_path" :download="doc.filename" class="p-2 hover:bg-gray-200 rounded-lg text-gray-600 transition" title="Download">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Submissions Board (If completed) -->
                    <div v-if="assignment.documents?.length" class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Submitted Deliverables
                        </h2>

                        <div class="space-y-4">
                            <div v-for="doc in assignment.documents" :key="doc.id" class="p-4 rounded-xl bg-green-50/30 border border-green-150 flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-green-100 text-green-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ doc.filename }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Submitted on {{ new Date(doc.created_at).toLocaleDateString() }} • {{ doc.description || 'Deliverable' }}
                                        </p>
                                    </div>
                                </div>
                                <a :href="'/storage/' + doc.file_path" :download="doc.filename" class="p-2 hover:bg-green-100 rounded-lg text-green-700 transition" title="Download">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Action Panel (1 Col) -->
                <div class="space-y-8">
                    <!-- Assignment Progress Step Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 space-y-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Execution Panel</h3>

                        <!-- State Machine Description -->
                        <div class="space-y-4">
                            <!-- State 1: Assigned / Pending -->
                            <div v-if="assignment.status === 'assigned' || assignment.status === 'pending'" class="space-y-4">
                                <div class="bg-amber-50 text-amber-800 p-4 rounded-xl text-sm border border-amber-100 leading-relaxed">
                                    This task has been assigned to you. Click <strong>Start Task</strong> to change the status to "In Progress" and begin work.
                                </div>
                                <button @click="startWork" :disabled="submitting" class="w-full bg-[#0a1f44] text-white hover:bg-[#152a4d] disabled:opacity-50 px-5 py-3 rounded-xl font-bold transition shadow-sm flex items-center justify-center gap-2">
                                    <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <span>Accept & Start Task</span>
                                </button>
                            </div>

                            <!-- State 2: In Progress -->
                            <div v-else-if="assignment.status === 'in_progress' || assignment.status === 'accepted'" class="space-y-4">
                                <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm border border-blue-100 leading-relaxed">
                                    You are currently working on this task. Once you have finished, upload the final files and submit them for Director review.
                                </div>

                                <button @click="showUploadModal = true" class="w-full bg-green-600 text-white hover:bg-green-700 px-5 py-3 rounded-xl font-bold transition shadow-sm flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Submit Final Deliverable
                                </button>
                            </div>

                            <!-- State 3: Completed -->
                            <div v-else-if="assignment.status === 'completed'" class="space-y-4">
                                <div class="bg-green-50 text-green-800 p-4 rounded-xl text-sm border border-green-100 leading-relaxed flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <strong>Task Completed!</strong>
                                        <p class="text-xs mt-1 text-green-700 leading-snug">
                                            The deliverables have been uploaded and forwarded to the directors for approval and delivery to the client.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Information Sidebar Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 space-y-4 text-sm text-gray-600">
                        <h4 class="font-bold text-gray-900 border-b border-gray-100 pb-2">Task Details</h4>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Assigned By</span>
                                <span class="font-semibold text-gray-900">{{ assignment.assigned_by?.name || 'Director' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Deadline</span>
                                <span class="font-semibold text-gray-900">
                                    {{ assignment.service_request?.deadline ? new Date(assignment.service_request.deadline).toLocaleDateString() : 'None' }}
                                </span>
                            </div>
                            <div class="flex justify-between" v-if="assignment.started_at">
                                <span class="text-gray-400">Started At</span>
                                <span class="font-semibold text-gray-900">
                                    {{ new Date(assignment.started_at).toLocaleDateString() }}
                                </span>
                            </div>
                            <div class="flex justify-between" v-if="assignment.completed_at">
                                <span class="text-gray-400">Completed At</span>
                                <span class="font-semibold text-gray-900">
                                    {{ new Date(assignment.completed_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Glassmorphic Submission Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-gray-150 p-6 space-y-6 transform scale-100 transition-transform">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-900">Submit Task Deliverables</h3>
                    <button @click="showUploadModal = false" class="p-1 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitDeliverables" class="space-y-6">
                    <!-- File Drag and Drop / Input -->
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">Finished Document/File <span class="text-red-500">*</span></label>
                        <div class="relative border-2 border-dashed border-gray-300 hover:border-brand-blue rounded-xl p-6 text-center cursor-pointer transition-colors"
                             :class="{ 'border-brand-blue bg-blue-50/10': isDragOver }"
                             @dragover.prevent="isDragOver = true"
                             @dragleave="isDragOver = false"
                             @drop.prevent="handleFileDrop">
                            <input type="file" ref="fileInput" @change="handleFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            
                            <div class="space-y-2">
                                <svg class="w-10 h-10 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <div class="text-sm font-semibold text-gray-700">
                                    {{ selectedFile ? selectedFile.name : 'Click to upload or drag & drop file' }}
                                </div>
                                <div class="text-xs text-gray-400" v-if="!selectedFile">
                                    Supported file types: PDF, DOC, DOCX, ZIP, XLS, TXT (Max 10MB)
                                </div>
                                <div class="text-xs text-brand-blue font-semibold" v-else>
                                    {{ (selectedFile.size / 1024).toFixed(1) }} KB • Ready to submit
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.file" class="text-red-500 text-xs font-semibold">{{ form.errors.file }}</div>
                    </div>

                    <!-- Submission Notes -->
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700">Delivery Notes / Response</label>
                        <textarea v-model="form.notes" placeholder="Describe the work done or include comments for the client..." rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"></textarea>
                        <div v-if="form.errors.notes" class="text-red-500 text-xs font-semibold">{{ form.errors.notes }}</div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showUploadModal = false" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold text-sm transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing || !selectedFile" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg font-bold text-sm transition shadow-sm flex items-center gap-2">
                            <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>Upload & Complete Task</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    assignment: Object
});

const submitting = ref(false);
const showUploadModal = ref(false);
const isDragOver = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);

const form = useForm({
    file: null,
    notes: '',
});

const startWork = () => {
    submitting.value = true;
    Inertia.patch(route('assignments.update', props.assignment.id), {
        status: 'in_progress'
    }, {
        onFinish: () => {
            submitting.value = false;
        }
    });
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFile.value = file;
        form.file = file;
    }
};

const handleFileDrop = (e) => {
    isDragOver.value = false;
    const file = e.dataTransfer.files[0];
    if (file) {
        selectedFile.value = file;
        form.file = file;
    }
};

const submitDeliverables = () => {
    form.post(route('assignments.complete', props.assignment.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            selectedFile.value = null;
            form.reset();
        }
    });
};
</script>

<style scoped>
.bg-brand-blue {
    background-color: #0a1f44;
}
.border-brand-blue {
    border-color: #0a1f44;
}
.text-brand-blue {
    color: #0a1f44;
}
.bg-brand-gold {
    background-color: #d4af37;
}
.text-brand-gold {
    color: #d4af37;
}
</style>
