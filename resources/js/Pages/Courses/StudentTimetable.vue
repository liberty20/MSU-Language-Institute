<template>
    <AuthenticatedLayout>
        <template #header>
            My Class Timetable
        </template>

        <div class="space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Scheduled Intakes &amp; Cohort Timetables</h3>
                    <p class="text-sm text-gray-500">Timetable changes will update automatically in real-time as lecturers make adjustments.</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="timetables.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Scheduled Timetable Entries Found</h4>
                <p class="text-xs text-gray-400 mt-1">Please confirm with your course instructor if timetable sessions have been assigned to your batch yet.</p>
            </div>

            <!-- Timetable List Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in timetables" :key="item.id" class="bg-white rounded-2xl border border-gray-150 overflow-hidden flex flex-col shadow-sm hover:shadow-md transition">
                    <div class="bg-[#0a1f44] text-white px-5 py-4 border-b border-[#f5c242]/20 flex justify-between items-center">
                        <span class="px-2.5 py-0.5 text-[0.65rem] font-bold bg-[#f5c242] text-[#0a1f44] rounded-full uppercase tracking-wider">
                            {{ item.intake.course.code }}
                        </span>
                        <span class="text-xs font-semibold text-gray-300">Class Intake</span>
                    </div>

                    <div class="p-6 flex-grow space-y-4">
                        <div>
                            <h4 class="text-base font-black text-brand-blue leading-snug line-clamp-2" :title="item.intake.course.title">{{ item.intake.course.title }}</h4>
                            <p class="text-[10px] text-brand-gold-dark font-bold uppercase tracking-wider mt-1">{{ item.intake.name }}</p>
                        </div>

                        <div class="space-y-2 text-xs text-gray-650 font-medium">
                            <div class="flex items-center gap-2.5 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <svg class="w-4 h-4 text-brand-blue-mid flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <div>
                                    <span class="text-[9px] text-gray-400 font-bold block uppercase leading-none">Session Date</span>
                                    <span class="text-gray-800 font-semibold">{{ formatDate(item.date) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <svg class="w-4 h-4 text-brand-blue-mid flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <span class="text-[9px] text-gray-400 font-bold block uppercase leading-none">Class Time</span>
                                    <span class="text-gray-800 font-semibold">{{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <svg class="w-4 h-4 text-brand-blue-mid flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <div>
                                    <span class="text-[9px] text-gray-400 font-bold block uppercase leading-none">Venue / Platform</span>
                                    <a v-if="isLink(item.venue)" :href="item.venue" target="_blank" class="text-blue-600 font-bold hover:underline line-clamp-1">
                                        Join Online Session &rarr;
                                    </a>
                                    <span v-else class="text-gray-850 font-bold leading-tight block">{{ item.venue }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <svg class="w-4 h-4 text-brand-blue-mid flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div>
                                    <span class="text-[9px] text-gray-400 font-bold block uppercase leading-none">Lecturer / Instructor</span>
                                    <span class="text-gray-800 font-semibold">{{ item.intake.instructor ? item.intake.instructor.name : 'TBA' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Special Notes -->
                        <div v-if="item.notes" class="bg-amber-50/50 border border-amber-200/50 p-3 rounded-xl text-[11px] text-gray-600 leading-relaxed italic">
                            <strong>Note:</strong> {{ item.notes }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    timetables: Array,
});

const isLink = (str) => {
    if (!str) return false;
    return str.startsWith('http://') || str.startsWith('https://') || str.startsWith('www.');
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    // Remove seconds if present
    const parts = timeString.split(':');
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`;
    }
    return timeString;
};
</script>
