<template>
    <AuthenticatedLayout>
        <template #header>
            Assigned Course Enrollments
        </template>

        <div class="space-y-8">
            <!-- Header Summary Row -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] p-6 rounded-2xl border border-gray-150 shadow-md flex flex-col md:flex-row md:justify-between md:items-center gap-4 text-white">
                <div class="space-y-1">
                    <span class="inline-block py-0.5 px-3 rounded-full bg-white/10 text-brand-gold text-xs font-bold tracking-widest uppercase mb-1">
                        Lecturer Dashboard
                    </span>
                    <h3 class="text-xl font-bold">Assigned Course Allocations</h3>
                    <p class="text-sm text-gray-300">View enrolled students separated cleanly by course cohort. Maintain class lists and export registers.</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-white/15 px-5 py-2.5 rounded-2xl backdrop-blur-sm text-center">
                        <span class="text-[0.65rem] text-gray-300 font-bold uppercase tracking-wider block">Allocated Courses</span>
                        <span class="text-xl font-extrabold text-brand-gold">{{ stats.total_courses }}</span>
                    </div>
                    <div class="bg-white/15 px-5 py-2.5 rounded-2xl backdrop-blur-sm text-center">
                        <span class="text-[0.65rem] text-gray-300 font-bold uppercase tracking-wider block">Total Students</span>
                        <span class="text-xl font-extrabold text-brand-gold">{{ stats.total_students }}</span>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-col md:flex-row md:items-center gap-4 flex-1">
                    <div class="w-full md:w-72">
                        <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Keyword Search</label>
                        <input v-model="searchQuery" type="text" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Search student name or email..." />
                    </div>
                    <div class="w-full md:w-64">
                        <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Filter by Course Session</label>
                        <select v-model="selectedIntake" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="all">All Assigned Intakes</option>
                            <option v-for="item in intakes" :key="item.id" :value="item.id">{{ item.name }} ({{ item.course.code }})</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredGroupedEnrollments.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Matching Enrolled Students Found</h4>
                <p class="text-xs text-gray-400 mt-1">Verify that you are currently assigned as the instructor of scheduled intakes or try relaxing search keywords.</p>
            </div>

            <!-- Grouped Enrollments Tables -->
            <div v-else class="space-y-8">
                <div v-for="group in filteredGroupedEnrollments" :key="group.intake_id" class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <span class="px-2.5 py-0.5 text-[0.65rem] font-bold bg-[#0a1f44] text-white rounded-full uppercase tracking-wider">
                                {{ group.course_code }}
                            </span>
                            <h3 class="text-lg font-black text-brand-blue mt-1 leading-snug">{{ group.course_title }}</h3>
                            <p class="text-xs text-brand-gold-dark font-semibold mt-0.5">{{ group.intake_name }} | Students: {{ group.student_count }}</p>
                        </div>
                        <a :href="route('instructor.enrollments.export', group.intake_id)" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-4 py-2 rounded-full transition shadow text-xs flex items-center gap-1.5 self-start sm:self-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export Class List
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                    <th class="px-6 py-4">Student Name</th>
                                    <th class="px-6 py-4">Email Address</th>
                                    <th class="px-6 py-4">Phone Number</th>
                                    <th class="px-6 py-4">Enrolled Date</th>
                                    <th class="px-6 py-4">Registration Status</th>
                                    <th class="px-6 py-4">Access Period</th>
                                    <th class="px-6 py-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                <tr v-for="student in group.students" :key="student.student_id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ student.name }}</td>
                                    <td class="px-6 py-4 text-gray-600 font-medium">{{ student.email }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ student.phone || 'N/A' }}</td>
                                    <td class="px-6 py-4 text-gray-400">{{ student.enrolled_at }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-green-50 text-green-700': student.status === 'active',
                                                'bg-blue-50 text-blue-700': student.status === 'completed',
                                                'bg-amber-50 text-amber-700': student.status === 'pending',
                                                'bg-red-50 text-red-700': student.status === 'dropped'
                                            }">
                                            {{ student.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium">
                                        <span v-if="student.access_until" class="text-brand-gold-dark font-bold">
                                            Extended to: {{ formatDate(student.access_until) }}
                                        </span>
                                        <span v-else class="text-gray-550">
                                            Ends: {{ formatDate(student.original_end_date) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button v-if="student.status === 'active'" @click="openExtendModal(student)" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white text-[10px] font-black uppercase tracking-wider px-3.5 py-1.5 rounded-full transition shadow">
                                            Extend Access
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="group.students.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                                        No matching student records found in this course intake.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Extend Access Modal -->
            <div v-if="extendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeExtendModal">
                <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4 text-xs">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="text-sm font-black text-[#0a1f44] uppercase tracking-wider">Extend Student Access Period</h3>
                        <p class="text-[10px] text-gray-500 mt-0.5">Extend learning access for {{ selectedStudent?.name }}</p>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">New Completion / Expiry Date *</label>
                            <input v-model="extendForm.new_expiry_date" type="date" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Reason for Extension</label>
                            <textarea v-model="extendForm.reason" rows="3" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Student requested more time to complete assignment 3 due to medical reasons."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="closeExtendModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">Cancel</button>
                        <button @click="submitExtension" :disabled="submitting || !extendForm.new_expiry_date" class="px-4 py-2 bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold rounded-xl transition disabled:opacity-50">Extend Access</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    groupedEnrollments: Array,
    intakes: Array,
    stats: Object,
});

const searchQuery = ref('');
const selectedIntake = ref('all');

const filteredGroupedEnrollments = computed(() => {
    return props.groupedEnrollments.map(group => {
        // Filter students within the group
        const filteredStudents = group.students.filter(student => {
            const matchesSearch = student.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                student.email.toLowerCase().includes(searchQuery.value.toLowerCase());
            return matchesSearch;
        });

        return {
            ...group,
            students: filteredStudents,
            student_count: filteredStudents.length,
        };
    }).filter(group => {
        // Filter groups by intake selector
        const matchesIntake = selectedIntake.value === 'all' || group.intake_id === Number(selectedIntake.value);
        return matchesIntake && group.students.length > 0;
    });
});

// Access Extension Modal State & Submission
const extendModalOpen = ref(false);
const selectedStudent = ref(null);
const submitting = ref(false);

const extendForm = reactive({
    new_expiry_date: '',
    reason: '',
});

const openExtendModal = (student) => {
    selectedStudent.value = student;
    extendForm.new_expiry_date = student.access_until || student.original_end_date;
    extendForm.reason = '';
    extendModalOpen.value = true;
};

const closeExtendModal = () => {
    extendModalOpen.value = false;
    selectedStudent.value = null;
};

const submitExtension = () => {
    submitting.value = true;
    Inertia.post(route('instructor.enrollments.extend', selectedStudent.value.enrollment_id), extendForm, {
        onBefore: () => { submitting.value = true; },
        onSuccess: () => {
            closeExtendModal();
            submitting.value = false;
        },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

const formatDate = (dateString) => {
    if (!dateString || dateString === 'N/A') return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
