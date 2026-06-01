<template>
    <AuthenticatedLayout>
        <template #header>
            Class Timetable Manager
        </template>

        <div class="space-y-8">
            <!-- Header Banner -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Timetables &amp; Session Schedules</h3>
                    <p class="text-sm text-gray-500">Plan and schedule class sessions. Timetable changes instantly notify enrolled students and update their portals.</p>
                </div>
                <button @click="openCreateModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Schedule a Class
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="timetables.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Timetable Sessions Scheduled Yet</h4>
                <p class="text-xs text-gray-400 mt-1">Get started by clicking the "Schedule a Class" button to allocate class slots.</p>
            </div>

            <!-- Timetable List Table -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                <th class="px-6 py-4">Course Session</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Time Duration</th>
                                <th class="px-6 py-4">Venue / Link</th>
                                <th class="px-6 py-4">Special Notes</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <tr v-for="item in timetables" :key="item.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-bold text-gray-800">
                                    <p>{{ item.intake.course.title }}</p>
                                    <span class="text-[9px] text-brand-gold-dark uppercase tracking-wider font-semibold block mt-0.5">{{ item.intake.name }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-650">{{ formatDate(item.date) }}</td>
                                <td class="px-6 py-4 text-gray-600 font-semibold">{{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</td>
                                <td class="px-6 py-4 max-w-xs truncate">
                                    <a v-if="isLink(item.venue)" :href="item.venue" target="_blank" class="text-blue-600 font-semibold hover:underline">
                                        Online (Click to Join)
                                    </a>
                                    <span v-else class="text-gray-800">{{ item.venue }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-450 italic max-w-xs truncate" :title="item.notes">{{ item.notes || '-' }}</td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <button @click="openEditModal(item)" class="text-blue-600 hover:text-blue-800 font-bold transition">Edit</button>
                                    <button @click="deleteItem(item.id)" class="text-rose-600 hover:text-rose-800 font-bold transition">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Schedule/Edit Class Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-150">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">{{ editMode ? 'Edit Timetable Schedule' : 'Schedule a New Class' }}</h3>
                        <p class="text-[9px] text-[#f5c242] mt-0.5">MSU National Language Institute Portal</p>
                    </div>
                    <button @click="closeModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Course Intake -->
                    <div v-if="!editMode">
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Assigned Course Session *</label>
                        <select v-model="form.course_intake_id" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="">Select Cohort Intake...</option>
                            <option v-for="item in intakes" :key="item.id" :value="item.id">{{ item.course.title }} ({{ item.name }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Date -->
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Date *</label>
                            <input v-model="form.date" type="date" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                        </div>

                        <!-- Start Time -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Start Time *</label>
                            <input v-model="form.start_time" type="time" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">End Time *</label>
                            <input v-model="form.end_time" type="time" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                        </div>
                    </div>

                    <!-- Venue -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Venue or Online Session Link *</label>
                        <input v-model="form.venue" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Language Lab G3 or Google Meet link" />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Class Notes (Optional)</label>
                        <textarea v-model="form.notes" rows="3" class="w-full text-xs rounded-xl border-gray-350 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Instructions, requirements etc..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs disabled:opacity-50">
                            {{ submitting ? 'Saving Schedule...' : 'Save Class Slot' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    timetables: Array,
    intakes: Array,
});

const modalOpen = ref(false);
const editMode = ref(false);
const submitting = ref(false);
const selectedId = ref(null);

const form = reactive({
    course_intake_id: '',
    date: '',
    start_time: '',
    end_time: '',
    venue: '',
    notes: '',
});

const isLink = (str) => {
    if (!str) return false;
    return str.startsWith('http://') || str.startsWith('https://') || str.startsWith('www.');
};

const openCreateModal = () => {
    editMode.value = false;
    modalOpen.value = true;
};

const openEditModal = (item) => {
    editMode.value = true;
    selectedId.value = item.id;
    form.course_intake_id = item.course_intake_id;
    form.date = item.date.split('T')[0];
    form.start_time = item.start_time;
    form.end_time = item.end_time;
    form.venue = item.venue;
    form.notes = item.notes;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editMode.value = false;
    selectedId.value = null;
    form.course_intake_id = '';
    form.date = '';
    form.start_time = '';
    form.end_time = '';
    form.venue = '';
    form.notes = '';
};

const submitForm = () => {
    submitting.value = true;

    if (editMode.value) {
        Inertia.put(route('instructor.timetable.update', selectedId.value), form, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
            },
            onError: () => {
                submitting.value = false;
            }
        });
    } else {
        Inertia.post(route('instructor.timetable.store'), form, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
            },
            onError: () => {
                submitting.value = false;
            }
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this class timetable slot? Enrolled students will be notified.')) {
        Inertia.delete(route('instructor.timetable.destroy', id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    const parts = timeString.split(':');
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`;
    }
    return timeString;
};
</script>
