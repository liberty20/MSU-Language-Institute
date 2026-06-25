<template>
    <Head title="Announcements Manager" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span class="font-extrabold text-[#0a1f44]">Announcements Manager</span>
                <button @click="openCreateModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black text-xs px-4 py-2.5 rounded-full shadow transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Announcement
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">Instructor Console</span>
                    <h3 class="text-2xl font-black leading-tight">Course Announcements</h3>
                    <p class="text-sm text-gray-300 max-w-xl">
                        Broadcast important updates, schedule changes, or materials to enrolled students. You can save announcements as drafts or publish them immediately to their feeds.
                    </p>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-1 flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input v-model="searchQuery" type="text" placeholder="Search announcements..." class="pl-10 w-full text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm" />
                    </div>
                    <select v-model="filterCourse" class="text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm">
                        <option value="">All Courses</option>
                        <option v-for="course in courses" :key="course.id" :value="course.id">
                            {{ course.code }} - {{ course.title }}
                        </option>
                    </select>
                    <select v-model="filterStatus" class="text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredAnnouncements.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <svg class="w-16 h-16 text-gray-350 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h4 class="font-bold text-brand-blue text-lg">No Announcements Found</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">Create a new announcement to communicate with students enrolled in your courses.</p>
                <button @click="openCreateModal" class="mt-4 bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black text-xs px-5 py-2.5 rounded-full transition shadow">
                    Create First Announcement
                </button>
            </div>

            <!-- Announcements Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="announcement in filteredAnnouncements" :key="announcement.id" class="bg-white rounded-2xl border border-gray-150 overflow-hidden flex flex-col shadow-sm hover:shadow-md transition">
                    <div class="p-6 flex-grow space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold bg-[#0a1f44]/10 text-[#0a1f44] rounded-full uppercase tracking-wider">
                                {{ announcement.course_name }}
                            </span>
                            <span :class="[
                                'px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md',
                                announcement.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'
                            ]">
                                {{ announcement.status }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base font-black text-brand-blue leading-snug line-clamp-1" :title="announcement.title">{{ announcement.title }}</h3>
                            <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-400">
                                <span>Posted: {{ formatDate(announcement.created_at) }}</span>
                                <span v-if="announcement.published_at">• Published: {{ formatDate(announcement.published_at) }}</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed line-clamp-4 whitespace-pre-line min-h-[5.5rem]">
                            {{ announcement.content }}
                        </p>

                        <!-- Attachment preview block -->
                        <div v-if="announcement.attachment_path" class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 text-[#0a1f44] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="font-bold text-gray-700 truncate" :title="announcement.attachment_filename">{{ announcement.attachment_filename }}</span>
                            </div>
                            <a :href="route('files.download', { type: 'announcement', id: announcement.id })" class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-extrabold flex-shrink-0 transition">
                                Download
                            </a>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button v-if="announcement.status === 'draft'" @click="publishAnnouncement(announcement.id)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                Publish Now
                            </button>
                            <span v-else class="text-xs text-gray-400 font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Active Announcement
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openEditModal(announcement)" class="p-1.5 hover:bg-amber-100 text-amber-700 rounded-lg transition" title="Edit Announcement">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button @click="deleteAnnouncement(announcement.id)" class="p-1.5 hover:bg-red-100 text-red-700 rounded-lg transition" title="Delete Announcement">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcement Create / Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-hidden" @click.self="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-150 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">{{ isEdit ? 'Edit Announcement' : 'Create New Announcement' }}</h4>
                    <button type="button" class="btn-close" @click="closeModal" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="flex flex-col flex-1 overflow-hidden">
                    <!-- Scrollable Modal Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-xs font-semibold">
                            {{ formError }}
                        </div>

                        <!-- Course Selection -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Target Course *</label>
                            <select v-model="form.course_id" required class="w-full text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm">
                                <option value="">Select Target Course...</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.code }} - {{ course.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Announcement Title *</label>
                            <input v-model="form.title" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm" placeholder="e.g. Swahili Class Rescheduled to Thursday" />
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Message Content *</label>
                            <textarea v-model="form.content" rows="5" required class="w-full text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm resize-none" placeholder="Write announcement message here..."></textarea>
                        </div>

                        <!-- Publish Date (only show if status is published) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Status *</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer">
                                    <input type="radio" v-model="form.status" value="published" class="text-[#0a1f44] focus:ring-[#0a1f44]" />
                                    Publish Immediately
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 font-semibold cursor-pointer">
                                    <input type="radio" v-model="form.status" value="draft" class="text-[#0a1f44] focus:ring-[#0a1f44]" />
                                    Save as Draft
                                </label>
                            </div>
                        </div>

                        <div v-if="form.status === 'published'">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Publish Date &amp; Time (Optional)</label>
                            <input v-model="form.publish_date" type="datetime-local" class="w-full text-xs rounded-xl border-gray-300 focus:border-[#0a1f44] focus:ring-[#0a1f44] shadow-sm" />
                            <span class="text-[10px] text-gray-400 block mt-1">Defaults to current time if left empty.</span>
                        </div>

                        <!-- Attachment -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Attachment (Optional)</label>
                            <div v-if="isEdit && form.existing_attachment" class="bg-gray-50 p-2.5 rounded-lg border border-gray-150 flex items-center justify-between text-xs mb-2">
                                <span class="font-bold text-gray-600 truncate max-w-[200px]" :title="form.existing_attachment">{{ form.existing_attachment }}</span>
                                <label class="flex items-center gap-1 text-xs text-red-600 font-bold cursor-pointer">
                                    <input type="checkbox" v-model="form.delete_attachment" class="rounded text-red-600 focus:ring-red-500" />
                                    Delete file
                                </label>
                            </div>
                            <input type="file" @change="handleFileUpload" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/20" />
                            <span class="text-[10px] text-gray-400 block mt-1.5">Supported formats: PDF, DOC, DOCX, PNG, JPG (Max 10MB)</span>
                        </div>
                    </div>

                    <!-- Modal Sticky Footer -->
                    <div class="p-6 border-t border-gray-150 bg-gray-50 flex justify-end gap-3 flex-shrink-0">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="processing" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-xs disabled:opacity-50">
                            {{ processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Create Announcement') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { ref, computed, reactive, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    announcements: Array,
    courses: Array,
});

// Search and filters
const searchQuery = ref('');
const filterCourse = ref('');
const filterStatus = ref('');

const filteredAnnouncements = computed(() => {
    return props.announcements.filter(item => {
        const matchesSearch = item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              item.content.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesCourse = filterCourse.value === '' || item.course_id === parseInt(filterCourse.value);
        const matchesStatus = filterStatus.value === '' || item.status === filterStatus.value;
        return matchesSearch && matchesCourse && matchesStatus;
    });
});

// Form state
const modalOpen = ref(false);
const isEdit = ref(false);
const processing = ref(false);
const formError = ref('');
const fileUpload = ref(null);
const editId = ref(null);

const form = reactive({
    course_id: '',
    title: '',
    content: '',
    status: 'published',
    publish_date: '',
    existing_attachment: null,
    delete_attachment: false,
});

// Disable body scrolling when modal is open
watch(modalOpen, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const openCreateModal = () => {
    isEdit.value = false;
    editId.value = null;
    formError.value = '';
    fileUpload.value = null;
    
    form.course_id = '';
    form.title = '';
    form.content = '';
    form.status = 'published';
    form.publish_date = '';
    form.existing_attachment = null;
    form.delete_attachment = false;

    modalOpen.value = true;
};

const openEditModal = (announcement) => {
    isEdit.value = true;
    editId.value = announcement.id;
    formError.value = '';
    fileUpload.value = null;

    form.course_id = announcement.course_id;
    form.title = announcement.title;
    form.content = announcement.content;
    form.status = announcement.status;
    form.publish_date = announcement.published_at ? announcement.published_at.substring(0, 16) : '';
    form.existing_attachment = announcement.attachment_filename;
    form.delete_attachment = false;

    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    document.body.style.overflow = '';
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        formError.value = 'Validation Error: File size exceeds the maximum 10MB limit.';
        e.target.value = '';
        fileUpload.value = null;
        return;
    }
    fileUpload.value = file;
    formError.value = '';
};

const submitForm = () => {
    if (!form.course_id) {
        formError.value = 'Target course is required.';
        return;
    }
    if (!form.title) {
        formError.value = 'Title is required.';
        return;
    }
    if (!form.content) {
        formError.value = 'Content is required.';
        return;
    }

    processing.value = true;
    formError.value = '';

    const formData = new FormData();
    formData.append('course_id', form.course_id);
    formData.append('title', form.title);
    formData.append('content', form.content);
    formData.append('status', form.status);
    if (form.publish_date) {
        formData.append('publish_date', form.publish_date);
    }
    if (fileUpload.value) {
        formData.append('attachment', fileUpload.value);
    }

    if (isEdit.value) {
        formData.append('_method', 'PUT');
        formData.append('delete_attachment', form.delete_attachment ? '1' : '0');

        Inertia.post(route('instructor.announcements.update', editId.value), formData, {
            onSuccess: () => {
                processing.value = false;
                closeModal();
            },
            onError: (err) => {
                processing.value = false;
                formError.value = Object.values(err).join(' ');
            }
        });
    } else {
        Inertia.post(route('instructor.announcements.store'), formData, {
            onSuccess: () => {
                processing.value = false;
                closeModal();
            },
            onError: (err) => {
                processing.value = false;
                formError.value = Object.values(err).join(' ');
            }
        });
    }
};

const publishAnnouncement = (id) => {
    if (confirm('Are you sure you want to publish this announcement now?')) {
        Inertia.post(route('instructor.announcements.publish', id));
    }
};

const deleteAnnouncement = (id) => {
    if (confirm('Are you sure you want to permanently delete this announcement?')) {
        Inertia.delete(route('instructor.announcements.destroy', id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background-color: #0A2A66; /* MSUNLI Dark Blue */
    color: #FFFFFF;
    border-bottom: none;
    position: sticky;
    top: 0;
    z-index: 1055;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #FFFFFF;
    letter-spacing: 0.3px;
}

.modal-header .btn-close {
    background: transparent;
    border: none;
    color: #FFFFFF;
    cursor: pointer;
    padding: 0.5rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.85;
    transition: opacity 0.15s ease-in-out;
}
.modal-header .btn-close:hover {
    opacity: 1;
}
</style>
