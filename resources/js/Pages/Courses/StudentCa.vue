<template>
    <AuthenticatedLayout>
        <template #header>
            Continuous Assessment Marks
        </template>

        <div class="space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Continuous Assessment Reports</h3>
                    <p class="text-sm text-gray-500">Track your grades, test history, instructor feedback, and export your formal academic assessment record.</p>
                </div>
                <a :href="route('student.ca.download')" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download CA Report
                </a>
            </div>

            <!-- Empty State -->
            <div v-if="courseData.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Continuous Assessment Records Available</h4>
                <p class="text-xs text-gray-400 mt-1">Assessment marks will become available once you have active enrollments and assignments are graded by your instructor.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Course Selector Sidebar -->
                <aside class="lg:col-span-1 space-y-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-4">
                        <h4 class="font-bold text-brand-blue text-xs uppercase tracking-wider border-b border-gray-100 pb-2">Select Course</h4>
                        <div class="flex flex-col gap-2">
                            <button v-for="(course, idx) in courseData" :key="course.enrollment_id"
                                @click="activeIdx = idx"
                                class="w-full text-left p-3.5 rounded-xl border text-xs font-semibold transition"
                                :class="activeIdx === idx ? 'bg-brand-blue text-white border-brand-blue' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border-gray-250'">
                                <p class="line-clamp-1 leading-snug">{{ course.course_title }}</p>
                                <p class="text-[9px] mt-0.5" :class="activeIdx === idx ? 'text-gray-300' : 'text-gray-400'">{{ course.course_code }}</p>
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- CA Details Main Block -->
                <section class="lg:col-span-3 space-y-6" v-if="activeCourse">
                    <!-- Metrics Summary Header -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-2">
                            <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Overall Average Grade</span>
                            <div class="flex items-baseline gap-1.5">
                                <h3 class="text-3xl font-extrabold text-[#0a1f44]">{{ activeCourse.average_score }}%</h3>
                                <span class="text-xs font-bold" :class="activeCourse.average_score >= 50 ? 'text-green-600' : 'text-rose-600'">
                                    {{ activeCourse.average_score >= 50 ? 'Passing' : 'Failing' }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-2">
                            <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Course Learning Progress</span>
                            <h3 class="text-3xl font-extrabold text-[#0a1f44]">{{ activeCourse.progress }}%</h3>
                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden mt-2">
                                <div class="bg-[#0a1f44] h-full" :style="{ width: activeCourse.progress + '%' }"></div>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-2">
                            <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Course Lecturer</span>
                            <h3 class="text-lg font-black text-gray-800 line-clamp-1 leading-snug">{{ activeCourse.instructor }}</h3>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase mt-0.5">Assigned Expert</p>
                        </div>
                    </div>

                    <!-- Grading Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h4 class="font-bold text-[#0a1f44] text-sm">Assessment History Log</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-400">
                                        <th class="px-6 py-3 font-semibold">Assessment</th>
                                        <th class="px-6 py-3 font-semibold">Type</th>
                                        <th class="px-6 py-3 font-semibold">Due/Post Date</th>
                                        <th class="px-6 py-3 font-semibold">Status</th>
                                        <th class="px-6 py-3 font-semibold">Marks Obtained</th>
                                        <th class="px-6 py-3 font-semibold">Feedback Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    <tr v-for="item in activeCourse.assessment_history" :key="item.name" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-bold text-gray-800">{{ item.name }}</td>
                                        <td class="px-6 py-4 text-gray-500 font-medium">{{ item.type }}</td>
                                        <td class="px-6 py-4 text-gray-400">{{ item.due_date }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                                :class="{
                                                    'bg-green-50 text-green-700': item.status === 'graded',
                                                    'bg-blue-50 text-blue-700': item.status === 'submitted',
                                                    'bg-amber-50 text-amber-700': item.status === 'pending',
                                                    'bg-red-50 text-red-700': item.status === 'overdue'
                                                }">
                                                {{ item.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-black" :class="item.marks_obtained !== null ? 'text-blue-600' : 'text-gray-400'">
                                            {{ item.marks_obtained !== null ? `${item.marks_obtained} / ${item.max_marks}` : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-650 italic max-w-xs truncate" :title="item.feedback">
                                            {{ item.feedback || 'No comments left.' }}
                                        </td>
                                    </tr>
                                    <tr v-if="activeCourse.assessment_history.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                            No assessment entries logged for this course yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    courseData: Array,
});

const activeIdx = ref(0);

const activeCourse = computed(() => {
    if (!props.courseData || props.courseData.length === 0) return null;
    return props.courseData[activeIdx.value];
});
</script>
