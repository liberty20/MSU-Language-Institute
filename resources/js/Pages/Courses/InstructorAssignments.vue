<template>
    <AuthenticatedLayout>
        <template #header>
            Course Assignments Manager
        </template>

        <div class="space-y-8">
            <!-- Header Banner -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Assigned Coursework &amp; Student Submissions</h3>
                    <p class="text-sm text-gray-500">Publish assignment tasks, upload resource materials, and grade student submissions in real-time.</p>
                </div>
                <button @click="openCreateModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Assignment
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="assignments.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Assignments Created Yet</h4>
                <p class="text-xs text-gray-400 mt-1">Get started by clicking the "Create Assignment" button to assign tasks to your active student batches.</p>
            </div>

            <!-- Assignments Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="asg in assignments" :key="asg.id" class="bg-white rounded-2xl border border-gray-150 overflow-hidden flex flex-col shadow-sm hover:shadow-md transition">
                    <div class="p-6 flex-grow space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-50 pb-3">
                            <span class="px-2.5 py-0.5 text-[0.65rem] font-bold bg-[#0a1f44]/10 text-brand-blue rounded-full uppercase tracking-wider">
                                {{ asg.intake.course.code }}
                            </span>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">{{ asg.intake.name }}</span>
                        </div>

                        <div>
                            <h3 class="text-lg font-black text-brand-blue leading-snug">{{ asg.title }}</h3>
                            <p class="text-xs text-gray-500 mt-1">Course: {{ asg.intake.course.title }}</p>
                        </div>

                        <p class="text-xs text-gray-650 leading-relaxed">{{ asg.description || 'No description provided.' }}</p>

                        <div class="grid grid-cols-2 gap-4 text-xs font-semibold text-gray-600">
                            <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <span class="text-[0.6rem] text-gray-450 uppercase block">Due Date</span>
                                <span class="text-gray-800">{{ asg.due_date }}</span>
                            </div>
                            <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                <span class="text-[0.6rem] text-gray-450 uppercase block">Max Marks</span>
                                <span class="text-gray-800">{{ asg.max_marks }} Marks</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex justify-between items-center text-xs">
                            <span class="text-gray-450 font-bold">Submissions Progress:</span>
                            <span class="font-extrabold text-brand-blue">{{ asg.graded_submissions }} Graded / {{ asg.total_submissions }} Submitted</span>
                        </div>
                    </div>

                    <!-- Footer Action Strip -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-150 flex flex-col sm:flex-row gap-3">
                        <button @click="viewSubmissions(asg)" class="flex-1 bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2 px-4 rounded-xl transition shadow text-center flex justify-center items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Submissions list
                        </button>
                        <div class="flex gap-2">
                            <button @click="openEditModal(asg)" class="px-4 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition text-xs">Edit</button>
                            <button @click="deleteItem(asg.id)" class="px-4 py-2 border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl transition text-xs">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Assignment Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in" @click.self="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-150">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">{{ editMode ? 'Edit Assignment details' : 'Publish a New Assignment' }}</h3>
                        <p class="text-[9px] text-[#f5c242] mt-0.5">MSU National Language Institute Portal</p>
                    </div>
                    <button @click="closeModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Session selection -->
                    <div v-if="!editMode">
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Allocated Course Batch *</label>
                        <select v-model="form.course_intake_id" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="">Select Cohort Intake...</option>
                            <option v-for="item in intakes" :key="item.id" :value="item.id">{{ item.course.title }} ({{ item.name }})</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Assignment Title *</label>
                        <input v-model="form.title" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. swahili conversational dialogue task" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Assignment description / guidelines</label>
                        <textarea v-model="form.description" rows="3" class="w-full text-xs rounded-xl border-gray-350 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Details or task questions..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Due date -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Due Date *</label>
                            <input v-model="form.due_date" type="datetime-local" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                        </div>

                        <!-- Max marks -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Max Score Points *</label>
                            <input v-model="form.max_marks" type="number" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. 100" />
                        </div>
                    </div>

                    <!-- Resource Files (Dynamic multi-file) -->
                    <div v-if="!editMode" class="space-y-2">
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Upload Resource Materials (Optional)</label>
                        
                        <div v-for="(field, idx) in fileFields" :key="field.id" class="flex items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200 transition hover:border-[#0a1f44]/20">
                            <div class="flex-grow">
                                <input 
                                    type="file"
                                    :id="`assign-file-${field.id}`"
                                    @change="handleFileChange(idx, $event)"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg"
                                    class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-[11px] file:font-bold file:bg-[#0a1f44]/10 file:text-brand-blue hover:file:bg-[#0a1f44]/20 cursor-pointer"
                                />
                            </div>
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <span v-if="field.file" class="text-[10px] text-green-600 font-bold">{{ (field.file.size / 1024).toFixed(0) }} KB</span>
                                <button v-if="fileFields.length > 1" type="button" @click="removeFileField(idx)"
                                    class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="button" @click="addFileField"
                                :disabled="fileFields.length >= 5"
                                class="text-xs font-bold text-[#0a1f44] hover:text-white bg-[#0a1f44]/5 hover:bg-[#0a1f44] px-3 py-1.5 rounded-lg border border-[#0a1f44]/15 hover:border-[#0a1f44] transition-all flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Another File
                            </button>
                            <span class="text-[10px] text-gray-400 font-semibold">{{ fileFields.filter(f => f.file).length }}/5 files · PDF, DOC, PPT, ZIP, IMG · Max 10MB</span>
                        </div>
                    </div>


                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs disabled:opacity-50">
                            {{ submitting ? 'Publishing Task...' : 'Publish coursework' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Submissions Log Modal -->
        <div v-if="submissionsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeSubmissionsModal">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden border border-gray-150 my-8">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">Submissions Log</h3>
                        <p class="text-[10px] text-[#f5c242] mt-0.5">{{ selectedAsg?.course_title }} - {{ selectedAsg?.title }}</p>
                    </div>
                    <button @click="closeSubmissionsModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Submission Tables -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-[10px] uppercase tracking-wider text-gray-450 font-bold">
                                    <th class="px-4 py-3">Student Name</th>
                                    <th class="px-4 py-3">Submitted Date</th>
                                    <th class="px-4 py-3">Notes</th>
                                    <th class="px-4 py-3">Attachment File</th>
                                    <th class="px-4 py-3">Graded Score</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="sub in submissions" :key="sub.id" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-800">{{ sub.student.name }}</p>
                                        <p class="text-[9px] text-gray-400 font-medium">{{ sub.student.email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ formatDate(sub.submitted_at) }}</td>
                                    <td class="px-4 py-3 text-gray-500 italic max-w-xs truncate" :title="sub.notes">{{ sub.notes || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <a :href="'/storage/' + sub.attachment_path" target="_blank" class="text-blue-600 font-bold hover:underline flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            View Work
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 font-bold" :class="sub.status === 'graded' ? 'text-green-600' : 'text-gray-400'">
                                        {{ sub.status === 'graded' ? `${sub.marks_obtained} / ${selectedAsg?.max_marks}` : 'Not Graded' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="openGradingPanel(sub)" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-3 py-1 rounded-full transition shadow text-[10px] uppercase">
                                            Grade
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="submissions.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">
                                        No student work has been submitted for this assignment yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Entry Modal -->
        <div v-if="gradingPanelOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="gradingPanelOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden border border-gray-150 animate-fade-in">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">Grade Student Submission</h3>
                        <p class="text-[10px] text-[#f5c242] mt-0.5">Evaluating: {{ activeSubmission?.student.name }}</p>
                    </div>
                    <button @click="gradingPanelOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitGrading" class="p-6 space-y-4">
                    <!-- Marks Obtained -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Marks Obtained * (Out of {{ selectedAsg?.max_marks }})</label>
                        <input v-model="gradeForm.marks_obtained" type="number" step="0.01" :max="selectedAsg?.max_marks" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. 85" />
                    </div>

                    <!-- Feedback -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Feedback Comments</label>
                        <textarea v-model="gradeForm.feedback" rows="4" class="w-full text-xs rounded-xl border-gray-350 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Excellent presentation. Pay attention to grammar..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="gradingPanelOpen = false" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="gradingSubmitting" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs disabled:opacity-50">
                            {{ gradingSubmitting ? 'Submitting Grade...' : 'Save Evaluation' }}
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
import axios from 'axios';

const props = defineProps({
    assignments: Array,
    intakes: Array,
});

const modalOpen = ref(false);
const editMode = ref(false);
const submitting = ref(false);
const selectedId = ref(null);

// Multi-file upload state
let _fieldCounter = 0;
const fileFields = ref([{ id: _fieldCounter++, file: null }]);

const addFileField = () => {
    if (fileFields.value.length < 5) {
        fileFields.value.push({ id: _fieldCounter++, file: null });
    }
};

const removeFileField = (idx) => {
    fileFields.value.splice(idx, 1);
};

const handleFileChange = (idx, e) => {
    const file = e.target.files[0];
    if (!file) { fileFields.value[idx].file = null; return; }
    // 10MB size check
    if (file.size > 10 * 1024 * 1024) {
        alert('File "' + file.name + '" exceeds the 10MB limit.');
        e.target.value = '';
        fileFields.value[idx].file = null;
        return;
    }
    fileFields.value[idx].file = file;
};

const form = reactive({
    course_intake_id: '',
    title: '',
    description: '',
    due_date: '',
    max_marks: '',
});

const submissionsModalOpen = ref(false);
const selectedAsg = ref(null);
const submissions = ref([]);

const gradingPanelOpen = ref(false);
const activeSubmission = ref(null);
const gradingSubmitting = ref(false);

const gradeForm = reactive({
    marks_obtained: '',
    feedback: '',
});

const openCreateModal = () => {
    editMode.value = false;
    modalOpen.value = true;
};

const openEditModal = (item) => {
    editMode.value = true;
    selectedId.value = item.id;
    form.course_intake_id = item.course_intake_id;
    form.title = item.title;
    form.description = item.description;
    form.due_date = item.due_date.replace(' ', 'T');
    form.max_marks = item.max_marks;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editMode.value = false;
    selectedId.value = null;
    form.course_intake_id = '';
    form.title = '';
    form.description = '';
    form.due_date = '';
    form.max_marks = '';
    // Reset multi-file fields
    fileFields.value = [{ id: _fieldCounter++, file: null }];
};

const submitForm = () => {
    submitting.value = true;

    if (editMode.value) {
        Inertia.put(route('instructor.assignments.update', selectedId.value), form, {
            onSuccess: () => { submitting.value = false; closeModal(); },
            onError:   () => { submitting.value = false; }
        });
    } else {
        const formData = new FormData();
        formData.append('course_intake_id', form.course_intake_id);
        formData.append('title', form.title);
        formData.append('description', form.description || '');
        formData.append('due_date', form.due_date);
        formData.append('max_marks', form.max_marks);

        // Append each selected file as attachments[]
        const selectedFiles = fileFields.value.filter(f => f.file);
        selectedFiles.forEach(f => {
            formData.append('attachments[]', f.file, f.file.name);
        });

        Inertia.post(route('instructor.assignments.store'), formData, {
            onSuccess: () => { submitting.value = false; closeModal(); },
            onError:   () => { submitting.value = false; }
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this course assignment? Student submissions will also be deleted.')) {
        Inertia.delete(route('instructor.assignments.destroy', id));
    }
};

const viewSubmissions = (asg) => {
    selectedAsg.value = asg;
    axios.get(route('instructor.assignments.submissions', asg.id)).then(res => {
        submissions.value = res.data.submissions;
        submissionsModalOpen.value = true;
    }).catch(err => console.error(err));
};

const closeSubmissionsModal = () => {
    submissionsModalOpen.value = false;
    selectedAsg.value = null;
    submissions.value = [];
};

const openGradingPanel = (submission) => {
    activeSubmission.value = submission;
    gradeForm.marks_obtained = submission.marks_obtained || '';
    gradeForm.feedback = submission.feedback || '';
    gradingPanelOpen.value = true;
};

const submitGrading = () => {
    gradingSubmitting.value = true;
    axios.post(route('instructor.submissions.grade', activeSubmission.value.id), gradeForm).then(() => {
        gradingSubmitting.value = false;
        gradingPanelOpen.value = false;
        activeSubmission.value = null;
        
        // Refresh submissions list
        axios.get(route('instructor.assignments.submissions', selectedAsg.value.id)).then(res => {
            submissions.value = res.data.submissions;
        });
    }).catch(err => {
        gradingSubmitting.value = false;
        console.error(err);
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
