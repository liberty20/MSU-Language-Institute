<template>
    <Head title="Class Attendance Register" />

    <AuthenticatedLayout>
        <template #header>
            Class Attendance Register
        </template>

        <div class="space-y-8">
            <!-- Header Summary Row -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] p-6 rounded-2xl border border-gray-150 shadow-md flex flex-col md:flex-row md:justify-between md:items-center gap-4 text-white">
                <div class="space-y-1">
                    <span class="inline-block py-0.5 px-3 rounded-full bg-white/10 text-brand-gold text-xs font-bold tracking-widest uppercase mb-1">
                        Instructor Panel
                    </span>
                    <h3 class="text-xl font-bold">Class Attendance Management</h3>
                    <p class="text-sm text-gray-300">Track and report class attendance registries. Monitor student engagement rates across your cohorts.</p>
                </div>
                
                <div class="flex gap-4" v-if="selectedIntakeId">
                    <div class="bg-white/15 px-5 py-2.5 rounded-2xl backdrop-blur-sm text-center">
                        <span class="text-[0.65rem] text-gray-300 font-bold uppercase tracking-wider block">Sessions Scheduled</span>
                        <span class="text-xl font-extrabold text-brand-gold">{{ sessions.length }}</span>
                    </div>
                    <div class="bg-white/15 px-5 py-2.5 rounded-2xl backdrop-blur-sm text-center">
                        <span class="text-[0.65rem] text-gray-300 font-bold uppercase tracking-wider block">Enrolled Students</span>
                        <span class="text-xl font-extrabold text-brand-gold">{{ students.length }}</span>
                    </div>
                </div>
            </div>

            <!-- Course Intake Selector -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="w-full sm:w-80">
                    <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Select Course Intake</label>
                    <select :value="selectedIntakeId" @change="onIntakeChange" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                        <option value="" disabled>-- Choose a course batch --</option>
                        <option v-for="item in intakes" :key="item.id" :value="item.id">
                            {{ item.course.title }} ({{ item.name }})
                        </option>
                    </select>
                </div>
                <div v-if="selectedIntakeId" class="self-end">
                    <a :href="route('instructor.attendance.export', selectedIntakeId)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-full transition shadow text-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Attendance (CSV)
                    </a>
                </div>
            </div>

            <!-- Active Management Section -->
            <div v-if="selectedIntakeId">
                <!-- Navigation Tabs -->
                <div class="flex border-b border-gray-200 mb-6 gap-2">
                    <button @click="activeTab = 'mark'" :class="[activeTab === 'mark' ? 'border-[#0a1f44] text-[#0a1f44] font-black' : 'border-transparent text-gray-400 font-bold hover:text-gray-600']" class="py-2.5 px-4 border-b-2 text-xs uppercase tracking-wider transition">
                        Mark Attendance
                    </button>
                    <button @click="activeTab = 'history'" :class="[activeTab === 'history' ? 'border-[#0a1f44] text-[#0a1f44] font-black' : 'border-transparent text-gray-400 font-bold hover:text-gray-600']" class="py-2.5 px-4 border-b-2 text-xs uppercase tracking-wider transition">
                        Attendance History &amp; Summary
                    </button>
                </div>

                <!-- TAB 1: MARK ATTENDANCE -->
                <div v-if="activeTab === 'mark'" class="space-y-6">
                    <!-- Session Selector -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm space-y-4">
                        <div class="w-full sm:w-80">
                            <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Select Class Session</label>
                            <select :value="selectedSessionId" @change="onSessionChange" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                                <option value="" disabled>-- Select a scheduled class date --</option>
                                <option v-for="sess in sessions" :key="sess.id" :value="sess.id">
                                    {{ sess.date }} ({{ formatTime(sess.start_time) }} - {{ formatTime(sess.end_time) }}) [{{ sess.venue }}]
                                </option>
                            </select>
                        </div>

                        <div v-if="!selectedSessionId" class="py-12 text-center text-gray-400 italic">
                            Select a scheduled class session above to retrieve the student roster and record/edit attendance marks.
                        </div>
                    </div>

                    <!-- Attendance Mark List -->
                    <div v-if="selectedSessionId" class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-55 flex justify-between items-center">
                            <h4 class="text-xs font-black uppercase text-brand-blue tracking-wide">Student Roll Call</h4>
                            <span class="text-[10px] bg-brand-gold/15 text-brand-gold-dark px-2 py-0.5 rounded-full font-black">RECORD MODE</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                        <th class="px-6 py-4 w-1/3">Student Details</th>
                                        <th class="px-6 py-4">Attendance Status</th>
                                        <th class="px-6 py-4">Remarks / Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800">{{ student.name }}</div>
                                            <div class="text-[10px] text-gray-400 mt-0.5">{{ student.email }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <label class="flex items-center gap-1.5 cursor-pointer font-bold">
                                                    <input type="radio" :name="'status-' + student.id" value="present" v-model="formState[student.id].status" class="text-green-600 focus:ring-green-500 border-gray-300" />
                                                    <span class="text-green-700">Present</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer font-bold">
                                                    <input type="radio" :name="'status-' + student.id" value="absent" v-model="formState[student.id].status" class="text-red-600 focus:ring-red-500 border-gray-300" />
                                                    <span class="text-red-700">Absent</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer font-bold">
                                                    <input type="radio" :name="'status-' + student.id" value="late" v-model="formState[student.id].status" class="text-amber-600 focus:ring-amber-500 border-gray-300" />
                                                    <span class="text-amber-700">Late</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer font-bold">
                                                    <input type="radio" :name="'status-' + student.id" value="excused" v-model="formState[student.id].status" class="text-blue-600 focus:ring-blue-500 border-gray-300" />
                                                    <span class="text-blue-700">Excused</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="text" v-model="formState[student.id].remarks" placeholder="Optional notes (e.g. sick leave, late bus)" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm py-1" />
                                        </td>
                                    </tr>
                                    <tr v-if="students.length === 0">
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-400 italic">No students are currently enrolled in this intake cohort.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                            <button @click="submitAttendance" :disabled="submitting || students.length === 0" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 px-6 rounded-full transition shadow flex items-center gap-1.5 disabled:opacity-50">
                                <svg v-if="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span v-if="submitting">Saving attendance registry...</span>
                                <span v-else>Save Attendance Register</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ATTENDANCE HISTORY -->
                <div v-if="activeTab === 'history'" class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-55">
                        <h4 class="text-xs font-black uppercase text-brand-blue tracking-wide">Student Performance Summaries</h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4 text-center">Total Classes</th>
                                    <th class="px-6 py-4 text-center text-green-700">Present</th>
                                    <th class="px-6 py-4 text-center text-red-700">Absent</th>
                                    <th class="px-6 py-4 text-center text-amber-700">Late</th>
                                    <th class="px-6 py-4 text-center text-blue-700">Excused</th>
                                    <th class="px-6 py-4 text-right">Attendance Rate</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                <template v-for="student in attendanceHistory" :key="student.student_id">
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800">{{ student.name }}</div>
                                            <div class="text-[10px] text-gray-400 mt-0.5">{{ student.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-gray-500">{{ student.total_classes }}</td>
                                        <td class="px-6 py-4 text-center font-extrabold text-green-700">{{ student.present }}</td>
                                        <td class="px-6 py-4 text-center font-extrabold text-red-700">{{ student.absent }}</td>
                                        <td class="px-6 py-4 text-center font-extrabold text-amber-700">{{ student.late }}</td>
                                        <td class="px-6 py-4 text-center font-extrabold text-blue-700">{{ student.excused }}</td>
                                        <td class="px-6 py-4 text-right font-black text-brand-blue">
                                            <span :class="rateBadgeClass(student.attendance_rate)" class="px-2.5 py-0.5 rounded-full text-[10px]">
                                                {{ student.attendance_rate }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button @click="toggleLogs(student.student_id)" class="text-brand-gold-dark hover:text-brand-blue font-black uppercase text-[10px] transition">
                                                {{ expandedLogs[student.student_id] ? 'Hide Logs' : 'View Logs' }}
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Expanded individual session logs table -->
                                    <tr v-if="expandedLogs[student.student_id]" class="bg-gray-50">
                                        <td colspan="8" class="p-6">
                                            <div class="border border-gray-150 rounded-xl overflow-hidden shadow-inner bg-white">
                                                <div class="px-4 py-2 border-b border-gray-100 bg-gray-50 text-[10px] font-bold text-[#0a1f44] uppercase tracking-wide">
                                                    Session Registry Logs for {{ student.name }}
                                                </div>
                                                <table class="w-full text-left text-xs border-collapse">
                                                    <thead>
                                                        <tr class="border-b border-gray-100 text-[9px] uppercase tracking-wider text-gray-400 font-bold bg-white">
                                                            <th class="px-4 py-2.5">Date</th>
                                                            <th class="px-4 py-2.5">Time Period</th>
                                                            <th class="px-4 py-2.5">Attendance status</th>
                                                            <th class="px-4 py-2.5">Remarks / Remarks Reason</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <tr v-for="log in student.logs" :key="log.session_id" class="hover:bg-gray-50 transition">
                                                            <td class="px-4 py-2 font-medium">{{ log.date }}</td>
                                                            <td class="px-4 py-2 text-gray-500">{{ formatTime(log.time.split(' - ')[0]) }} - {{ formatTime(log.time.split(' - ')[1]) }}</td>
                                                            <td class="px-4 py-2">
                                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                                                                    :class="{
                                                                        'bg-green-50 text-green-700': log.status === 'present',
                                                                        'bg-red-50 text-red-700': log.status === 'absent',
                                                                        'bg-amber-50 text-amber-700': log.status === 'late',
                                                                        'bg-blue-50 text-blue-700': log.status === 'excused',
                                                                        'bg-gray-50 text-gray-450': log.status === 'unrecorded'
                                                                    }">
                                                                    {{ log.status }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-2 text-gray-600 italic">{{ log.remarks || 'N/A' }}</td>
                                                        </tr>
                                                        <tr v-if="student.logs.length === 0">
                                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 italic">No scheduled timetable sessions found for this intake.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="attendanceHistory.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">No students are currently enrolled in this intake cohort.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Empty state when no intake selected -->
            <div v-else class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <h4 class="font-bold text-brand-blue text-base">Select a Course Batch Intake above to manage attendance records</h4>
                <p class="text-xs text-gray-400 mt-1">Attendance registers are scoped explicitly to each allocated class section timetable.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { ref, computed, watch, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    intakes: Array,
    selectedIntakeId: Number,
    selectedSessionId: Number,
    students: Array,
    sessions: Array,
    attendance: Object,
    attendanceHistory: Array,
});

const activeTab = ref('mark');
const expandedLogs = reactive({});
const submitting = ref(false);

const formState = reactive({});

// Watch for selected session changes to initialize formState
watch(() => props.selectedSessionId, (newVal) => {
    if (newVal) {
        props.students.forEach(st => {
            const recorded = props.attendance[st.id] || {};
            formState[st.id] = {
                student_id: st.id,
                status: recorded.status || 'present',
                remarks: recorded.remarks || '',
            };
        });
    }
}, { immediate: true });

// Also initialize formState on student roster updates
watch(() => props.students, (newStudents) => {
    newStudents.forEach(st => {
        if (!formState[st.id]) {
            const recorded = props.attendance[st.id] || {};
            formState[st.id] = {
                student_id: st.id,
                status: recorded.status || 'present',
                remarks: recorded.remarks || '',
            };
        }
    });
}, { deep: true });

const onIntakeChange = (e) => {
    Inertia.get(route('instructor.attendance.index'), {
        intake_id: e.target.value
    });
};

const onSessionChange = (e) => {
    Inertia.get(route('instructor.attendance.index'), {
        intake_id: props.selectedIntakeId,
        session_id: e.target.value
    });
};

const submitAttendance = () => {
    submitting.value = true;
    const postData = {
        course_timetable_id: props.selectedSessionId,
        attendance: Object.values(formState),
    };

    Inertia.post(route('instructor.attendance.record'), postData, {
        onBefore: () => { submitting.value = true; },
        onSuccess: () => { submitting.value = false; },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

const toggleLogs = (studentId) => {
    expandedLogs[studentId] = !expandedLogs[studentId];
};

const rateBadgeClass = (rate) => {
    if (rate >= 80) return 'bg-green-50 text-green-700 border border-green-200/50';
    if (rate >= 50) return 'bg-amber-50 text-amber-700 border border-amber-200/50';
    return 'bg-red-50 text-red-700 border border-red-200/50';
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    const parts = timeString.split(':');
    if (parts.length < 2) return timeString;
    return `${parts[0]}:${parts[1]}`;
};
</script>
