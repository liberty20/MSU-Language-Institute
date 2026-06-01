<template>
    <AuthenticatedLayout>
        <template #header>
            Continuous Assessment Marks Manager
        </template>

        <div class="space-y-8">
            <!-- Header Select Session Row -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Continuous Assessment (CA) Records</h3>
                    <p class="text-sm text-gray-500">Add assessment scores, upload batch marks, and review cohort academic statistics.</p>
                </div>
                <div class="w-full md:w-80">
                    <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Assigned Course Session *</label>
                    <select v-model="selectedIntake" @change="changeIntake" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                        <option v-for="item in intakes" :key="item.id" :value="item.id">{{ item.course.title }} ({{ item.name }})</option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!selectedIntakeId" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Assigned Course Sessions Scheduled</h4>
                <p class="text-xs text-gray-400 mt-1">Enrollment and CA records will appear here once you are allocated to active batches.</p>
            </div>

            <div v-else class="space-y-8">
                <!-- Analytics Indicators -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-1">
                        <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Class Average</span>
                        <h3 class="text-2xl font-black text-brand-blue">{{ analytics.class_average }}%</h3>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-1">
                        <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Highest Score</span>
                        <h3 class="text-2xl font-black text-emerald-600">{{ analytics.highest_score }}%</h3>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-1">
                        <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Lowest Score</span>
                        <h3 class="text-2xl font-black text-rose-600">{{ analytics.lowest_score }}%</h3>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm space-y-1">
                        <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Cohort Pass Rate</span>
                        <h3 class="text-2xl font-black text-brand-blue">{{ analytics.pass_rate }}%</h3>
                    </div>
                </div>

                <!-- Main Actions Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Class List Score Logging Table -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h4 class="font-bold text-[#0a1f44] text-sm">Class Student Grade Sheet</h4>
                        </div>
                        <div class="overflow-x-auto flex-grow">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-gray-150 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                        <th class="px-6 py-4">Student Name</th>
                                        <th class="px-6 py-4">Overall Score</th>
                                        <th class="px-6 py-4">Recorded Assessments</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    <tr v-for="student in students" :key="student.student_id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-gray-800">{{ student.name }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ student.email }}</p>
                                        </td>
                                        <td class="px-6 py-4 font-black" :class="student.overall_pct >= 50 ? 'text-green-600' : (student.overall_pct !== null ? 'text-rose-600' : 'text-gray-400')">
                                            {{ student.overall_pct !== null ? `${student.overall_pct}%` : 'No Marks' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 font-medium">
                                            <div class="flex flex-wrap gap-1.5 max-w-xs">
                                                <span v-for="m in student.marks" :key="m.id" class="inline-block px-2 py-0.5 bg-gray-100 border border-gray-200 text-gray-700 rounded-lg text-[9px]" :title="m.feedback">
                                                    {{ m.name }}: {{ m.obtained }}/{{ m.max }}
                                                </span>
                                                <span v-if="student.marks.length === 0" class="text-gray-400 italic">None logged.</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="openGradeModal(student)" class="text-blue-600 hover:text-blue-800 font-bold transition">Grade Student</button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                                            No verified students currently enrolled in this intake.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bulk Marks Upload Panel -->
                    <div class="lg:col-span-1 bg-white p-6 rounded-2xl border border-gray-150 shadow-sm space-y-6 self-start">
                        <div class="border-b border-gray-100 pb-3">
                            <h4 class="font-bold text-[#0a1f44] text-xs uppercase tracking-wider">Bulk CSV Marks Upload</h4>
                            <p class="text-[10px] text-gray-400 font-semibold mt-1">Upload student scores instantly using formatted CSV files.</p>
                        </div>

                        <!-- Template instructions -->
                        <div class="bg-blue-50/50 border border-blue-200/50 p-4 rounded-xl text-[11px] text-gray-600 leading-relaxed space-y-2">
                            <p class="font-bold text-brand-blue uppercase tracking-wider text-[10px]">Expected CSV Schema:</p>
                            <p class="font-mono bg-white p-2 rounded border border-gray-150 text-[10px] select-all">email, assessment_name, marks_obtained, max_marks, feedback</p>
                            <p class="italic text-[10px]">Row Example:<br/>student@msunli.edu, Quiz 1, 15, 20, Well done!</p>
                        </div>

                        <form @submit.prevent="uploadBulkMarks" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Select CSV File</label>
                                <input type="file" @change="handleCsvUpload" required class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-brand-blue hover:file:bg-[#0a1f44]/20" />
                            </div>
                            <button type="submit" :disabled="bulkUploading" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 rounded-xl transition shadow flex justify-center items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                {{ bulkUploading ? 'Uploading Marks...' : 'Upload Bulk Marks' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Input Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden border border-gray-150">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">Record CA Mark</h3>
                        <p class="text-[10px] text-[#f5c242] mt-0.5">Recording score for: {{ selectedStudent?.name }}</p>
                    </div>
                    <button @click="closeModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitGrade" class="p-6 space-y-4">
                    <!-- Assessment Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Assessment Title *</label>
                        <input v-model="form.assessment_name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Quiz 1, Midterm Test" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Marks Obtained -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Marks Obtained *</label>
                            <input v-model="form.marks_obtained" type="number" step="0.01" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. 17.5" />
                        </div>

                        <!-- Max Marks -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Max Possible Marks *</label>
                            <input v-model="form.max_marks" type="number" step="0.01" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. 20" />
                        </div>
                    </div>

                    <!-- Feedback Comments -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Assigned Feedback (Optional)</label>
                        <textarea v-model="form.feedback" rows="3" class="w-full text-xs rounded-xl border-gray-350 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Enter academic feedback comments..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs disabled:opacity-50">
                            {{ submitting ? 'Saving Marks...' : 'Save Assessment Score' }}
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
    intakes: Array,
    students: Array,
    analytics: Object,
    selectedIntakeId: Number,
});

const selectedIntake = ref(props.selectedIntakeId || (props.intakes.length > 0 ? props.intakes[0].id : ''));
const modalOpen = ref(false);
const submitting = ref(false);
const selectedStudent = ref(null);
const csvFile = ref(null);
const bulkUploading = ref(false);

const form = reactive({
    course_intake_id: selectedIntake.value,
    user_id: '',
    assessment_name: '',
    marks_obtained: '',
    max_marks: '',
    feedback: '',
});

const changeIntake = () => {
    Inertia.get(route('instructor.ca.index'), { intake_id: selectedIntake.value }, { preserveState: true });
};

const openGradeModal = (student) => {
    selectedStudent.value = student;
    form.user_id = student.student_id;
    form.course_intake_id = selectedIntake.value;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    selectedStudent.value = null;
    form.user_id = '';
    form.assessment_name = '';
    form.marks_obtained = '';
    form.max_marks = '';
    form.feedback = '';
};

const submitGrade = () => {
    submitting.value = true;

    Inertia.post(route('instructor.ca.store'), form, {
        onSuccess: () => {
            submitting.value = false;
            closeModal();
        },
        onError: () => {
            submitting.value = false;
        }
    });
};

const handleCsvUpload = (e) => {
    csvFile.value = e.target.files[0];
};

const uploadBulkMarks = () => {
    if (!csvFile.value) return;

    bulkUploading.value = true;
    const formData = new FormData();
    formData.append('course_intake_id', selectedIntake.value);
    formData.append('marks_file', csvFile.value);

    Inertia.post(route('instructor.ca.bulk-upload'), formData, {
        onSuccess: () => {
            bulkUploading.value = false;
            csvFile.value = null;
        },
        onError: () => {
            bulkUploading.value = false;
        }
    });
};
</script>
