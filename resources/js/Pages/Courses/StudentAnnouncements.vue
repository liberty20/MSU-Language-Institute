<template>
    <Head title="Course Announcements" />

    <AuthenticatedLayout>
        <template #header>
            Course Announcements
        </template>

        <div class="space-y-6">
            <!-- Summary Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">Student Desk</span>
                    <h3 class="text-2xl font-black leading-tight">Course Announcements</h3>
                    <p class="text-sm text-gray-300 max-w-xl">
                        Keep up with class schedules, study requirements, and messages published by your instructors for your enrolled courses.
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
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredAnnouncements.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
                <svg class="w-16 h-16 text-gray-350 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h4 class="font-bold text-brand-blue text-lg">No Announcements Found</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">You do not have any announcements for your enrolled courses yet.</p>
            </div>

            <!-- Announcements Feed -->
            <div v-else class="space-y-4">
                <div v-for="announcement in filteredAnnouncements" :key="announcement.id" 
                     @click="viewAnnouncement(announcement)"
                     :class="[
                         'bg-white rounded-2xl border transition p-6 cursor-pointer flex flex-col md:flex-row md:items-start justify-between gap-4 relative overflow-hidden',
                         !announcement.is_read 
                             ? 'border-l-4 border-l-[#f5c242] border-gray-250 shadow-md ring-1 ring-amber-500/10' 
                             : 'border-gray-150 hover:border-gray-300 shadow-sm hover:shadow-md'
                     ]">
                    
                    <div class="space-y-3 flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold bg-[#0a1f44]/10 text-[#0a1f44] rounded-full uppercase tracking-wider">
                                {{ announcement.course_name }}
                            </span>
                            <span v-if="!announcement.is_read" class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-[#f5c242] text-[#0a1f44] rounded-md animate-pulse">
                                NEW
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base font-black text-brand-blue leading-snug line-clamp-1" :title="announcement.title">
                                {{ announcement.title }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-400 font-bold">
                                <span>Instructor: {{ announcement.instructor_name }}</span>
                                <span>•</span>
                                <span>Published: {{ formatDate(announcement.published_at) }}</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed line-clamp-3 whitespace-pre-line">
                            {{ announcement.content }}
                        </p>
                    </div>

                    <!-- Side Attachment Indicator -->
                    <div v-if="announcement.attachment_path" class="flex-shrink-0 self-start md:self-center flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-xs font-bold text-gray-600">
                        <svg class="w-4 h-4 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate max-w-[120px]" :title="announcement.attachment_filename">{{ announcement.attachment_filename }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcement Details Modal -->
        <div v-if="detailsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-hidden" @click.self="closeDetailsModal">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden border border-gray-150 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Announcement Details</h4>
                    <button type="button" class="btn-close" @click="closeDetailsModal" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <div class="border-b border-gray-100 pb-4">
                        <span class="px-2.5 py-0.5 text-[10px] font-bold bg-[#0a1f44]/10 text-[#0a1f44] rounded-full uppercase tracking-wider">
                            {{ selectedAnnouncement?.course_name }}
                        </span>
                        <h2 class="text-lg font-black text-brand-blue mt-2 leading-snug">
                            {{ selectedAnnouncement?.title }}
                        </h2>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 font-bold">
                            <span>By: {{ selectedAnnouncement?.instructor_name }}</span>
                            <span>•</span>
                            <span>Published: {{ formatDate(selectedAnnouncement?.published_at) }}</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ selectedAnnouncement?.content }}
                    </div>

                    <!-- Attachment -->
                    <div v-if="selectedAnnouncement?.attachment_path" class="pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Attachment</h4>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-[#0a1f44]/5 flex items-center justify-center text-brand-blue">
                                    <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-gray-700 truncate" :title="selectedAnnouncement?.attachment_filename">
                                        {{ selectedAnnouncement?.attachment_filename }}
                                    </p>
                                </div>
                            </div>
                            <a :href="route('files.download', { type: 'announcement', id: selectedAnnouncement?.id })" class="px-4 py-2 bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] rounded-xl text-xs font-black transition shadow-sm">
                                Download
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-gray-150 bg-gray-50 flex justify-end flex-shrink-0">
                    <button type="button" @click="closeDetailsModal" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import axios from 'axios';

const props = defineProps({
    announcements: Array,
    courses: Array,
});

const page = usePage();

// Search and filters
const searchQuery = ref('');
const filterCourse = ref('');

const filteredAnnouncements = computed(() => {
    return props.announcements.filter(item => {
        const matchesSearch = item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              item.content.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesCourse = filterCourse.value === '' || item.course_id === parseInt(filterCourse.value);
        return matchesSearch && matchesCourse;
    });
});

// Modal state
const detailsModalOpen = ref(false);
const selectedAnnouncement = ref(null);

// Disable body scrolling when modal is open
watch(detailsModalOpen, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const viewAnnouncement = (announcement) => {
    selectedAnnouncement.value = announcement;
    detailsModalOpen.value = true;
    
    if (!announcement.is_read) {
        axios.post(route('student.announcements.read', announcement.id))
            .then(() => {
                announcement.is_read = true;
                // Decrement the global unread count
                if (page.props.value.unreadAnnouncementsCount > 0) {
                    page.props.value.unreadAnnouncementsCount--;
                }
            })
            .catch(err => console.error("Error marking announcement as read", err));
    }
};

const closeDetailsModal = () => {
    detailsModalOpen.value = false;
    selectedAnnouncement.value = null;
    document.body.style.overflow = '';
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
